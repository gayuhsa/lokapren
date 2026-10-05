<?php

declare(strict_types=1);

use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
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
}
