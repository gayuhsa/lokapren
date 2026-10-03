<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;

/**
 * The storefront is a public page about one seller, so these pin what it may
 * show: real aggregates only, the store's own works only, and nothing at all
 * for a store that is missing, unverified or closed.
 *
 * @internal
 */
final class StorefrontPageTest extends CIUnitTestCase
{
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    public function testTheStorefrontIsPubliclyReadable(): void
    {
        $seller = $this->createSeller('storefront.guest');
        $store  = $this->makeStore($seller, [
            'name'        => 'Omah Kriya Uji',
            'slug'        => 'omah-kriya-uji',
            'description' => 'Sentra ukir kayu Borobudur.',
        ]);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Relung Jati']));

        $result = $this->get('/store/omah-kriya-uji');

        $result->assertOK();
        $result->assertSee('Omah Kriya Uji');
        $result->assertSee('Sentra ukir kayu Borobudur.');
        $result->assertSee('Relung Jati');
    }

    public function testTheStorefrontHidesProductsOfOtherStores(): void
    {
        $seller = $this->createSeller('storefront.owner');
        $other  = $this->createSeller('storefront.stranger');

        $store = $this->makeStore($seller, ['name' => 'Toko Sendiri', 'slug' => 'toko-sendiri']);
        $rival = $this->makeStore($other, ['name' => 'Toko Tetangga', 'slug' => 'toko-tetangga']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Sendiri']));
        $this->makeVariant($this->makeProduct($rival, ['name' => 'Karya Tetangga']));

        $result = $this->get('/store/toko-sendiri');

        $result->assertSee('Karya Sendiri');
        $result->assertDontSee('Karya Tetangga');
    }

    public function testTheStorefront404sForAnUnknownStore(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('/store/toko-hantu');
    }

    public function testTheStorefront404sForAnInactiveStore(): void
    {
        $seller = $this->createSeller('storefront.closed');
        $store  = $this->makeStore($seller, ['name' => 'Toko Tutup', 'slug' => 'toko-tutup', 'is_active' => false]);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));

        $this->expectException(PageNotFoundException::class);
        $this->get('/store/toko-tutup');
    }

    public function testTheStorefront404sForAnUnverifiedStore(): void
    {
        $seller = $this->createSeller('storefront.pending');
        $store  = $this->makeStore($seller, ['slug' => 'toko-pending', 'verification_status' => 'pending']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Menunggu']));

        $this->expectException(PageNotFoundException::class);
        $this->get('/store/toko-pending');
    }

    public function testTheStorefrontReportsRealAggregatesInsteadOfDesignNumbers(): void
    {
        $seller = $this->createSeller('storefront.aggregate');
        $store  = $this->makeStore($seller, ['name' => 'Toko Angka', 'slug' => 'toko-angka']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Angka']), ['price' => 250000]);

        $result = $this->get('/store/toko-angka');

        $result->assertSee('Rp250.000');
        $result->assertSee('Belum ada ulasan');
        $result->assertDontSee('4.9');
        $result->assertDontSee('99%');
    }

    public function testTheStorefrontExplainsTheOpeningHours(): void
    {
        $seller = $this->createSeller('storefront.hours');
        $store  = $this->makeStore($seller, [
            'slug'          => 'toko-jam',
            'opening_hours' => "Senin - Sabtu\n08.00 - 17.00",
        ]);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Jam']));

        $result = $this->get('/store/toko-jam');

        $result->assertSee('Senin');
        $result->assertSee('Sabtu');
    }

    public function testTheStorefrontListsOnlyItsOwnCategories(): void
    {
        $seller = $this->createSeller('storefront.category');
        $store  = $this->makeStore($seller, ['slug' => 'toko-kategori']);

        $batik = $this->makeCategory(['name' => 'Batik Tulis', 'slug' => 'batik-tulis']);
        $kayu  = $this->makeCategory(['name' => 'Ukiran Kayu', 'slug' => 'ukiran-kayu']);

        $this->makeVariant($this->makeProduct($store, [
            'name'        => 'Kain Batik',
            'category_id' => $batik['id'],
        ]));
        $this->makeVariant($this->makeProduct($store, [
            'name'        => 'Pahatan Kayu',
            'category_id' => $kayu['id'],
        ]));

        $result = $this->get('/store/toko-kategori');

        $result->assertSee('Batik Tulis');
        $result->assertSee('Ukiran Kayu');
    }

    public function testTheStorefrontNarrowsToOneCategory(): void
    {
        $seller = $this->createSeller('storefront.filter');
        $store  = $this->makeStore($seller, ['slug' => 'toko-filter']);

        $batik = $this->makeCategory(['name' => 'Batik Tulis', 'slug' => 'batik-tulis']);
        $kayu  = $this->makeCategory(['name' => 'Ukiran Kayu', 'slug' => 'ukiran-kayu']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Kain Batik', 'category_id' => $batik['id']]));
        $this->makeVariant($this->makeProduct($store, ['name' => 'Pahatan Kayu', 'category_id' => $kayu['id']]));

        $result = $this->get('/store/toko-filter?kategori=ukiran-kayu');

        $result->assertSee('Pahatan Kayu');
        $result->assertDontSee('Kain Batik');
    }

    public function testTheStorefrontIgnoresACategoryFromAnotherStore(): void
    {
        $seller = $this->createSeller('storefront.alien');
        $other  = $this->createSeller('storefront.alien.other');

        $store = $this->makeStore($seller, ['slug' => 'toko-asing']);
        $rival = $this->makeStore($other, ['slug' => 'toko-asing-lain']);

        $pakai = $this->makeCategory(['name' => 'Tenun Pakai', 'slug' => 'tenun-pakai']);

        $this->makeVariant($this->makeProduct($store, ['name' => 'Kain Sendiri']));
        $this->makeVariant($this->makeProduct($rival, ['name' => 'Kain Tetangga', 'category_id' => $pakai['id']]));

        $result = $this->get('/store/toko-asing?kategori=tenun-pakai');

        $result->assertSee('Kain Sendiri');
        $result->assertDontSee('Kain Tetangga');
    }

    public function testTheStorefrontOffersAContactInsteadOfADeadContact(): void
    {
        $seller = $this->createSeller('storefront.contact');
        $store  = $this->makeStore($seller, ['slug' => 'toko-telepon', 'phone' => '0812-3456-7890']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya Telepon']));

        $result = $this->get('/store/toko-telepon');

        $result->assertSee('Kontak Perajin');
        $result->assertSee('tel:081234567890');
    }

    public function testTheStorefrontEscapesSellerSuppliedText(): void
    {
        $seller = $this->createSeller('storefront.xss');
        $store  = $this->makeStore($seller, [
            'name'        => 'Toko <script>alert(1)</script>',
            'slug'        => 'toko-xss',
            'description' => 'Cerita "<b>aman</b>" dari perajin.',
        ]);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya <i>Uji</i>']));

        $result = $this->get('/store/toko-xss');

        $result->assertSee('Cerita "&lt;b&gt;aman&lt;/b&gt;" dari perajin.');
        $result->assertDontSee('<script>alert(1)</script>');
        $result->assertDontSee('<i>Uji</i>');
    }
}