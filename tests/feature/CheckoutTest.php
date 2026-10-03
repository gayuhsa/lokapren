<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesCheckoutOptions;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;
use Tests\Support\PostsWithCsrf;

/**
 * The cart and checkout are the place where the marketplace handles money, so
 * these tests are mostly about what the server refuses to take from a browser:
 * a submitted price, a submitted total, another customer's cart line, and an
 * order id that belongs to somebody else.
 *
 * @internal
 */
final class CheckoutTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use CreatesCheckoutOptions;
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PostsWithCsrf;

    protected $migrate = true;

    protected $namespace = null;

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function marketplace(): array
    {
        $seller  = $this->createSeller();
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Relung Jati Borobudur']);
        $variant = $this->makeVariant($product, [
            'name'  => 'Ukuran 40cm',
            'price' => 250000,
            'stock' => 4,
        ]);

        return [$seller, $store, ['product' => $product, 'variant' => $variant]];
    }

    private function seedOptions(int $shippingCost = 20000): void
    {
        $this->makeShippingMethod('pesan_antar_terima', $shippingCost, ['name' => 'Pesan Antar Terima', 'sort_order' => 0]);
        $this->makePaymentMethod('qris', ['name' => 'QRIS', 'sort_order' => 0]);
    }

    public function testGuestsAreSentToLoginInsteadOfTheCart(): void
    {
        $result = $this->get('/cart');

        $result->assertRedirect();
    }

    public function testGuestsCannotPostToTheCart(): void
    {
        // Rejected by CSRF before any handler runs, so a guest cannot create a
        // cart row even with a forged request.
        try {
            $this->post('/cart/add', ['variant_id' => 1, 'qty' => 1]);
            $this->fail('A guest POST without a CSRF token should be refused.');
        } catch (SecurityException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, $this->db->table('cart_items')->countAllResults());
    }

    public function testAProductPageLetsASignedInCustomerAddToTheCart(): void
    {
        [, , $data] = $this->marketplace();
        $customer    = $this->createCustomer();

        $result = $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 2,
        ], '/product/' . $data['product']['slug']);

        $result->assertRedirectTo('/checkout');

        $this->assertSame(1, $this->db->table('cart_items')->countAllResults());

        $line = $this->db->table('cart_items')->get()->getRowArray();
        $this->assertSame(2, (int) $line['qty']);
    }

    public function testTheCartPageShowsTheServerSidePriceNotASubmittedOne(): void
    {
        [, , $data] = $this->marketplace();
        $customer    = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
            'price'      => 1,
            'line_total' => 1,
        ], '/product/' . $data['product']['slug']);

        $result = $this->actingAs($customer)->get('/cart');

        $result->assertOK();
        $result->assertSee('Rp250.000');
        $result->assertDontSee('Rp1');
    }

    public function testTheCartRefusesMoreThanTheStockOnHand(): void
    {
        [, , $data] = $this->marketplace();
        $customer    = $this->createCustomer();

        $result = $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 99,
        ], '/product/' . $data['product']['slug']);

        $this->assertSame(0, $this->db->table('cart_items')->countAllResults());
        $this->assertStringContainsString('tidak cukup', $this->flashes()['error'] ?? '');
    }

    public function testACustomerCannotEditOrRemoveAnotherCustomersCartLine(): void
    {
        [, , $data] = $this->marketplace();
        $owner       = $this->createCustomer('cart.owner');
        $intruder    = $this->createCustomer('cart.intruder');

        $this->actingAs($owner)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 2,
        ], '/product/' . $data['product']['slug']);

        $lineId = (int) $this->db->table('cart_items')->get()->getRowArray()['id'];

        $this->actingAs($intruder)->postWithCsrf('/cart/line/' . $lineId, ['qty' => 1], '/cart');

        $this->actingAs($intruder)->postWithCsrf('/cart/line/' . $lineId . '/remove', [], '/cart');

        // The owner's line is untouched by either attempt.
        $line = $this->db->table('cart_items')->where('id', $lineId)->get()->getRowArray();
        $this->assertSame(2, (int) $line['qty']);
    }

    public function testTheCheckoutPageQuotesTotalsFromTheDatabase(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(20000);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 2,
        ], '/product/' . $data['product']['slug']);

        $result = $this->actingAs($customer)->get('/checkout');

        $result->assertOK();
        $result->assertSee('Checkout');
        $result->assertSee('Pesan Antar Terima');
        $result->assertSee('QRIS');
        // 2 x Rp250.000 subtotal, shown in the summary as the server computed it.
        $result->assertSee('Rp500.000');
    }

    public function testPlacingAnOrderCreatesAnOrderWithServerComputedTotals(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(20000);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 2,
        ], '/product/' . $data['product']['slug']);

        $result = $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
            // A tampered form trying to set its own money.
            'subtotal'   => 1000,
            'total'      => 1,
            'shipping'   => 0,
            'discount'   => 0,
            'customer_id' => 9999,
        ]), '/checkout');

        $result->assertRedirect();

        $order = $this->db->table('orders')->get()->getRowArray();

        $this->assertNotNull($order);
        $this->assertSame($customer->id, (int) $order['customer_id'], 'owner comes from Shield, not the form');
        $this->assertSame('pending', $order['status']);
        $this->assertSame(500000, (int) $order['subtotal']);
        $this->assertSame(20000, (int) $order['delivery_fee']);
        $this->assertSame(520000, (int) $order['total']);

        $this->assertSame(1, $this->db->table('order_items')->countAllResults());
        $this->assertSame(1, $this->db->table('order_deliveries')->countAllResults());
        $this->assertSame(1, $this->db->table('order_payments')->countAllResults());
    }

    public function testPlacingAnOrderDecrementsStockAndEmptiesTheCart(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(0);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 3,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $variant = $this->db->table('product_variants')->where('id', $data['variant']['id'])->get()->getRowArray();
        $this->assertSame(1, (int) $variant['stock']);
        $this->assertSame(0, $this->db->table('cart_items')->countAllResults());
    }

    public function testPlacingAnOrderWithoutAShippingOptionIsRefused(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions();
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'kurir-palsu',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $this->assertSame(0, $this->db->table('orders')->countAllResults());
        $this->assertStringContainsString('tidak tersedia', $this->flashes()['error'] ?? '');
    }

    public function testAnInactivePaymentMethodIsRefused(): void
    {
        [, , $data] = $this->marketplace();
        $this->makeShippingMethod('pesan_antar_terima', 0, ['name' => 'Pesan Antar Terima']);
        $this->makePaymentMethod('qris', ['name' => 'QRIS']);
        $this->makePaymentMethod('matahari', ['is_active' => false]);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'matahari',
        ]), '/checkout');

        $this->assertSame(0, $this->db->table('orders')->countAllResults());
    }

    public function testAPromoCodeReducesTheOrderTotal(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(20000);
        $this->makePromo('HEMAT50', ['discount_amount' => 50000, 'min_subtotal' => 300000]);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 2,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/promo', ['code' => 'HEMAT50'], '/checkout');

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $order = $this->db->table('orders')->get()->getRowArray();

        $this->assertSame(50000, (int) $order['discount_amount']);
        $this->assertSame(470000, (int) $order['total']);
        $this->assertSame(1, $this->db->table('order_promos')->countAllResults());
    }

    public function testAPromoBelowItsMinimumIsRejected(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions();
        $this->makePromo('MINIM', ['discount_amount' => 5000, 'min_subtotal' => 9000000]);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/promo', ['code' => 'MINIM'], '/checkout');

        $this->assertNull($this->db->table('carts')->where('user_id', $customer->id)->get()->getRowArray()['promo_code'] ?? null);
        $this->assertStringContainsString('belum terpenuhi', $this->flashes()['error'] ?? '');
    }

    public function testADiscountNeverExceedsTheSubtotal(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(0);
        $this->makePromo('GILAS', ['discount_amount' => 999999999]);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/promo', ['code' => 'GILAS'], '/checkout');
        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $order = $this->db->table('orders')->get()->getRowArray();

        $this->assertSame(250000, (int) $order['discount_amount']);
        $this->assertSame(0, (int) $order['total'], 'total never goes negative');
    }

    public function testAnIncompleteAddressIsRefused(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions();
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'recipient_name' => '',
        ]), '/checkout');

        $this->assertSame(0, $this->db->table('orders')->countAllResults());
    }

    public function testCheckoutWithAnEmptyCartRedirectsAway(): void
    {
        $this->seedOptions();
        $customer = $this->createCustomer();

        $this->actingAs($customer)->get('/checkout')->assertRedirectTo('/cart');
    }

    public function testACustomerCannotReadAnotherCustomersOrder(): void
    {
        [$seller, , $data] = $this->marketplace();
        $this->seedOptions(0);
        $owner    = $this->createCustomer('order.owner');
        $intruder = $this->createCustomer('order.intruder');

        $this->actingAs($owner)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($owner)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $orderId = (int) $this->db->table('orders')->get()->getRowArray()['id'];

        $this->expectException(PageNotFoundException::class);
        $this->actingAs($intruder)->get('/order/' . $orderId);
    }

    public function testTheOrderListOfAnotherCustomerShowsNoneOfTheirOrders(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(0);
        $owner    = $this->createCustomer('list.owner');
        $intruder = $this->createCustomer('list.intruder');

        $this->actingAs($owner)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($owner)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $result = $this->actingAs($intruder)->get('/orders');

        $result->assertOK();
        $result->assertDontSee('Relung Jati Borobudur');
    }

    public function testTheOrderPageShowsTheOrderToItsOwner(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(20000);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $orderId = (int) $this->db->table('orders')->get()->getRowArray()['id'];

        $result = $this->actingAs($customer)->get('/order/' . $orderId);

        $result->assertOK();
        $result->assertSee('Relung Jati Borobudur');
        $result->assertSee('Budi Santoso');
        $result->assertSee('Rp250.000');
    }

    public function testAnUnverifiedStoreCannotBeBoughtFrom(): void
    {
        $seller   = $this->createSeller();
        $store    = $this->makeStore($seller, ['verification_status' => 'pending']);
        $product  = $this->makeProduct($store);
        $variant  = $this->makeVariant($product);
        $this->seedOptions();
        $customer = $this->createCustomer();

        // The product page is 404 for an unverified store, so the token comes
        // from a public page and the post goes straight at the endpoint.
        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $variant['id'],
            'qty'        => 1,
        ], '/marketplace');

        $this->assertSame(0, $this->db->table('cart_items')->countAllResults());
    }

    public function testStockIsNotReservedWhenTheOrderFails(): void
    {
        [, , $data] = $this->marketplace();
        $this->seedOptions(0);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $data['variant']['id'],
            'qty'        => 1,
        ], '/product/' . $data['product']['slug']);

        // Drive the variant below what the cart wants, so the guarded update
        // fails and the whole transaction has to roll back.
        $this->db->table('product_variants')
            ->where('id', $data['variant']['id'])
            ->update(['stock' => 0]);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $this->assertSame(0, $this->db->table('orders')->countAllResults());
        $this->assertSame(0, $this->db->table('order_items')->countAllResults());
        $this->assertSame(1, $this->db->table('cart_items')->countAllResults(), 'the cart survives a failed order');
    }

    /**
     * @return array<string, mixed>
     */
    private function flashes(): array
    {
        return session()->getFlashdata() ?? [];
    }
}