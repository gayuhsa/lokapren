<?php

declare(strict_types=1);

namespace Tests\feature;

use App\Database\Seeds\DatabaseSeeder;
use App\Database\Seeds\UserSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Seeding is the only way anyone gets demo accounts, so the promise it makes
 * is that after a run the catalogue is populated and the documented demo
 * credentials actually authenticate. This pins both, and pins that running it
 * twice changes nothing.
 *
 * @internal
 */
final class SeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    protected function tearDown(): void
    {
        // Leaving a session behind would make the next test's GET /login
        // redirect instead of rendering the form.
        auth()->logout();

        parent::tearDown();
    }

    private function seed(): void
    {
        $seeder = new DatabaseSeeder(config('Database'), $this->db);

        $seeder->setSilent(true);
        $seeder->run();
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return [
            'categories'       => $this->countRows('categories'),
            'stores'           => $this->countRows('stores'),
            'products'         => $this->countRows('products'),
            'product_variants' => $this->countRows('product_variants'),
            'product_images'   => $this->countRows('product_images'),
        ];
    }

    private function countRows(string $table): int
    {
        return (int) $this->db->table($table)->countAllResults();
    }

    public function testSeedingPopulatesTheCatalogue(): void
    {
        $this->seed();

        $counts = $this->counts();

        $this->assertSame(5, $counts['categories']);
        $this->assertSame(4, $counts['stores']);
        $this->assertSame(8, $counts['products']);
        $this->assertSame(14, $counts['product_variants']);
        $this->assertSame(14, $counts['product_images']);
    }

    public function testSeedingTwiceChangesNothing(): void
    {
        $this->seed();
        $before = $this->counts();

        $this->seed();
        $after = $this->counts();

        $this->assertSame($before, $after, 'seeding must be idempotent');
    }

    public function testSeedingDoesNotDuplicateStoresOnReRun(): void
    {
        $this->seed();

        $codes = $this->db
            ->table('stores')
            ->select('store_code')
            ->orderBy('store_code', 'ASC')
            ->get()
            ->getResultArray();

        $this->assertCount(4, $codes, 'a second run must not add a second copy of each store');
    }

    public function testEveryProductBelongsToAStoreAndHasAPrice(): void
    {
        $this->seed();

        $products = $this->db->table('products')->get()->getResultArray();

        $this->assertNotEmpty($products);

        foreach ($products as $product) {
            $store = $this->db->table('stores')->where('id', $product['store_id'])->get()->getRowArray();

            $this->assertNotNull($store, "product {$product['slug']} must point at a real store");

            $variants = $this->db
                ->table('product_variants')
                ->where('product_id', $product['id'])
                ->get()
                ->getResultArray();

            $this->assertNotEmpty($variants, "product {$product['slug']} must have at least one variant");

            foreach ($variants as $variant) {
                $this->assertGreaterThan(
                    0,
                    (int) $variant['price'],
                    "variant {$variant['sku']} must be priced in whole rupiah",
                );
            }
        }
    }

    public function testEverySeededProductImageExistsOnDisk(): void
    {
        $this->seed();

        $urls = $this->db->table('product_images')->select('url')->get()->getResultArray();

        $this->assertNotEmpty($urls);

        foreach ($urls as $url) {
            $this->assertFileExists(
                FCPATH . $url['url'],
                "seeded image {$url['url']} is referenced but missing, so the catalogue renders broken",
            );
        }
    }

    public function testTheDemoSellerCanAuthenticate(): void
    {
        $this->seed();

        $result = auth()->attempt([
            'email'    => 'klipoh@lokapren.test',
            'password' => UserSeeder::DEMO_PASSWORD,
        ]);

        $this->assertTrue($result->isOK(), 'the documented demo seller must be able to sign in');
    }

    public function testTheDemoSellerCanSignInThroughTheLoginForm(): void
    {
        $this->seed();

        // CSRF is enabled globally, so the form must be primed with a real
        // token the way a browser would.
        $page   = $this->get('/login');
        $body   = $page->getBody();
        $csrfKey = config('Security')->tokenName;

        $this->assertSame(
            1,
            preg_match('/name="' . preg_quote($csrfKey, '/') . '" value="([^"]+)"/', $body, $matches),
            'the login form must carry a CSRF token',
        );

        $result = $this->withBodyFormat('urlencoded')
            ->post('/login', [
                $csrfKey => $matches[1],
                'email'  => 'klipoh@lokapren.test',
                'password' => UserSeeder::DEMO_PASSWORD,
            ]);

        $result->assertRedirect();

        $this->assertNotNull(auth()->user(), 'signing in through the form must produce a session');
        $this->assertTrue(
            auth()->user()->inGroup('seller'),
            'the demo seller must land in the seller group',
        );
    }

    public function testTheDemoAdminAndCustomerCanAuthenticate(): void
    {
        $this->seed();

        foreach (['admin@lokapren.test' => 'admin', 'andi@lokapren.test' => 'customer'] as $email => $group) {
            $result = auth()->attempt([
                'email'    => $email,
                'password' => UserSeeder::DEMO_PASSWORD,
            ]);

            $this->assertTrue($result->isOK(), "{$email} must be able to sign in");
            $this->assertTrue(auth()->user()->inGroup($group), "{$email} must be in the {$group} group");

            auth()->logout();
        }
    }

    public function testSeedingRepairsAnAccountThatWasLeftInactive(): void
    {
        $this->seed();

        // Simulate the state the demo rows were in before the seeder existed:
        // present but deactivated.
        $this->db->table('users')
            ->where('username', 'klipoh@lokapren.test')
            ->update(['active' => 0]);

        $this->seed();

        $user = $this->db->table('users')
            ->where('username', 'klipoh@lokapren.test')
            ->get()
            ->getRowArray();

        $this->assertSame(1, (int) $user['active'], 're-running must reactivate a demo account');

        $result = auth()->attempt([
            'email'    => 'klipoh@lokapren.test',
            'password' => UserSeeder::DEMO_PASSWORD,
        ]);

        $this->assertTrue($result->isOK());
    }
}