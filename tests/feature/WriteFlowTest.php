<?php

declare(strict_types=1);

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Database\MarketplaceFixtures;

/**
 * The write paths, posted for real.
 *
 * PageRenderTest only ever issues GET requests, and every bug this suite was
 * written for was a POST-only failure: CodeIgniter 3 validation rules (`min`,
 * `max`, `in`) that throw a 500 the moment a form is submitted, a publish step
 * that updates one column of a pair and leaves the other behind, a delete that
 * silently breaks the "exactly one default address" invariant. A redirected
 * 303 plus the resulting row (or its absence) is the assertion that matters:
 * the request must either do the right thing or refuse cleanly — never crash.
 *
 * @internal
 */
final class WriteFlowTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    /**
     * @var array{seller: int, category: int, customer: int}
     */
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ids = [
            'seller'   => $this->makeSeller('mitra-tulis')[0],
            'category' => $this->makeCategory('Kriya Tulis'),
            'customer' => $this->makeUser('pembeli-tulis'),
        ];
    }

    /**
     * Log in as one of the fixture users created in setUp().
     */
    private function actAs(string $who): void
    {
        $row = $this->db->table('users')->where('id', $this->ids[$who])->get()->getRowArray();

        $this->actingAs(new User($row));
    }

    public function testProductStoreAcceptsValidInputAndStartsAsDraft(): void
    {
        $this->actAs('seller');

        $response = $this->post('/seller/products', [
            'name'                => 'Vas Uji Coba',
            'category_id'         => (string) $this->ids['category'],
            'price'               => '99000',
            'stock'               => '2',
            'low_stock_threshold' => '1',
            'weight_gram'         => '400',
            'production_days'     => '7',
            'is_active'           => '1',
        ]);

        $response->assertRedirect();

        $row = $this->db->table('products')->where('name', 'Vas Uji Coba')->get()->getRowArray();
        $this->assertNotNull($row, 'Valid product input must persist a row');
        $this->assertSame('draft', $row['status'], 'A new product starts as a draft');
    }

    public function testProductStoreRejectsOutOfRangeInputWithoutA500(): void
    {
        $this->actAs('seller');

        $response = $this->post('/seller/products', [
            'name'      => 'Produk Ditolak',
            'price'     => '-5',
            'is_active' => 'maybe',
        ]);

        $response->assertRedirect();
        $this->assertNull(
            $this->db->table('products')->where('name', 'Produk Ditolak')->get()->getRowArray(),
            'Out-of-range input must be refused by validation, not persisted'
        );
    }

    public function testBlogPublishKeepsStatusAndIsPublishedInLockstep(): void
    {
        $postId = $this->insertFixture('blog_posts', [
            'seller_id'    => $this->ids['seller'],
            'slug'         => 'cerita-uji-tulis',
            'title'        => 'Cerita Uji Tulis',
            'body'         => 'Isi cerita.',
            'status'       => 'draft',
            'is_published' => 0,
            'created_at'   => $this->fixtureNow(),
            'updated_at'   => $this->fixtureNow(),
        ]);

        $this->actAs('seller');

        $this->post('/seller/articles/' . $postId, [
            'title'  => 'Cerita Uji Tulis',
            'body'   => 'Isi cerita.',
            'status' => 'published',
        ])->assertRedirect();

        $row = $this->db->table('blog_posts')->where('id', $postId)->get()->getRowArray();
        $this->assertSame('published', $row['status']);
        $this->assertSame(1, (int) $row['is_published'], 'Publishing must keep is_published in step with status');
        $this->assertNotNull($row['published_at']);
    }

    public function testDeletingTheDefaultAddressPromotesTheNextOne(): void
    {
        $shared = [
            'user_id'        => $this->ids['customer'],
            'recipient_name' => 'Budi Santoso',
            'recipient_phone'=> '08123456789',
            'address_line'   => 'Jl. Raya Magelang No. 10',
            'village'        => 'Klegen',
            'district'       => 'Sidoarjo',
            'regency'        => 'Magelang',
            'province'       => 'Jawa Tengah',
            'postal_code'    => '56524',
        ];

        $keep = $this->insertFixture('addresses', array_merge($shared, [
            'label'      => 'Rumah',
            'is_default' => 0,
        ]));
        $drop = $this->insertFixture('addresses', array_merge($shared, [
            'label'      => 'Kantor',
            'is_default' => 1,
        ]));

        $this->actAs('customer');

        $this->post('/account/addresses/' . $drop . '/delete')->assertRedirect();

        $row = $this->db->table('addresses')->where('id', $keep)->get()->getRowArray();
        $this->assertSame(1, (int) $row['is_default'], 'Deleting the default must promote another address');
    }

    public function testAddressStoreRejectsOutOfRangeCoordinates(): void
    {
        $this->actAs('customer');

        $this->post('/account/addresses', [
            'label'           => 'Koordinat Aneh',
            'recipient_name'  => 'Budi Santoso',
            'recipient_phone' => '08123456789',
            'address_line'    => 'Jl. di Lautan',
            'latitude'        => '999',
            'longitude'       => '-999',
        ])->assertRedirect();

        $this->assertNull(
            $this->db->table('addresses')->where('label', 'Koordinat Aneh')->get()->getRowArray(),
            'Out-of-range coordinates must be refused, not persisted'
        );
    }
}
