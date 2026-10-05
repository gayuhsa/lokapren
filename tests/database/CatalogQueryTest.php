<?php

declare(strict_types=1);

use App\Services\CartService;
use App\Models\OrderModel;
use App\Services\CatalogService;
use App\Services\OrderService;
use App\Services\ProfileService;
use App\Services\ShopService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\MarketplaceFixtures;

/**
 * The read queries behind the public and account pages, run against the real
 * schema.
 *
 * These services compose their SQL by hand rather than going through model
 * `find()`, so a column rename cannot be caught by a model test — a wrong column
 * only shows up as a database error when the page renders. Asserting on the
 * returned shape here keeps that failure in the test suite instead of in a
 * browser.
 *
 * @internal
 */
final class CatalogQueryTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    public function testSearchReturnsProductWithItsCoverImage(): void
    {
        [$sellerId] = $this->makeSeller('sanggar-kayu');
        $categoryId = $this->makeCategory('Gebyok');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'name'   => 'Gebyok Ukir Tulap',
            'status' => 'published',
        ]);

        $imageId = $this->insertFixture('product_images', [
            'product_id' => $productId,
            'file_path'  => 'products/gebyok-1.jpg',
            'thumb_path' => 'products/gebyok-1-thumb.jpg',
            'alt_text'   => 'Gebyok ukir tulap',
            'is_primary' => 1,
            'position'   => 0,
            'created_at' => $this->fixtureNow(),
        ]);

        $result = (new CatalogService())->search(['q' => 'Gebyok']);

        $this->assertGreaterThan(0, $result['total']);

        $found = null;

        foreach ($result['products'] as $product) {
            if ((int) $product['id'] === $productId) {
                $found = $product;
            }
        }

$this->assertNotNull($found, 'A published product must appear in search results.');
        $this->assertSame('products/gebyok-1.jpg', $found['cover_path'], 'The card needs the cover path.');
        $this->assertStringStartsWith('Sanggar', (string) $found['shop_name'], 'A card must name its sanggar.');
        $this->assertNotEmpty($found['shop_slug']);
    }

    public function testSearchExcludesDraftsAndUnrequestedSellers(): void
    {
        [$sellerId] = $this->makeSeller('a');
        $categoryId = $this->makeCategory('Cat');

        [, $draft] = $this->makeProduct($sellerId, $categoryId, [
            'name'   => 'Naskah Belum Terbit',
            'status' => 'draft',
        ]);

        [, $published] = $this->makeProduct($sellerId, $categoryId, [
            'name'   => 'Naskah Terbit',
            'status' => 'published',
        ]);

        [$otherSeller] = $this->makeSeller('b');
        [, $otherProduct] = $this->makeProduct($otherSeller, $categoryId, [
            'name'   => 'Naskah Terbit Punya Orang Lain',
            'status' => 'published',
        ]);

        $catalog = new CatalogService();

        $all = array_map(
            static fn (array $row): int => (int) $row['id'],
            $catalog->search(['q' => 'Naskah'])['products']
        );

        $this->assertContains($published, $all);
        $this->assertNotContains($draft, $all, 'A draft must never reach the public catalog.');

        // The storefront filter is what narrows results to one sanggar.
        $sellerSlug = $catalog->storefrontBySlug(
            (string) db_connect()->table('seller_profiles')->where('user_id', $sellerId)->get()->getRowArray()['slug']
        );

        $mine = array_map(
            static fn (array $row): int => (int) $row['id'],
            $catalog->search(['q' => 'Naskah', 'seller' => $sellerSlug['profile']['slug']])['products']
        );

        $this->assertContains($published, $mine);
        $this->assertNotContains($otherProduct, $mine, 'Seller filtering must be honoured.');
    }

    public function testSearchRespectsCategoryAndPriceRange(): void
    {
        [$sellerId] = $this->makeSeller('a');
        $categoryId = $this->makeCategory('Kalpataru');

        [, $cheap] = $this->makeProduct($sellerId, $categoryId, [
            'name'   => 'Kalpataru Murah',
            'price'  => 50000,
            'status' => 'published',
        ]);

        [, $pricey] = $this->makeProduct($sellerId, $categoryId, [
            'name'   => 'Kalpataru Mahal',
            'price'  => 2500000,
            'status' => 'published',
        ]);

        $slug = db_connect()->table('product_categories')->where('id', $categoryId)->get()->getRowArray()['slug'];

        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            (new CatalogService())->search([
                'category'  => $slug,
                'min_price' => 1000000,
                'max_price' => 3000000,
            ])['products']
        );

        $this->assertContains($pricey, $ids);
        $this->assertNotContains($cheap, $ids);
    }

    public function testStorefrontBySlugReturnsEverythingThePageRenders(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, ['status' => 'published']);

        $this->insertFixture('seller_facilities', [
            'seller_id' => $sellerId,
            'facility'  => 'ruang_pelatihan',
            'label'     => 'Ruang pelatihan',
            'position'  => 0,
        ]);

        $this->insertFixture('seller_story_sections', [
            'seller_id'  => $sellerId,
            'title'      => 'Sejarah sanggar',
            'subtitle'   => 'Sejak 1998',
            'body'       => 'Berawal dari satu bengkel.',
            'position'   => 0,
            'is_active'  => 1,
        ]);

        $store = (new CatalogService())->storefrontBySlug(
            (string) db_connect()->table('seller_profiles')->where('user_id', $sellerId)->get()->getRowArray()['slug']
        );

        $this->assertNotNull($store);
        $this->assertSame($sellerId, (int) $store['profile']['user_id']);
        $this->assertNotSame([], $store['products']);
        $this->assertNotSame([], $store['facilities']);
        $this->assertNotSame([], $store['stories']);
        $this->assertArrayHasKey('is_open', $store);
        $this->assertSame($productId, (int) $store['products'][0]['id']);
        $this->assertArrayHasKey('cover_path', $store['products'][0]);
    }

    public function testStorefrontOfUnknownSlugIsNull(): void
    {
        $this->assertNull((new CatalogService())->storefrontBySlug('tidak-ada-sangkar-ini'));
    }

    public function testSellerDashboardAggregatesWithoutSqlErrors(): void
    {
        [$sellerId] = $this->makeSeller('mitra');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'stock'                => 1,
            'low_stock_threshold'  => 2,
        ]);

        [, $orderId] = $this->makeOrder($customerId, $sellerId, $productId, [
            'status'         => 'shipped',
            'ship_regency'   => 'Magelang',
            'shipped_at'     => $this->fixtureNow(),
        ]);

        $dashboard = (new CatalogService())->sellerDashboard($sellerId);

        $this->assertArrayHasKey('revenue_30d', $dashboard);
        $this->assertGreaterThan(0, $dashboard['revenue_30d']);
        $this->assertSame(1, count($dashboard['orders']['all']));
        $this->assertSame(1, count($dashboard['orders']['in_transit']));
        $this->assertNotSame([], $dashboard['geography'], 'Delivered geography should be grouped.');
        $this->assertNotSame([], $dashboard['low_stock'], 'Stock at the threshold must be listed.');
        $this->assertSame($orderId, (int) $dashboard['orders']['all'][0]['id']);
    }

    public function testLowStockIgnoresMadeToOrderProducts(): void
    {
        [$sellerId] = $this->makeSeller('mitra');
        $categoryId = $this->makeCategory('Cat');

        $this->makeProduct($sellerId, $categoryId, [
            'made_to_order'       => 1,
            'stock'               => 0,
            'low_stock_threshold' => 5,
        ]);

        $this->assertSame([], (new CatalogService())->sellerDashboard($sellerId)['low_stock']);
    }

    public function testCartLinesCarryPriceStockAndCover(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price'  => 275000,
            'stock'  => 4,
            'status' => 'published',
        ]);

        $this->insertFixture('product_images', [
            'product_id' => $productId,
            'file_path'  => 'products/keranjang.jpg',
            'is_primary' => 1,
            'created_at' => $this->fixtureNow(),
        ]);

        $cart = new CartService();

        $cart->add($customerId, $productId, null, 2);

        $view = $cart->viewFor($customerId);

        // The badge counts units, not lines.
        $this->assertSame(2, $view['item_count']);
        $this->assertSame(550000, $view['subtotal']);
        $this->assertCount(1, $view['groups'], 'One sanggar means one checkout group.');
        $this->assertCount(1, $view['groups'][0]['lines']);

        $line = $view['groups'][0]['lines'][0];

        $this->assertSame('products/keranjang.jpg', $line['cover_path']);
        $this->assertSame(4, $line['stock']);
        $this->assertSame(550000, (int) $line['line_total']);
        $this->assertSame($sellerId, (int) $line['seller_id']);
    }

    public function testHeaderBadgeDoesNotCreateACart(): void
    {
        $customerId = $this->makeUser('penyimak');

        // A signed-in account that has never shopped (a seller, say) has no cart
        $this->assertSame(0, (new CartService())->badgeCount($customerId), 'No cart means a zero badge, not a new row.');
        $this->assertCount(0, $this->db->table('carts')->where('customer_id', $customerId)->get()->getResultArray());

        // Adding something still creates the cart, and the badge then counts it.
        [$sellerId]   = $this->makeSeller('sanggar');
        $categoryId   = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price'  => 100000,
            'stock'  => 5,
            'status' => 'published',
        ]);

        (new CartService())->add($customerId, $productId, null, 2);

        $this->assertCount(1, $this->db->table('carts')->where('customer_id', $customerId)->get()->getResultArray());
        $this->assertSame(2, (new CartService())->badgeCount($customerId));
    }

    public function testCheckoutLinesSnapshotTheCoverForTheOrderItem(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price'  => 120000,
            'stock'  => 3,
            'status' => 'published',
        ]);

        $this->insertFixture('product_images', [
            'product_id' => $productId,
            'file_path'  => 'products/snapshot.jpg',
            'is_primary' => 1,
            'created_at' => $this->fixtureNow(),
        ]);

        $cart = new CartService();
        $cart->add($customerId, $productId, null, 1);

        $lines = $cart->repriceForCheckout($customerId)['lines'];

        $this->assertCount(1, $lines);
        $this->assertSame('products/snapshot.jpg', $lines[0]['cover_path']);
    }

    public function testCartRepricesAgainstTheLivePriceNotTheSnapshot(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price'  => 100000,
            'stock'  => 5,
            'status' => 'published',
        ]);

        $cart = new CartService();
        $cart->add($customerId, $productId, null, 2);

        // The seller raises the price after the line was added.
        db_connect()->table('products')->where('id', $productId)->update(['price' => 150000]);

        $lines = $cart->repriceForCheckout($customerId)['lines'];

        $this->assertSame(150000, (int) $lines[0]['unit_price']);
        $this->assertSame(300000, (int) $lines[0]['subtotal']);
    }

    public function testCartClampsQuantityToAvailableStock(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price' => 90000,
            'stock' => 5,
        ]);

        $cart = new CartService();
        $cart->add($customerId, $productId, null, 4);

        db_connect()->table('products')->where('id', $productId)->update(['stock' => 2]);

        $result = $cart->repriceForCheckout($customerId);

        $this->assertSame(2, (int) $result['lines'][0]['quantity']);
        $this->assertSame(180000, (int) $result['subtotal']);
    }

    public function testMadeToOrderProductHasNoQuantityCeiling(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'price'         => 800000,
            'stock'         => 0,
            'made_to_order' => 1,
        ]);

        $cart = new CartService();
        $cart->add($customerId, $productId, null, 3);

        $result = $cart->repriceForCheckout($customerId);

        $this->assertSame(3, (int) $result['lines'][0]['quantity']);
        $this->assertSame(2400000, (int) $result['subtotal']);
    }

    public function testCartDropsAProductThatWasTakenOffSale(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'name'  => 'Gebyok Tutup Lawang',
            'stock' => 3,
        ]);

        $cart = new CartService();
        $cart->add($customerId, $productId, null, 1);

        db_connect()->table('products')->where('id', $productId)->update([
            'status'    => 'draft',
            'is_active' => 0,
        ]);

        $result = $cart->repriceForCheckout($customerId);

        $this->assertSame([], $result['lines']);
        $this->assertSame(['Gebyok Tutup Lawang'], $result['dropped']);
    }

    public function testProfileDashboardAggregatesLifetimeSpending(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId);

        // More than the ten most recent the customer page used to load, so a
        // lifetime figure cannot be hiding behind a recent-orders limit.
        for ($i = 0; $i < 12; $i++) {
            $this->makeOrder($customerId, $sellerId, $productId, [
                'status'    => 'completed',
                'subtotal'  => 10000 + $i,
                'grand_total' => 10000 + $i,
            ]);
        }

        $expected = 0;

        for ($i = 0; $i < 12; $i++) {
            $expected += 10000 + $i;
        }

        $profile = (new ProfileService())->dashboardFor($customerId);

        $this->assertSame($expected, $profile['contribution_total']);
        $this->assertSame(12, $profile['orders_total']);
        $this->assertCount(10, $profile['recent_orders'], 'The recent list is capped, the totals are not.');
    }

    public function testOrderHistoryRejectsAnUnknownStatusBucket(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId);

        $this->makeOrder($customerId, $sellerId, $productId, ['status' => 'completed']);
        $this->makeOrder($customerId, $sellerId, $productId, ['status' => 'cancelled']);

        $orders = new OrderModel();

        $this->assertCount(2, $orders->forCustomerHistory($customerId));
        $this->assertCount(1, $orders->forCustomerHistory($customerId, 50, 0, ['completed']));

        // A bucket that maps to no real status selects nothing rather than
        // falling back to every order.
        $this->assertCount(0, $orders->forCustomerHistory($customerId, 50, 0, ['__no_such_status__']));

        $counts = $orders->statusCountsForCustomer($customerId, OrderService::CUSTOMER_BUCKETS);

        $this->assertSame(2, $counts['all']);
        $this->assertSame(1, $counts['done']);
        $this->assertSame(1, $counts['cancelled']);
        $this->assertSame(0, $counts['unpaid']);
    }

    public function testCustomerOrderCountIgnoresTheRecentListLimit(): void
    {
        [$sellerId] = $this->makeSeller('sanggar');
        $customerId = $this->makeUser('pembeli');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerId, $categoryId);

        for ($i = 0; $i < 11; $i++) {
            $this->makeOrder($customerId, $sellerId, $productId);
        }

        $orders = new OrderModel();

        $this->assertSame(11, $orders->countForCustomer($customerId));
        $this->assertCount(10, $orders->forCustomerHistory($customerId, 10));
        $this->assertSame(11, (new ProfileService())->dashboardFor($customerId)['orders_total']);
    }

    public function testShopEditorReturnsOnlyThisSellersContent(): void
    {
        [$sellerId] = $this->makeSeller('saya');
        [$otherId] = $this->makeSeller('lain');

        $this->insertFixture('seller_facilities', [
            'seller_id' => $sellerId,
            'facility'  => 'kelas',
            'label'     => 'Kelas mengaji',
            'position'  => 0,
        ]);

        $this->insertFixture('seller_facilities', [
            'seller_id' => $otherId,
            'facility'  => 'parkir',
            'label'     => 'Parkir motor',
            'position'  => 0,
        ]);

        $shop = (new ShopService())->editorFor($sellerId);

        $this->assertNotNull($shop);
        $this->assertCount(1, $shop['facilities']);
        $this->assertSame('Kelas mengaji', $shop['facilities'][0]['label']);
        $this->assertNotSame([], $shop['facility_choices']);
    }

    public function testSellerCannotEditAnotherSellersProduct(): void
    {
        [$sellerA] = $this->makeSeller('a');
        $categoryId = $this->makeCategory('Cat');
        [, $productId] = $this->makeProduct($sellerA, $categoryId);

        $service = new \App\Services\ProductService();

        $this->assertNull($service->findForEdit($productId, 999999));

        $result = $service->update($productId, 999999, ['name' => 'Dibajak', 'price' => 1]);
        $this->assertFalse($result['ok']);

        $row = db_connect()->table('products')->where('id', $productId)->get()->getRowArray();
        $this->assertNotSame('Dibajak', $row['name']);
        $this->assertSame(450000, (int) $row['price']);
    }

    public function testProductStatusConstantMatchesTheColumnDefault(): void
    {
        $this->assertSame('published', \App\Models\ProductModel::STATUS_PUBLISHED);
    }
}
