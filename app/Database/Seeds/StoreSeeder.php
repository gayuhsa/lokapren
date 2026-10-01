<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Creates the artisan stores and binds each to its Shield seller account.
 *
 * Idempotent: matched on `store_code`, which is the natural key the schema
 * already enforces. Seller ownership is resolved through upsertUser(), so the
 * stores always end up pointing at real, active accounts even when the
 * account rows already existed.
 *
 * Run with: php spark db:seed StoreSeeder
 */
class StoreSeeder extends LokaprenSeeder
{
    /**
     * Latitude/longitude for Magelang regency craft towns, used by the
     * locator phase. Values are stored as-is and never recomputed here.
     */
    private const STORES = [
        [
            'store_code'        => 'MGL-BBD',
            'seller'            => 'borobudur@lokapren.test',
            'name'              => 'Omah Kriya Borobudur',
            'slug'              => 'omah-kriya-borobudur',
            'tagline'           => 'Ukiran kayu dan relief Tempa Manual Candi Borobudur',
            'description'       => 'Workshop ukiran kayu yang dikerjakan satu per satu tanpa mesin, mulai dari pemilihan kayu sampai finishing.',
            'address_line'      => 'Jl. Borobudur No. 27',
            'subdistrict'       => 'Kecamatan Borobudur',
            'city'              => 'Kabupaten Magelang',
            'lat'               => -7.6079,
            'lng'               => 110.1978,
            'phone'             => '0812-1000-BBD',
            'opening_hours'     => "Senin - Sabtu\n08.00 - 17.00",
            'partner_level'     => 'gold',
        ],
        [
            'store_code'        => 'MGL-KLP',
            'seller'            => 'klipoh@lokapren.test',
            'name'              => 'Sanggar Kriya Klipoh',
            'slug'              => 'sanggar-kriya-klipoh',
            'tagline'           => 'Ukiran kayu dan tekaman tembaga dari Desa Klipoh',
            'description'       => 'Perajin ukiran dan tekaman tembaga yang turun-temurun dari Desa Klipoh.',
            'address_line'      => 'Dusun Klipoh RT 03 RW 05',
            'subdistrict'       => 'Kecamatan Candirejo',
            'city'              => 'Kabupaten Magelang',
            'lat'               => -7.5561,
            'lng'               => 110.1547,
            'phone'             => '0812-1000-KLP',
            'opening_hours'     => "Senin - Jumat\n08.00 - 16.00",
            'partner_level'     => 'silver',
        ],
        [
            'store_code'        => 'MGL-MTL',
            'seller'            => 'muntilan@lokapren.test',
            'name'              => 'Batik Tulis Sanggar Muntilan',
            'slug'              => 'batik-tulis-sanggar-muntilan',
            'tagline'           => 'Batik tulis Lereng Merapi tanpa mesin printing',
            'description'       => 'Sanggar batik tulis yang memproses tiap kain satu per satu menggunakan canting, dari motif lereng Merapi.',
            'address_line'      => 'Jl. Muntilan No. 12',
            'subdistrict'       => 'Kecamatan Muntilan',
            'city'              => 'Kabupaten Magelang',
            'lat'               => -7.4833,
            'lng'               => 110.4503,
            'phone'             => '0812-1000-MTL',
            'opening_hours'     => "Senin - Sabtu\n07.30 - 17.30",
            'partner_level'     => 'gold',
        ],
        [
            'store_code'        => 'MGL-CRJ',
            'seller'            => 'candirejo@lokapren.test',
            'name'              => 'Anyaman Bambu Borobudur',
            'slug'              => 'anyaman-bambu-borobudur',
            'tagline'           => 'Anyaman bambu untuk kebutuhan rumah tangga',
            'description'       => 'Pengrajin anyaman bambu yang memakai teknik herringbone dan anyaman berkekuatan tinggi untuk hasil yang kuat namun ringan.',
            'address_line'      => 'Jl. Candi Sewu No. 8',
            'subdistrict'       => 'Kecamatan Candirejo',
            'city'              => 'Kabupaten Magelang',
            'lat'               => -7.5599,
            'lng'               => 110.1533,
            'phone'             => '0812-1000-CRJ',
            'opening_hours'     => "Senin - Sabtu\n08.00 - 16.00",
            'partner_level'     => 'silver',
        ],
    ];

    public function run(): void
    {
        foreach (self::STORES as $store) {
            $seller = $this->upsertUser(
                $store['seller'],
                UserSeeder::DEMO_PASSWORD,
                ['seller'],
            );

            $id = $this->upsertRow('stores', 'store_code', $store['store_code'], [
                'user_id'             => $seller->id,
                'name'                => $store['name'],
                'slug'                => $store['slug'],
                'tagline'             => $store['tagline'],
                'description'         => $store['description'],
                'address_line'        => $store['address_line'],
                'subdistrict'         => $store['subdistrict'],
                'city'                => $store['city'],
                'lat'                 => $store['lat'],
                'lng'                 => $store['lng'],
                'phone'               => $store['phone'],
                'opening_hours'       => $store['opening_hours'],
                'verification_status' => 'verified',
                'verified_at'         => $this->now(),
                'partner_level'       => $store['partner_level'],
                'is_active'           => true,
            ]);

            $this->report(sprintf('  %-10s %-34s seller=%s id=%d', $store['store_code'], $store['name'], $store['seller'], $id));
        }
    }
}