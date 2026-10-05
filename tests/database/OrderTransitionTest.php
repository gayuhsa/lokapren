<?php

declare(strict_types=1);

use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\SellerDailyStatModel;
use App\Services\OrderService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\MarketplaceFixtures;

/**
 * Order lifecycle: transitions, audit history, and ownership checks.
 *
 * @internal
 */
final class OrderTransitionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    public function testOrderOwnershipRespected(): void
    {
        $customerA = $this->makeUser('cust-a');
        $customerB = $this->makeUser('cust-b');
        $sellerA = $this->makeSeller('a')[0];
        $sellerB = $this->makeSeller('b')[0];
        $category = $this->makeCategory('Cat');

        [, $productA] = $this->makeProduct($sellerA, $category);
        [, $orderId] = $this->makeOrder($customerA, $sellerA, $productA);

        $orders = new OrderModel();

        $this->assertNotNull($orders->findForCustomer($orderId, $customerA));
        $this->assertNull($orders->findForCustomer($orderId, $customerB));

        $this->assertNotNull($orders->findForSeller($orderId, $sellerA));
        $this->assertNull($orders->findForSeller($orderId, $sellerB));
    }

    public function testValidTransitionWritesAuditHistory(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);
        [, $orderId] = $this->makeOrder($customer, $seller, $product, ['status' => OrderModel::STATUS_AWAITING_ARTISAN]);

        $orders = new OrderModel();
        $history = new OrderStatusHistoryModel();

        $this->assertTrue($orders->transition($orderId, OrderModel::STATUS_IN_PRODUCTION, $seller, 'Mulai produksi'));
        $order = $orders->find($orderId);
        $this->assertSame(OrderModel::STATUS_IN_PRODUCTION, $order['status']);

        $logs = $history->forOrder($orderId);
        $this->assertNotEmpty($logs);
        $this->assertSame(OrderModel::STATUS_AWAITING_ARTISAN, $logs[0]['from_status']);
        $this->assertSame(OrderModel::STATUS_IN_PRODUCTION, $logs[0]['to_status']);
        $this->assertSame($seller, (int) $logs[0]['actor_id']);
        $this->assertSame('Mulai produksi', $logs[0]['note']);
    }

    public function testInvalidTransitionRejectedAndNoAuditWritten(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);
        [, $orderId] = $this->makeOrder($customer, $seller, $product, ['status' => OrderModel::STATUS_PENDING_PAYMENT]);

        $orders = new OrderModel();
        $history = new OrderStatusHistoryModel();

        $beforeCount = count($history->forOrder($orderId));
        $this->assertFalse($orders->transition($orderId, OrderModel::STATUS_COMPLETED, $seller, 'Skip'));
        $order = $orders->find($orderId);
        $this->assertSame(OrderModel::STATUS_PENDING_PAYMENT, $order['status']);
        $this->assertCount($beforeCount, $history->forOrder($orderId));
    }

    public function testTransitionSetsTimestamps(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);
        [, $orderId] = $this->makeOrder($customer, $seller, $product, ['status' => OrderModel::STATUS_READY_TO_SHIP]);

        $orders = new OrderModel();
        $orders->transition($orderId, OrderModel::STATUS_SHIPPED, $seller, 'Kirim dengan ekspedisi');
        $order = $orders->find($orderId);
        $this->assertNotEmpty($order['shipped_at']);
        $this->assertNotEmpty($order['updated_at']);

        $orders->transition($orderId, OrderModel::STATUS_DELIVERED, $seller, 'Terkirim');
        $order = $orders->find($orderId);
        $this->assertNotEmpty($order['delivered_at']);

        $orders->transition($orderId, OrderModel::STATUS_COMPLETED, $customer, 'Selesai');
        $order = $orders->find($orderId);
        $this->assertNotEmpty($order['completed_at']);
    }

    public function testCancellationReturnsProductStockAndSoldCount(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category, [
            'stock'      => 5,
            'sold_count' => 0,
        ]);

        [, $orderId, $orderItemId] = $this->makeOrder($customer, $seller, $product, [
            'status' => OrderModel::STATUS_AWAITING_ARTISAN,
        ]);

        db_connect()->table('order_items')
            ->where('id', $orderItemId)
            ->update(['quantity' => 2]);

        // Checkout would have taken two units away and counted two sales.
        db_connect()->table('products')
            ->set('stock', 'stock - 2', false)
            ->set('sold_count', 'sold_count + 2', false)
            ->where('id', $product)
            ->update();

        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer, 'Berubah pikiran');

        $this->assertTrue($result['ok'], $result['message']);

        $productRow = db_connect()->table('products')->where('id', $product)->get()->getRowArray();
        $this->assertSame(5, (int) $productRow['stock'], 'Cancelled order must return its stock.');
        $this->assertSame(0, (int) $productRow['sold_count'], 'Cancelled order must not count as a sale.');

        $order = (new OrderModel())->find($orderId);
        $this->assertSame(OrderModel::STATUS_CANCELLED, $order['status']);

        $item = (new OrderItemModel())->find($orderItemId);
        $this->assertSame(2, (int) $item['quantity']);
    }

    public function testCancellationReturnsVariantStockNotProductStock(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category, [
            'stock'      => 9,
            'sold_count' => 1,
        ]);

        $variantId = $this->insertFixture('product_variants', [
            'product_id'  => $product,
            'variant_code' => 'V-BESAR',
            'label'       => 'Ukuran besar',
            'price'       => 450000,
            'stock'       => 4,
            'is_active'   => 1,
            'created_at'  => $this->fixtureNow(),
            'updated_at'  => $this->fixtureNow(),
        ]);

        [, $orderId, $orderItemId] = $this->makeOrder($customer, $seller, $product);
        db_connect()->table('order_items')->where('id', $orderItemId)->update(['variant_id' => $variantId]);

        db_connect()->table('product_variants')
            ->set('stock', 'stock - 1', false)
            ->where('id', $variantId)
            ->update();

        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer);

        $this->assertTrue($result['ok'], $result['message']);

        $variant = db_connect()->table('product_variants')->where('id', $variantId)->get()->getRowArray();
        $this->assertSame(4, (int) $variant['stock'], 'Variant stock must be the one returned.');

        // The parent row must be untouched: writing to `products` here would
        // have changed the wrong row entirely.
        $productRow = db_connect()->table('products')->where('id', $product)->get()->getRowArray();
        $this->assertSame(9, (int) $productRow['stock'], 'Product stock must not change for a variant line.');
        $this->assertSame(0, (int) $productRow['sold_count']);
    }

    public function testMadeToOrderCancellationLeavesStockAlone(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category, [
            'made_to_order' => 1,
            'stock'        => 0,
            'sold_count'   => 3,
        ]);

        [, $orderId] = $this->makeOrder($customer, $seller, $product);

        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer);

        $this->assertTrue($result['ok'], $result['message']);

        $productRow = db_connect()->table('products')->where('id', $product)->get()->getRowArray();
        $this->assertSame(0, (int) $productRow['stock']);
        $this->assertSame(3, (int) $productRow['sold_count'], 'Made-to-order stock was never decremented, so it must not be credited.');
    }

    public function testCancellationReversesTheDayCheckoutCountedNotToday(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);

        // Placed yesterday, cancelled today.
        $placedAt = date('Y-m-d 21:30:00', strtotime('-1 day'));
        [, $orderId] = $this->makeOrder($customer, $seller, $product, [
            'created_at' => $placedAt,
            'placed_at'  => $placedAt,
        ]);

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stats = new SellerDailyStatModel();
        $stats->increment($seller, $yesterday, ['order_count' => 1]);

        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer);

        $this->assertTrue($result['ok'], $result['message']);

        $row = db_connect()->table('seller_daily_stats')
            ->where('seller_id', $seller)
            ->where('stat_date', $yesterday)
            ->get()
            ->getRowArray();

        $this->assertSame(0, (int) $row['order_count'], 'Yesterday\'s count must come back down.');
    }

    public function testCancellationDoesNotCreateANegativeCounterRow(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);
        [, $orderId] = $this->makeOrder($customer, $seller, $product);

        // No daily stat row exists at all for this seller.
        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer);

        $this->assertTrue($result['ok'], $result['message']);

        $rows = db_connect()->table('seller_daily_stats')
            ->where('seller_id', $seller)
            ->where('order_count <', 0)
            ->get()
            ->getRowArray();

        $this->assertNull($rows, 'No stat row may go negative.');
    }

    public function testShippedOrderCannotBeCancelled(): void
    {
        $customer = $this->makeUser('cust');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category, ['stock' => 4]);
        [, $orderId] = $this->makeOrder($customer, $seller, $product, [
            'status' => OrderModel::STATUS_SHIPPED,
        ]);

        $service = new OrderService();
        $result = $service->cancelForUser($orderId, $customer);

        $this->assertFalse($result['ok']);

        $productRow = db_connect()->table('products')->where('id', $product)->get()->getRowArray();
        $this->assertSame(4, (int) $productRow['stock'], 'A refused cancellation must not touch stock.');
        $this->assertSame(OrderModel::STATUS_SHIPPED, (new OrderModel())->find($orderId)['status']);
    }

    /**
     * Checkout normally pre-creates the shipment row, but an order staged or
     * seeded without one must still keep the resi the seller types in — the
     * tracking used to be written with an UPDATE that matched zero rows and
     * vanished silently.
     */
    public function testShippingCreatesTheShipmentRowWhenNoneExists(): void
    {
        $customer = $this->makeUser('cust');
        $seller   = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);
        [, $orderId] = $this->makeOrder($customer, $seller, $product, [
            'status' => OrderModel::STATUS_READY_TO_SHIP,
        ]);

        $result = (new OrderService())->advanceAsSeller($orderId, $seller, OrderModel::STATUS_SHIPPED, [
            'tracking_number' => 'RESI-991',
        ]);

        $this->assertTrue($result['ok'], $result['message']);

        $shipment = $this->db->table('order_shipments')->where('order_id', $orderId)->get()->getRowArray();
        $this->assertNotNull($shipment, 'Shipping must leave a shipment row even when none existed');
        $this->assertSame('RESI-991', (string) $shipment['tracking_number']);
        $this->assertNotNull($shipment['shipped_at']);
    }
}
