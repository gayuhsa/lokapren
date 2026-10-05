<?php

declare(strict_types=1);

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\MarketplaceFixtures;
use App\Services\CartService;

/**
 * Every page, rendered end to end against the real schema.
 *
 * The other suites call the service layer directly, so a broken view — a wrong
 * array key, a typo in a helper call, a `use` statement that does not parse —
 * slips through until a browser is opened. A page render is the only check that
 * exercises the controller, the query and the template together, so these tests
 * walk the public catalog, the storefront, the account area and the seller
 * dashboard and assert only that each one renders.
 *
 * @internal
 */
final class PageRenderTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    /**
     * A seeded marketplace shared by every test here: one sanggar with a
     * published product, a story section, a business hour and a published
     * article, plus one customer with an order.
     *
     * @var array<string, int>
     */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();

        [, $profileId] = $this->makeSeller('sanggar-render');
        $profile       = $this->db->table('seller_profiles')->where('id', $profileId)->get()->getRowArray();

        $sellerId   = (int) $profile['user_id'];
        $sellerSlug = (string) $profile['slug'];

        $categoryId    = $this->makeCategory('Gebyok');
        [, $productId] = $this->makeProduct($sellerId, $categoryId, [
            'name' => 'Gebyok Ukir Tulap',
        ]);

$this->insertFixture('seller_story_sections', [
            'seller_id'  => $sellerId,
            'title'      => 'Kayu dari pohon jati',
            'subtitle'   => 'Dijemur dua minggu sebelum diukir',
            'body'       => "Dipilih dari pohon jati yang tumbang dan sengaja dipesan.\n\nDijemur dua minggu sebelum diukir.",
            'position'   => 0,
            'is_active'  => 1,
        ]);

        $this->insertFixture('seller_business_hours', [
            'seller_id'   => $sellerId,
            'day_of_week' => (int) date('N'),
            'opens_at'    => '08:00:00',
            'closes_at'   => '17:00:00',
            'is_closed'   => 0,
        ]);

        $this->insertFixture('seller_facilities', [
            'seller_id' => $sellerId,
            'facility'  => 'galeri',
            'label'     => 'Galeri Karya',
            'position'  => 0,
        ]);

        $this->insertFixture('seller_quick_replies', [
            'seller_id' => $sellerId,
            'title'     => 'Halo',
            'body'      => 'Selamat datang di sanggar kami.',
            'is_active' => 1,
        ]);

        $categoryRowId = $this->insertFixture('blog_categories', [
            'parent_id' => null,
            'name'      => 'Proses',
            'slug'      => 'proses',
        ]);

        $postId = $this->insertFixture('blog_posts', [
            'seller_id'    => $sellerId,
            'category_id'  => $categoryRowId,
            'slug'         => 'cara-mengasapkan-gebyok',
            'title'        => 'Cara Mengasapkan Gebyok',
            'excerpt'      => 'Tanpa meratakan motif.',
            'body'         => "Pilih jati yang sudah kering, lalu biarkan satu tahun di gudang.\n\nKeringkan perlahan sebelum diukir.",
            'status'       => 'published',
            'is_published' => 1,
            'published_at' => $this->fixtureNow(),
            'created_at'   => $this->fixtureNow(),
            'updated_at'   => $this->fixtureNow(),
        ]);

        $customerId = $this->makeUser('pembeli');
        [, $orderId] = $this->makeOrder($customerId, $sellerId, $productId, [
            'status'               => 'in_production',
            'ship_recipient_name'  => 'Budi Santoso',
            'ship_recipient_phone' => '08123456789',
            'ship_address_line'    => 'Jl. Raya Magelang No. 10',
            'ship_village'         => 'Klegen',
            'ship_district'        => 'Sidoarjo',
            'ship_regency'         => 'Magelang',
        ]);

        $this->ids = [
            'seller'      => $sellerId,
            'customer'    => $customerId,
            'product'     => $productId,
            // Product detail is routed by slug, never by id.
            'product_slug' => (string) $this->db->table('products')->where('id', $productId)
                ->get()->getRowArray()['slug'],
            'order'       => $orderId,
            'post'        => $postId,
            'slug'        => $sellerSlug,
        ];
    }

    /**
     * Public pages a guest can reach.
     */
    public function testGuestPagesRender(): void
    {
        $pages = [
            '/',
            '/catalog',
            '/catalog?q=gebyok',
            '/location',
            '/articles',
            '/articles?category=proses',
            '/articles/' . $this->ids['post'],
            '/store/' . $this->ids['slug'],
            '/product/' . $this->ids['product_slug'],
            '/login',
            '/register',
        ];

        foreach ($pages as $page) {
            $this->assertPageRenders($page);
        }
    }

    /**
     * Fetch a page and report the URL when anything goes wrong.
     *
     * A render failure normally surfaces as a framework exception with no hint
     * of which URL was being visited, which makes a list-driven test
     * unreadable. Every failure is reported with the page that caused it.
     */
    private function assertPageRenders(string $page, int $expected = 200): void
    {
        try {
            $result = $this->get($page);
        } catch (\Throwable $exception) {
            $this->fail(sprintf(
                'GET %s threw %s at %s:%d: %s',
                $page,
                $exception::class,
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage()
            ));
        }

        $this->assertSame($expected, $result->response()->getStatusCode(), $page . ' should respond ' . $expected);

        // A redirect intentionally carries no page body.
        if ($expected < 300 || $expected >= 400) {
            $this->assertNotSame('', $result->response()->getBody(), $page . ' should have a body');
        }
    }

    public function testStorefrontAndCatalogShowTheProduct(): void
    {
        $catalog = (string) $this->get('/catalog?q=gebyok')->response()->getBody();

        $this->assertStringContainsString('Gebyok Ukir Tulap', $catalog);
    }

    public function testBlogIndexListsTheArticle(): void
    {
        $body = (string) $this->get('/articles')->response()->getBody();

        $this->assertStringContainsString('Cara Mengasapkan Gebyok', $body);
    }

    /**
     * Pages behind `login`, still only rendered as the customer.
     */
    public function testAccountPagesRenderForACustomer(): void
    {
        $customer = new User($this->db->table('users')->where('id', $this->ids['customer'])->get()->getRowArray());

        $pages = [
            '/account',
            '/account/addresses',
            '/account/addresses/create',
            '/orders',
            '/orders/' . $this->ids['order'],
            '/orders/' . $this->ids['order'] . '/tracking',
            '/chat',
            // The cart is behind Shield's `session` filter, so it only renders
            // for a signed-in customer.
            '/cart',
        ];

        foreach ($pages as $page) {
            $this->actingAs($customer);

            $this->assertPageRenders($page);
        }
    }

    /**
     * The seller area, which is where most of the hand-written templates live.
     */
    public function testSellerPagesRenderForASeller(): void
    {
        $seller = new User($this->db->table('users')->where('id', $this->ids['seller'])->get()->getRowArray());

        $pages = [
            '/seller',
            '/seller/shop',
            '/seller/products',
            '/seller/products/create',
            '/seller/products/' . $this->ids['product'] . '/edit',
            '/seller/variants/' . $this->ids['product'],
            '/seller/orders',
            '/seller/orders?status=to_ship',
            '/seller/orders/' . $this->ids['order'],
            '/seller/articles',
            '/seller/articles/create',
            '/seller/articles/' . $this->ids['post'] . '/edit',
        ];

        foreach ($pages as $page) {
            $this->actingAs($seller);

            $this->assertPageRenders($page);
        }
    }

    /**
     * The seller pages that are reachable without a profile row.
     */
    public function testSellerPagesRenderWithoutAStorefrontProfile(): void
    {
        $bare = $this->makeUser('tanpa-profil');
        // The role is what opens the `/seller` group; the storefront profile row
        // is deliberately absent so the "profile not complete" paths run.
        $this->addUserToGroup($bare, 'seller');

        $user = new User($this->db->table('users')->where('id', $bare)->get()->getRowArray());

        $this->actingAs($user);

        // The dashboard sends a profile-less seller to the shop editor instead
        // of rendering figures it cannot compute.
        $this->assertPageRenders('/seller', 302);
        $this->assertPageRenders('/seller/products');
        $this->assertPageRenders('/seller/orders');
        $this->assertPageRenders('/seller/articles');

        // The shop editor itself redirects back with an explanation rather than
        // rendering a form over null values.
        $this->assertPageRenders('/seller/shop', 302);
    }

    /**
     * `/media` must stream a file whose stored path has more than one segment
     * (`demo/products/...`), not just the first segment (`demo`). The capture
     * depends on `Routing::$multipleSegmentsOneParam`; a regression here turns
     * every storefront image into a 404 without any of the page tests noticing.
     */
    public function testMediaRouteStreamsAMultisegmentPath(): void
    {
        $relative = 'feature-tests/teh-panas.png';
        $absolute = WRITEPATH . 'uploads/' . $relative;

        $directory = dirname($absolute);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        // A 1x1 PNG, written by the test and removed afterwards.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        try {
            file_put_contents($absolute, $png);

            $result = $this->get('/media/' . $relative);

            $this->assertSame(200, $result->response()->getStatusCode(), 'A multisegment media path must stream, not 404.');
            $this->assertSame($png, $result->response()->getBody());

            // A missing file shaped the same way is still a 404 (surfaced as an
            // exception under the test harness).
            try {
                $this->get('/media/' . $relative . '.missing');
                $this->fail('A missing media file must 404.');

                return;
            } catch (PageNotFoundException) {
                // Expected.
            }
        } finally {
            @unlink($absolute);
            @rmdir($directory);
        }
    }

    /**
     * `/checkout` renders the review step for a customer who has a cart and an
     * address. This is where a regression stored `dropped` as an integer count
     * instead of a list and crashed the view with `foreach() ... int given` on
     * every healthy cart, without any of the numbered page tests noticing.
     */
    public function testCheckoutRendersForACustomerWithACartAndAddress(): void
    {
        $customerId = $this->makeUser('pembeli');

        $this->insertFixture('addresses', [
            'user_id'        => $customerId,
            'label'          => 'Rumah',
            'recipient_name' => 'Budi Santoso',
            'recipient_phone'=> '08123456789',
            'address_line'   => 'Jl. Raya Magelang No. 10',
            'village'        => 'Klegen',
            'district'       => 'Sidoarjo',
            'regency'        => 'Magelang',
            'province'       => 'Jawa Tengah',
            'postal_code'    => '56524',
            'is_default'     => 0,
        ]);

        (new CartService())->add($customerId, $this->ids['product'], null, 2);

        $customer = new User($this->db->table('users')->where('id', $customerId)->get()->getRowArray());
        $this->actingAs($customer);

        $this->assertPageRenders('/checkout');
    }

    /**
     * The same review step when part of the cart had to be dropped for being
     * taken off sale: the alert lists what disappeared, and the page still
     * renders the remaining item with its totals.
     */
    public function testCheckoutRendersWhenARepricedItemWasDropped(): void
    {
        $customerId = $this->makeUser('pembeli');

        $this->insertFixture('addresses', [
            'user_id'        => $customerId,
            'recipient_name' => 'Budi Santoso',
            'address_line'   => 'Jl. Raya Magelang No. 10',
            'regency'        => 'Magelang',
            'is_default'     => 0,
        ]);

        [$sellerId] = $this->makeSeller('sanggar');
        $categoryId = $this->makeCategory('Cat');
        [, $keptId] = $this->makeProduct($sellerId, $categoryId, ['name' => 'Gebyok Utuh']);
        [, $goneId] = $this->makeProduct($sellerId, $categoryId, ['name' => 'Gebyok Retak']);

        $cart = new CartService();
        $cart->add($customerId, $keptId, null, 1);
        $cart->add($customerId, $goneId, null, 1);

        $this->db->table('products')->where('id', $goneId)->update([
            'status'    => 'draft',
            'is_active' => 0,
        ]);

        $customer = new User($this->db->table('users')->where('id', $customerId)->get()->getRowArray());
        $this->actingAs($customer);

        $result = $this->get('/checkout');
        $this->assertSame(200, $result->response()->getStatusCode());

        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString('tidak tersedia lagi', $body);
        $this->assertStringContainsString('Gebyok Retak', $body);
        $this->assertStringContainsString('Gebyok Utuh', $body);
    }
}
