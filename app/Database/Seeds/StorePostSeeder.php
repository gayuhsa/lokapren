<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Creates the documentary post each storefront shows in its philosophy block.
 *
 * Idempotent: matched on `slug`, which the schema enforces as unique per store.
 * Re-running updates the existing row rather than duplicating it.
 *
 * No `media_url` is seeded, because there is no real video file to point at.
 * The storefront only renders the play control when a URL exists, so a null
 * value means no dead link rather than a broken player.
 *
 * Run with: php spark db:seed StorePostSeeder
 */
class StorePostSeeder extends LokaprenSeeder
{
    private const POSTS = [
        [
            'store_code' => 'MGL-BBD',
            'title'      => 'Proses Ukir Kinara dari Kayu Tropis',
            'slug'       => 'proses-ukir-kinara-kayu-tropis',
            'excerpt'    => 'Menyusul satu batang kayu lokal dari pilihannya sampai relief selesai, tanpa satu pun mesin.',
            'body'       => "Setiap batang kayu yang masuk ke workshop ini dibaca dulu arah seratnya. keras atau lembutnya kayu menentukan boleh tidaknya gouge masuk terlalu dalam.\n\nRelief dikerjakan satu per satu secara manual, bisa memakan waktu berminggu-minggu. Finishing minyak linseed diaplikasi berulang dan dikeringkan perlahan supaya permukaan tidak retak.",
            'cover'      => 'assets/img/kriya/store/omah-kriya-borobudur.svg',
        ],
        [
            'store_code' => 'MGL-KLP',
            'title'      => 'Tekaman Tembaga Desa Klipoh',
            'slug'       => 'tekaman-tembaga-desa-klipoh',
            'excerpt'    => 'Lembaran tembaga dihantam di atas batu cekung, pulih sedikit demi sedikit sampai menjadi teko.',
            'body'       => "Tembaga dipukul satu per satu dengan palu, bukan dipres. Bentuk teko tumbuh perlahan dari satu lembaran datar.\n\nPermukaan diburam dengan batu agar tidak tajam, lalu dikunci dengan perak agar tidak berkarat di udara lembap.",
            'cover'      => 'assets/img/kriya/store/sanggar-kriya-klipoh.svg',
        ],
        [
            'store_code' => 'MGL-MTL',
            'title'      => 'Canting Motif Lereng Merapi',
            'slug'       => 'canting-motif-lereng-merapi',
            'excerpt'    => 'Setiap kain dilukis satu per satu dengan canting, tanpa mesin printing.',
            'body'       => "Motif lereng Merapi digambar dulu di kain, lalu diisi lilin satu per satu dengan canting tunggal.\n\nKain yang sudah kering dicelup beberapa kali. Yang menentukan hasil akhir adalah jumlah celupan, bukan kecepatan tangannya.",
            'cover'      => 'assets/img/kriya/store/batik-tulis-sanggar-muntilan.svg',
        ],
        [
            'store_code' => 'MGL-CRJ',
            'title'      => 'Herringbone untuk Rumah Tangga',
            'slug'       => 'anyaman-herringbone-rumah-tangga',
            'excerpt'    => 'Bambu dipilah sesuai umur, lalu dianyam herringbone agar kuat namun tetap ringan.',
            'body'       => "Bambu yang dipakai harus cukup matang, baru dipilah sesuai panjang yang dibutuhkan. Anyaman herringbone membuat hasil kuat tanpa perlu lem.\n\nUntuk kebutuhan rumah tangga, anyaman dikeringkan di bawah naungan agar tidak retak saat dipakai.",
            'cover'      => 'assets/img/kriya/store/anyaman-bambu-borobudur.svg',
        ],
    ];

    public function run(): void
    {
        foreach (self::POSTS as $post) {
            $storeId = $this->idFor('stores', 'store_code', $post['store_code']);

            if ($storeId === 0) {
                $this->report(sprintf('  %-34s (store missing, skipped)', $post['slug']));

                continue;
            }

            $id = $this->upsertRow('store_posts', 'slug', $post['slug'], [
                'store_id'        => $storeId,
                'title'           => $post['title'],
                'excerpt'         => $post['excerpt'],
                'body'            => $post['body'],
                'cover_image_url' => $post['cover'],
                'media_url'       => null,
                'post_type'       => 'documentary',
                'status'          => 'published',
                'published_at'    => $this->now(),
            ]);

            $this->report(sprintf('  %-34s store_id=%d id=%d', $post['slug'], $storeId, $id));
        }
    }
}