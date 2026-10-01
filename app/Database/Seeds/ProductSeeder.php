<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Creates the published catalogue: products, their priced variants and images.
 *
 * Idempotent: products are matched on `slug` and variants on `sku`, so a
 * re-run refreshes copy and stock in place without touching ids that the
 * cart and order tables will later point at.
 *
 * Run with: php spark db:seed ProductSeeder
 */
class ProductSeeder extends LokaprenSeeder
{
    /**
     * Images live in public/assets/img/kriya and are committed as part of the
     * repo, so the catalogue renders with no external requests.
     */
    private const PRODUCTS = [
        [
            'slug'        => 'plakat-relief-candi-borobudur',
            'store'       => 'MGL-BBD',
            'category'    => 'ukiran-kayu',
            'name'        => 'Plakat Relief Candi Borobudur',
            'featured'    => true,
            'description' => 'Plakat kayu relief dengan motif Candi Borobudur yang dipahat satu per satu. Dinding relief dibentuk bertahap dari kayu jati pilihan supaya detail garis candinya tetap tajam.',
            'attributes'  => '{"material":"Kayu Jati","finishing":"Minyak Linseed","origin":"Borobudur, Magelang"}',
            'variants'    => [
                ['name' => 'Ukuran M (25 x 20 cm)', 'sku' => 'BBD-PLA-M', 'price' => 340000, 'stock' => 4],
                ['name' => 'Ukuran L (35 x 28 cm)', 'sku' => 'BBD-PLA-L', 'price' => 450000, 'stock' => 3],
                ['name' => 'Kolektor XL (50 x 40 cm)', 'sku' => 'BBD-PLA-XL', 'price' => 820000, 'stock' => 0],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/plakat-relief-borobudur-1.svg', 'alt' => 'Tampak Depan'],
                ['url' => 'assets/img/kriya/plakat-relief-borobudur-2.svg', 'alt' => 'Detail Pahat'],
                ['url' => 'assets/img/kriya/plakat-relief-borobudur-3.svg', 'alt' => 'Interior Staging'],
                ['url' => 'assets/img/kriya/plakat-relief-borobudur-4.svg', 'alt' => 'Besek Bambu'],
            ],
        ],
        [
            'slug'        => 'relung-candi-aba',
            'store'       => 'MGL-BBD',
            'category'    => 'ukiran-kayu',
            'name'        => 'Relung Candi Aba',
            'featured'    => true,
            'description' => 'Relung ukiran dengan motif candi abad ke-8 yang dikerjakan dari balok kayu lokal tanpa mesin, pola ukirannya mengikuti sisa struktur yang ditemukan di lapangan.',
            'attributes'  => '{"material":"Kayu Sono","finishing":"Minyak Tunggal","origin":"Borobudur, Magelang"}',
            'variants'    => [
                ['name' => 'Sedang (24 x 18 cm)', 'sku' => 'BBD-RLA-S', 'price' => 275000, 'stock' => 5],
                ['name' => 'Besar (34 x 26 cm)', 'sku' => 'BBD-RLA-B', 'price' => 385000, 'stock' => 2],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/relung-candi-aba-1.svg', 'alt' => 'Tampak Depan'],
                ['url' => 'assets/img/kriya/relung-candi-aba-2.svg', 'alt' => 'Detail Pahat'],
            ],
        ],
        [
            'slug'        => 'pahatan-kinara-kinarya',
            'store'       => 'MGL-BBD',
            'category'    => 'ukiran-kayu',
            'name'        => 'Pahatan Kinara Kinarya',
            'featured'    => false,
            'description' => 'Pahatan gading dari kayu tropis dengan proporsi yang dikerjakan sebagai alat ukir, ringan di tangan namun kuat untuk incision detail.',
            'attributes'  => '{"material":"Kayu Tropis","finishing":"Semi Matte","origin":"Borobudur, Magelang"}',
            'variants'    => [
                ['name' => 'Mini (12 cm)', 'sku' => 'BBD-KIN-M', 'price' => 165000, 'stock' => 6],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/kinara-kinarya-1.svg', 'alt' => 'Tampak Depan'],
            ],
        ],
        [
            'slug'        => 'cangkir-kayu-jati-klipoh',
            'store'       => 'MGL-KLP',
            'category'    => 'ukiran-kayu',
            'name'        => 'Cangkir Kayu Jati Klipoh',
            'featured'    => false,
            'description' => 'Cangkir minum dari kayu jati yang dikeringkan perlahan agar tidak retak. Bagian dalam dibuat halus tanpa pelapis agar rasa teh tidak berubah.',
            'attributes'  => '{"material":"Kayu Jati","finishing":"Tanpa Pelapis","origin":"Klipoh, Magelang"}',
            'variants'    => [
                ['name' => '350 ml', 'sku' => 'KLP-CNG-35', 'price' => 95000, 'stock' => 12],
                ['name' => '500 ml', 'sku' => 'KLP-CNG-50', 'price' => 125000, 'stock' => 8],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/cangkir-kayu-1.svg', 'alt' => 'Tampak Depan'],
                ['url' => 'assets/img/kriya/cangkir-kayu-2.svg', 'alt' => 'Detail Pahat'],
            ],
        ],
        [
            'slug'        => 'teko-tempa-tembaga-merapi',
            'store'       => 'MGL-KLP',
            'category'    => 'kriya-tembaga',
            'name'        => 'Teko Tempa Tembaga Merapi',
            'featured'    => true,
            'description' => 'Teko tempa tangan dari tembaga murni dengan ketebalan yang bervariasi mengikuti teknik palu. Diolah tanpa bahan pelapis agar tetap berkarakter.',
            'attributes'  => '{"material":"Tembaga Murni","finishing":"Tembaga Dicuci Asam","origin":"Klipoh, Magelang"}',
            'variants'    => [
                ['name' => '1 Liter', 'sku' => 'KLP-TKO-1L', 'price' => 245000, 'stock' => 3],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/teko-tembaga-1.svg', 'alt' => 'Tampak Depan'],
            ],
        ],
        [
            'slug'        => 'kain-batik-tulis-lereng-merapi',
            'store'       => 'MGL-MTL',
            'category'    => 'batik-tulis',
            'name'        => 'Kain Batik Tulis Lereng Merapi',
            'featured'    => true,
            'description' => 'Kain batik tulis motif lereng Merapi. Setiap lembar dicanting satu per satu tanpa mesin printing, sehingga corak antarlembar selalu sedikit berbeda.',
            'attributes'  => '{"material":"Kain Katun Prima","teknik":"Batik Tulis","origin":"Muntilan, Magelang"}',
            'variants'    => [
                ['name' => '2,5 Meter', 'sku' => 'MTL-BTK-25', 'price' => 650000, 'stock' => 2],
                ['name' => '4 Meter', 'sku' => 'MTL-BTK-40', 'price' => 980000, 'stock' => 1],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/batik-lereng-merapi-1.svg', 'alt' => 'Tampak Depan'],
                ['url' => 'assets/img/kriya/batik-lereng-merapi-2.svg', 'alt' => 'Detail Pahat'],
            ],
        ],
        [
            'slug'        => 'besek-roket-bambu-candirejo',
            'store'       => 'MGL-CRJ',
            'category'    => 'anyaman-bambu',
            'name'        => 'Besek Roket Bambu Candirejo',
            'featured'    => false,
            'description' => 'Besek anyaman bambu dengan pola roket yang umum dipakai di rumah desa Magelang. Ringan, kuat, dan bisa dilipat saat tidak dipakai.',
            'attributes'  => '{"material":"Bambu Ori","teknik":"Anyaman herringbone","origin":"Candirejo, Magelang"}',
            'variants'    => [
                ['name' => 'Sedang (40 cm)', 'sku' => 'CRJ-BSK-S', 'price' => 88000, 'stock' => 15],
                ['name' => 'Besar (60 cm)', 'sku' => 'CRJ-BSK-B', 'price' => 132000, 'stock' => 9],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/besek-bambu-1.svg', 'alt' => 'Tampak Depan'],
            ],
        ],
        [
            'slug'        => 'keranjang-padi-villager',
            'store'       => 'MGL-CRJ',
            'category'    => 'anyaman-bambu',
            'name'        => 'Keranjang Padi Villager',
            'featured'    => false,
            'description' => 'Keranjang padi anyaman bambu dengan ukuran standar untuk menampung hasil panen. Bagian dibuat rapat sehingga beras tidak mudah tumpah.',
            'attributes'  => '{"material":"Bambu Ori","teknik":"Anyaman rapat","origin":"Candirejo, Magelang"}',
            'variants'    => [
                ['name' => 'Besar', 'sku' => 'CRJ-KRJ-B', 'price' => 210000, 'stock' => 4],
            ],
            'images'      => [
                ['url' => 'assets/img/kriya/keranjang-padi-1.svg', 'alt' => 'Tampak Depan'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::PRODUCTS as $product) {
            $productId = $this->upsertRow('products', 'slug', $product['slug'], [
                'store_id'     => $this->idFor('stores', 'store_code', $product['store']),
                'category_id'  => $this->idFor('categories', 'slug', $product['category']),
                'name'         => $product['name'],
                'description'  => $product['description'],
                'attributes'   => $product['attributes'],
                'status'       => 'active',
                'is_featured'  => $product['featured'],
            ]);

            $variantCount = $this->seedVariants($productId, $product['variants']);
            $imageCount   = $this->seedImages($productId, $product['images']);

            $this->report(sprintf(
                '  %-36s variants=%-2d images=%-2d id=%d',
                $product['name'],
                $variantCount,
                $imageCount,
                $productId,
            ));
        }
    }

    /**
     * @param list<array{name: string, sku: string, price: int, stock: int}> $variants
     */
    private function seedVariants(int $productId, array $variants): int
    {
        foreach ($variants as $variant) {
            $this->upsertRow('product_variants', 'sku', $variant['sku'], [
                'product_id' => $productId,
                'name'       => $variant['name'],
                'price'      => $variant['price'],
                'stock'      => $variant['stock'],
                'is_active'  => true,
            ]);
        }

        return count($variants);
    }

    /**
     * Images have no natural key, so they are keyed on product + url.
     *
     * @param list<array{url: string, alt: string}> $images
     */
    private function seedImages(int $productId, array $images): int
    {
        foreach ($images as $position => $image) {
            $data = [
                'product_id' => $productId,
                'url'        => $image['url'],
                'alt'        => $image['alt'],
                'sort_order' => $position,
            ];

            $exists = $this->db
                ->table('product_images')
                ->where('product_id', $productId)
                ->where('url', $image['url'])
                ->get()
                ->getRow();

            if ($exists !== null) {
                $this->db->table('product_images')->where('id', $exists->id)->update($data);

                continue;
            }

            $this->db->table('product_images')->insert($data + [
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }

        return count($images);
    }
}