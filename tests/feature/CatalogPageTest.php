<?php

declare(strict_types=1);

namespace Tests\feature;

use App\Services\CatalogQuery;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;

/**
 * The catalogue reads from the database, so these pin the rules that must hold
 * no matter what is seeded: only active products on verified stores are
 * visible, filters and sorts happen in SQL, and prices stay whole rupiah.
 *
 * @internal
 */
final class CatalogPageTest extends CIUnitTestCase
{
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    private function catalog(): CatalogQuery
    {
        return new CatalogQuery($this->db);
    }

    public function testTheCataloguePageListsSeededProducts(): void
    {
        $seller = $this->createSeller('katalog.seller');
        $store  = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Plakat Uji', 'slug' => 'plakat-uji']);
        $this->makeVariant($product, ['price' => 340000, 'stock' => 4]);

        $result = $this->get('/marketplace');

        $result->assertOK();
        $result->assertSee('Plakat Uji');
        $result->assertSee('Rp340.000');
    }

    public function testTheCatalogueExcludesDraftProducts(): void
    {
        $seller  = $this->createSeller('draft.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Penggambar Uji', 'status' => 'draft']);
        $this->makeVariant($product);

        $this->get('/marketplace')->assertDontSee('Penggambar Uji');
    }

    public function testTheCatalogueExcludesProductsOnUnverifiedStores(): void
    {
        $seller  = $this->createSeller('pending.seller');
        $store   = $this->makeStore($seller, ['verification_status' => 'pending']);
        $product = $this->makeProduct($store, ['name' => 'Karya Menunggu Verifikasi']);
        $this->makeVariant($product);

        $this->get('/marketplace')->assertDontSee('Karya Menunggu Verifikasi');
    }

    public function testTheCatalogueExcludesProductsOnInactiveStores(): void
    {
        $seller  = $this->createSeller('closed.seller');
        $store   = $this->makeStore($seller, ['is_active' => false]);
        $product = $this->makeProduct($store, ['name' => 'Karya Toko Tutup']);
        $this->makeVariant($product);

        $this->get('/marketplace')->assertDontSee('Karya Toko Tutup');
    }

    public function testTheCatalogueFiltersByCategory(): void
    {
        $seller = $this->createSeller('kategori.seller');
        $store  = $this->makeStore($seller);

        $batik = $this->makeCategory(['name' => 'Batik Tulis', 'slug' => 'batik-tulis']);
        $kayu  = $this->makeCategory(['name' => 'Ukiran Kayu', 'slug' => 'ukiran-kayu']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Kain Batik', 'category_id' => $batik['id']]));
        $this->makeVariant($this->makeProduct($store, ['name' => 'Cangkir Jati', 'category_id' => $kayu['id']]));

        $result = $this->get('/marketplace?category=batik-tulis');

        $result->assertOK();
        $result->assertSee('Kain Batik');
        $result->assertDontSee('Cangkir Jati');
    }

    public function testTheCategoryHeadingNamesTheChosenCategory(): void
    {
        $seller = $this->createSeller('judul.seller');
        $store  = $this->makeStore($seller);
        $batik  = $this->makeCategory(['name' => 'Batik Tulis', 'slug' => 'batik-tulis']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Kain Batik', 'category_id' => $batik['id']]));

        // The heading has to say which category is being browsed, not just
        // repeat the word "Kategori".
        $this->get('/marketplace?category=batik-tulis')->assertSee('Kategori: Batik Tulis');
    }

    public function testAnUnknownCategoryIsIgnoredRatherThanShowingEverything(): void
    {
        $seller = $this->createSeller('unknown.seller');
        $store  = $this->makeStore($seller);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Terlihat']));

        // The category does not exist, so the filter is dropped and the
        // unfiltered set renders rather than a misleading empty result.
        $this->get('/marketplace?category=tidak-ada')->assertSee('Karya Terlihat');
    }

    public function testTheCatalogueFiltersBySearchTerm(): void
    {
        $seller = $this->createSeller('cari.seller');
        $store  = $this->makeStore($seller);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Anyaman Bambu', 'description' => 'Keranjang anyaman']));
        $this->makeVariant($this->makeProduct($store, ['name' => 'Cangkir Jati', 'description' => 'Minuman kayu']));

        $result = $this->get('/marketplace?q=anyaman');

        $result->assertSee('Anyaman Bambu');
        $result->assertDontSee('Cangkir Jati');
    }

    public function testTheCatalogueFiltersByPriceRange(): void
    {
        $seller = $this->createSeller('harga.seller');
        $store  = $this->makeStore($seller);

        $murah = $this->makeProduct($store, ['name' => 'Murah', 'slug' => 'murah']);
        $this->makeVariant($murah, ['price' => 88000]);

        $mahal = $this->makeProduct($store, ['name' => 'Mahal', 'slug' => 'mahal']);
        $this->makeVariant($mahal, ['price' => 980000]);

        $page = $this->get('/marketplace?max=100000');
        $page->assertSee('Murah');
        $page->assertDontSee('Mahal');
        $page = $this->get('/marketplace?min=500000');
        $page->assertSee('Mahal');
        $page->assertDontSee('Murah');
    }

    public function testAPricedRangeMatchesWhenAnyVariantFallsInsideIt(): void
    {
        $seller = $this->createSeller('varian.seller');
        $store  = $this->makeStore($seller);

        $plakat = $this->makeProduct($store, ['name' => 'Plakat', 'slug' => 'plakat']);
        $this->makeVariant($plakat, ['price' => 165000]);
        $this->makeVariant($plakat, ['price' => 820000]);

        // 820.000 is above the ceiling but 165.000 is inside it, so the product
        // still belongs in the result set.
        $this->get('/marketplace?max=200000')->assertSee('Plakat');
    }

    public function testTheCatalogueSortsByPriceAscending(): void
    {
        $seller = $this->createSeller('urut.seller');
        $store  = $this->makeStore($seller);

        $a = $this->makeProduct($store, ['name' => 'Termurah', 'slug' => 'termurah']);
        $this->makeVariant($a, ['price' => 50000]);

        $b = $this->makeProduct($store, ['name' => 'Termahal', 'slug' => 'termahal']);
        $this->makeVariant($b, ['price' => 900000]);

        $names = array_column($this->catalog()->paginate(['sort' => 'harga_asc'], 12)['products'], 'name');

        $this->assertSame(['Termurah', 'Termahal'], $names);
    }

    public function testAnUnsupportedSortKeyFallsBackToTheDefault(): void
    {
        $seller = $this->createSeller('inject.seller');
        $store  = $this->makeStore($seller);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Ada']));

        // A crafted sort key must not be able to reach the ORDER BY clause.
        $this->assertFalse(CatalogQuery::isSortable('harga_asc; DROP TABLE products'));
        $this->get('/marketplace?sort=' . urlencode('harga_asc; DROP TABLE products'))->assertOK();
    }

    public function testProductsWithoutAStockedVariantAreMarkedSoldOut(): void
    {
        $seller = $this->makeStore($this->createSeller('habis.seller'));
        $habis  = $this->makeProduct($seller, ['name' => 'Relik Kolektor', 'slug' => 'relik-kolektor']);
        $this->makeVariant($habis, ['price' => 820000, 'stock' => 0]);

        $this->get('/marketplace')->assertSee('Stok habis');
    }

    public function testTheCategoryListOnlyCountsVisibleProducts(): void
    {
        $seller = $this->createSeller('hitung.seller');
        $store  = $this->makeStore($seller);
        $batik  = $this->makeCategory(['name' => 'Batik', 'slug' => 'batik']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Batik Aktif', 'category_id' => $batik['id']]));
        $this->makeVariant($this->makeProduct($store, ['name' => 'Batik Draft', 'category_id' => $batik['id'], 'status' => 'draft']));

        foreach ($this->catalog()->categories() as $category) {
            if ($category['slug'] === 'batik') {
                $this->assertSame(1, (int) $category['product_count']);

                return;
            }
        }

        $this->fail('the batik category should appear in the filter list');
    }

    public function testPriceBoundsSpanOnlyVisibleProducts(): void
    {
        $seller = $this->makeStore($this->createSeller('batas.seller'));
        $visible = $this->makeProduct($seller, ['name' => 'Terlihat']);
        $this->makeVariant($visible, ['price' => 88000]);

        $hidden = $this->makeProduct($seller, ['name' => 'Disembunyikan', 'status' => 'draft']);
        $this->makeVariant($hidden, ['price' => 9000000]);

        $bounds = $this->catalog()->priceBounds();

        $this->assertSame(88000, $bounds['min']);
        $this->assertSame(88000, $bounds['max']);
    }

    public function testTheCataloguePaginates(): void
    {
        $seller = $this->createSeller('halaman.seller');
        $store  = $this->makeStore($seller);

        for ($i = 1; $i <= 15; $i++) {
            $this->makeVariant($this->makeProduct($store, ['name' => sprintf("Karya %02d", $i)]));
        }

        $result = $this->catalog()->paginate([], 12, 1);

        $this->assertSame(15, $result['total']);
        $this->assertSame(2, $result['pages']);
        $this->assertCount(12, $result['products']);

        $second = $this->catalog()->paginate([], 12, 2);
        $this->assertCount(3, $second['products']);
    }

    public function testTheProductPageRendersFromTheDatabase(): void
    {
        $seller  = $this->createSeller('detail.seller');
        $store   = $this->makeStore($seller, ['name' => 'Omah Kriya Uji']);
        $product = $this->makeProduct($store, [
            'name'        => 'Plakat Relief Uji',
            'slug'        => 'plakat-relief-uji',
            'description' => 'Plakat relief dipahat tangan.',
            'attributes'  => '{"material":"Kayu Jati","finishing":"Minyak Linseed"}',
        ]);
        $this->makeVariant($product, ['name' => 'Ukuran L', 'price' => 450000, 'stock' => 3]);

        $result = $this->get('/product/plakat-relief-uji');

        $result->assertOK();
        $result->assertSee('Plakat Relief Uji');
        $result->assertSee('Omah Kriya Uji');
        $result->assertSee('Rp450.000');
        $result->assertSee('Ukuran L');
        $result->assertSee('Kayu Jati');
    }

    public function testTheProductPage404sForAnUnknownSlug(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('/product/tidak-ada');
    }

    public function testTheProductPage404sForADraftProduct(): void
    {
        $seller  = $this->createSeller('rahasia.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Rancangan', 'slug' => 'rancangan', 'status' => 'draft']);
        $this->makeVariant($product);

        $this->expectException(PageNotFoundException::class);
        $this->get('/product/rancangan');
    }

    public function testTheProductPageIsReachableByNumericIdToo(): void
    {
        $seller  = $this->createSeller('lama.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Karya Lama', 'slug' => 'karya-lama']);
        $this->makeVariant($product);

        $this->get('/product/' . $product['id'])->assertOK();
    }

    public function testTheProductPageDefaultsToTheCheapestBuyableVariant(): void
    {
        $seller  = $this->createSeller('default.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Pilih Varian', 'slug' => 'pilih-varian']);

        // Cheapest is sold out, so the default must skip to the next buyable one.
        $this->makeVariant($product, ['name' => 'Solit', 'price' => 165000, 'stock' => 0]);
        $this->makeVariant($product, ['name' => 'Jual', 'price' => 340000, 'stock' => 4]);

        $detail = $this->catalog()->detail('pilih-varian');

        $this->assertSame('Jual', $detail['default_variant']['name']);
        $this->assertSame(340000, $detail['price']);
        $this->assertTrue($detail['in_stock']);
    }

    public function testTheProductPageWarnsWhenEverythingIsSoldOut(): void
    {
        $seller  = $this->createSeller('soldout.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Semua Habis', 'slug' => 'semua-habis']);
        $this->makeVariant($product, ['price' => 820000, 'stock' => 0]);

        $detail = $this->catalog()->detail('semua-habis');

        $this->assertFalse($detail['in_stock']);

        $this->get('/product/semua-habis')->assertSee('Stok sedang habis');
    }

    public function testTheProductPageShowsAReviewBreakdown(): void
    {
        $seller  = $this->createSeller('ulasan.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, ['name' => 'Berulasan', 'slug' => 'berulasan']);
        $this->makeVariant($product);

        $buyer = $this->createCustomer('pembeli.ulasan');
        $now   = date('Y-m-d H:i:s');

        foreach ([5, 5, 4] as $index => $stars) {
            // product_reviews.order_id is a real foreign key, so each review
            // needs an order that actually exists.
            $this->db->table('orders')->insert([
                'order_code'     => sprintf('LKP-2026-%04d', $index + 1),
                'customer_id'    => $buyer->id,
                'status'         => 'completed',
                'subtotal'       => 450000,
                'total'          => 450000,
                'recipient_name' => 'Dewi Indonesian',
                'recipient_phone' => '08123456789',
                'address_line'   => 'Jl. Magelang No. 1',
                'city'           => 'Magelang',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            $this->db->table('product_reviews')->insert([
                'product_id'  => $product['id'],
                'order_id'    => $this->db->insertID(),
                'customer_id' => $buyer->id,
                'rating'      => $stars,
                'comment'     => 'Karya bagus.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }

        $detail = $this->catalog()->detail('berulasan');

        $this->assertSame(3, $detail['rating']['count']);
        $this->assertSame(4.7, $detail['rating']['average']);
        $this->assertSame(2, $detail['rating']['breakdown'][5]);
        $this->assertSame(1, $detail['rating']['breakdown'][4]);

        $page = $this->get('/product/berulasan');
        $page->assertSee('Berulasan');
        $page->assertSee('5 Bintang');
    }

    public function testMalformedAttributesDoNotBreakTheProductPage(): void
    {
        $seller  = $this->createSeller('json.seller');
        $store   = $this->makeStore($seller);
        $product = $this->makeProduct($store, [
            'name'       => 'Atribut Rusak',
            'slug'       => 'atribut-rusak',
            'attributes' => '{bukan json',
        ]);
        $this->makeVariant($product);

        $detail = $this->catalog()->detail('atribut-rusak');

        $this->assertSame([], $detail['specs']);
        $this->get('/product/atribut-rusak')->assertOK();
    }

    public function testAnEmptyCatalogueRendersAnEmptyState(): void
    {
        $result = $this->get('/marketplace');

        $result->assertOK();
        $result->assertSee('Belum ada karya yang cocok');
    }

    public function testTheProductPageLinksToRelatedWork(): void
    {
        $seller = $this->createSeller('serupa.seller');
        $store  = $this->makeStore($seller);
        $batik  = $this->makeCategory(['slug' => 'batik']);

        $main = $this->makeProduct($store, ['name' => 'Karya Utama', 'slug' => 'karya-utama', 'category_id' => $batik['id']]);
        $this->makeVariant($main);

        $other = $this->makeProduct($store, ['name' => 'Karya Serupa', 'slug' => 'karya-serupa', 'category_id' => $batik['id']]);
        $this->makeVariant($other);

        $result = $this->get('/product/karya-utama');

        $result->assertSee('Karya Serupa');
        $this->assertStringNotContainsString(
            'Karya Utama</h3>',
            preg_replace('/\s+/', ' ', $result->getBody()),
            'the current product should not be listed as related work',
        );
    }
}