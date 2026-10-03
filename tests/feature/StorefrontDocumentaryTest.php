<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;

/**
 * The philosophy block leans on the store's published documentary, so these
 * pin the two cases that decide whether it renders at all: no post yet, and a
 * post that exists but points at a video nobody uploaded.
 *
 * @internal
 */
final class StorefrontDocumentaryTest extends CIUnitTestCase
{
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    private function post(array $store, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'store_id'  => $store['id'],
            'title'     => 'Proses Kriya',
            'slug'      => 'proses-kriya',
            'excerpt'   => 'Rangkaian proses Workshop.',
            'post_type' => 'documentary',
            'status'    => 'published',
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $this->db->table('store_posts')->insert($row);

        return $row;
    }

    public function testThePhilosophyBlockSurvivesAStoreWithoutADocumentary(): void
    {
        $seller = $this->createSeller('documentary.none');
        $store  = $this->makeStore($seller, ['name' => 'Toko Tanpa Dokumenter', 'slug' => 'toko-tanpa-dok']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));

        $result = $this->get('/store/toko-tanpa-dok');

        $result->assertOK();
        $result->assertSee('Jiwa di Balik Karya');
        $result->assertSee('assets/img/kriya/store/store-cover.svg');
        $result->assertDontSee('Dokumenter Perajin Mandiri');
    }

    public function testTheDocumentaryIsShownWhenPublished(): void
    {
        $seller = $this->createSeller('documentary.published');
        $store  = $this->makeStore($seller, ['slug' => 'toko-dok']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));
        $this->post($store, [
            'title'            => 'Proses Ukir Kinara',
            'excerpt'          => 'Kayu lokal dibaca seratnya dulu.',
            'cover_image_url'  => 'assets/img/kriya/store/store-cover.svg',
        ]);

        $result = $this->get('/store/toko-dok');

        $result->assertSee('Dokumenter Perajin Mandiri');
        $result->assertSee('Proses Ukir Kinara');
        $result->assertSee('Kayu lokal dibaca seratnya dulu.');
    }

    public function testThePlayControlOnlyAppearsWithARealVideo(): void
    {
        $seller = $this->createSeller('documentary.video');
        $store  = $this->makeStore($seller, ['slug' => 'toko-video']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));

        $this->post($store, ['title' => 'Tanpa Video']);

        $this->get('/store/toko-video')->assertDontSee('Tonton Proses Kriya');

        $withVideo = $this->post($store, [
            'title'    => 'Dengan Video',
            'slug'     => 'dengan-video',
            'media_url' => 'assets/img/kriya/store/store-cover.svg',
        ]);

        $this->assertNotEmpty($withVideo['media_url']);
        $this->get('/store/toko-video')->assertSee('Tonton Proses Kriya');
    }

    public function testDraftAndArticlePostsAreNotUsedAsTheDocumentary(): void
    {
        $seller = $this->createSeller('documentary.mismatch');
        $store  = $this->makeStore($seller, ['slug' => 'toko-draf']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));

        $this->post($store, ['title' => 'Naskah Belum Terbit', 'status' => 'draft']);
        $this->post($store, ['title' => 'Artikel Biasa', 'slug' => 'artikel-biasa', 'post_type' => 'article']);

        $result = $this->get('/store/toko-draf');

        $result->assertDontSee('Dokumenter Perajin Mandiri');
        $result->assertDontSee('Naskah Belum Terbit');
        $result->assertDontSee('Artikel Biasa');
    }

    public function testTheNewestPublishedDocumentaryWins(): void
    {
        $seller = $this->createSeller('documentary.order');
        $store  = $this->makeStore($seller, ['slug' => 'toko-terbaru']);
        $this->makeVariant($this->makeProduct($store, ['name' => 'Karya']));

        $this->post($store, ['title' => 'Dokumenter Lama', 'published_at' => '2026-01-01 09:00:00']);
        $this->post($store, ['title' => 'Dokumenter Baru', 'slug' => 'baru', 'published_at' => '2026-06-01 09:00:00']);

        $result = $this->get('/store/toko-terbaru');

        $result->assertSee('Dokumenter Baru');
        $result->assertDontSee('Dokumenter Lama');
    }
}