<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\SellerFacilityModel;

/**
 * A complete, browsable marketplace for a local `spark db:seed` run.
 *
 * Everything here is ordinary application data — Shield users with real
 * password identities, seller storefronts, a catalog, articles and one settled
 * order — so every screen has something to show and no screen needs a special
 * "demo mode". Three rules keep it predictable:
 *
 * - it is guarded by a marker row, so running it twice never doubles the data;
 * - every id is read back from the connection rather than assumed, so the
 *   seeder works on MariaDB and SQLite alike;
 * - images are generated from a seeded RNG into deterministic filenames, so
 *   the uploads directory does not grow on a re-run.
 *
 * Run it with: `php spark db:seed 'Database\Seeds\DemoSeeder'`.
 */
class DemoSeeder extends Seeder
{
    /**
     * The password every demo account uses. It is demo data by definition and
     * lives nowhere else, so it is safe to print in the CLI summary.
     */
    public const PASSWORD = 'lokapren123';

    /**
     * Usernames that mark the demo data as already present.
     */
    private const MARKER = 'sari.pembeli';

    /**
     * Warm, saturated tones so a generated tile reads as a kriya pattern
     * rather than a grey placeholder.
     *
     * @var list<array{0: int, 1: int, 2: int}>
     */
    private const PALETTE = [
        [ 40,  72,  64],
        [168,  66,  44],
        [206, 152,  58],
        [ 38,  68, 104],
        [120,  62,  96],
        [ 58,  92, 122],
    ];

    /**
     * @var array<string, int> usernames to their `users.id`
     */
    private array $users = [];

    /**
     * @var array<string, int> seller usernames to their `seller_profiles.id`
     */
    private array $profiles = [];

    /**
     * @var array<string, int> category slugs to `product_categories.id`
     */
    private array $categories = [];

    /**
     * @var array<string, int> product slugs to `products.id`
     */
    private array $products = [];

    public function run(): void
    {
        if ($this->isSeeded()) {
            $this->note('Demo data already present, nothing to do.');

            return;
        }

        $this->db->transStart();

        $this->seedUsers();
        $this->seedSellerProfiles();
        $this->seedSellerPages();
        $this->seedCatalog();
        $this->seedArticles();
        $this->seedCustomer();
        $this->seedOrder();
        $this->seedDailyStats();
        $this->reconcileAggregates();

        $this->db->transComplete();

        $this->note('Demo marketplace seeded.');
        $this->note('Sign in at /login — every account uses the password ' . self::PASSWORD . '.');
    }

    // ------------------------------------------------------------------
    // Users
    // ------------------------------------------------------------------

    /**
     * Shield accounts. `UserModel::save()` writes the email/password identity
     * through its `afterInsert` callback, so this is the same path the
     * registration controller takes.
     */
    private function seedUsers(): void
    {
        $users = model(UserModel::class);

        $accounts = [
            'gebyok.mandiri' => ['gebyok@lokapren.test', 'seller'],
            'tenun.magelang' => ['tenun@lokapren.test', 'seller'],
            'keramik.magelang' => ['keramik@lokapren.test', 'seller'],
            'sari.pembeli'   => ['sari@lokapren.test', 'customer'],
        ];

        foreach ($accounts as $username => [$email, $group]) {
            $user = $users->createNewUser([
                'username' => $username,
                'email'    => $email,
                'password' => self::PASSWORD,
                'active'   => 1,
            ]);

            $users->save($user);

            $created = $users->findById($users->getInsertID());
            $created->addGroup($group);

            $this->users[$username] = (int) $created->id;
        }
    }

    // ------------------------------------------------------------------
    // Storefronts
    // ------------------------------------------------------------------

    private function seedSellerProfiles(): void
    {
        $now = date('Y-m-d H:i:s');

        $shops = [
            'gebyok.mandiri' => [
                'slug'          => 'gebyok-mandiri',
                'partner_code'  => 'AB-0001',
                'display_name'  => 'Gebyok Mandiri',
                'owner_name'    => 'Suryanto',
                'tagline'       => 'Ukiran kayu jati dari Magelang sejak 1998',
                'description'   => "Sanggar ukir keluarga di Panjang, Magelang Utara. Setiap gebyok dipahat tangan, tanpa cetakan, dan dikeringkan delapan bulan sebelum dirakit.\n\nKami menerima pesanan ukir menyesuaikan lebar pintu dan motif pilihan pembeli.",
                'craft_focus'   => 'Ukiran & Gebyok Jati',
                'artisan_count' => 12,
                'address_line'  => 'Jl. Urip Sumoharjo No. 12, Panjang',
                'village'       => 'Panjang',
                'district'      => 'Magelang Utara',
                'regency'       => 'Magelang',
                'province'      => 'Jawa Tengah',
                'postal_code'   => '56111',
                'latitude'      => '-7.4621000',
                'longitude'     => '110.2198000',
                'landmark_name' => 'Tugu Kota Magelang',
                'landmark_distance_km' => '1.10',
                'member_since'  => '2018-03-04',
                'seed'          => 11,
            ],
            'tenun.magelang' => [
                'slug'          => 'tenun-magelang',
                'partner_code'  => 'AB-0002',
                'display_name'  => 'Tenun Magelang',
                'owner_name'    => 'Siti Maryam',
                'tagline'       => 'Tenun ikat sutra yang ditenun di depan pengunjung',
                'description'   => 'Lima alat tenun non-mesin berdiri di ruang depan rumah. Kami membuka kelas menganyam setiap Sabtu untuk pelajar dan wisatawan.',
                'craft_focus'   => 'Tenun Ikat & Selendang',
                'artisan_count' => 7,
                'address_line'  => 'Jl. Jend. Ahmad Yani No. 45, Rejotengan',
                'village'       => 'Rejotengan',
                'district'      => 'Magelang Tengah',
                'regency'       => 'Magelang',
                'province'      => 'Jawa Tengah',
                'postal_code'   => '56125',
                'latitude'      => '-7.4774000',
                'longitude'     => '110.2241000',
                'landmark_name' => 'Alun-alun Kota Magelang',
                'landmark_distance_km' => '0.60',
                'member_since'  => '2019-08-17',
                'seed'          => 22,
            ],
            'keramik.magelang' => [
                'slug'          => 'keramik-magelang',
                'partner_code'  => 'AB-0003',
                'display_name'  => 'Keramik Magelang',
                'owner_name'    => 'Bagus Prasetyo',
                'tagline'       => 'Peralatan makan dari tanah liat Magelang',
                'description'   => 'Dua tungku listrik dan satu tungku kayu menghasilkan teko, vas, dan piring dengan glasir ramah pangan.',
                'craft_focus'   => 'Keramik & Porselen',
                'artisan_count' => 9,
                'address_line'  => 'Jl. Magelang-Yogyakarta KM 15, Muntilan',
                'village'       => 'Muntilan',
                'district'      => 'Muntilan',
                'regency'       => 'Magelang',
                'province'      => 'Jawa Tengah',
                'postal_code'   => '56412',
                'latitude'      => '-7.5608000',
                'longitude'     => '110.2472000',
                'landmark_name' => 'Pasar Muntilan',
                'landmark_distance_km' => '0.90',
                'member_since'  => '2020-01-22',
                'seed'          => 33,
            ],
        ];

        foreach ($shops as $username => $shop) {
            $sellerId = $this->users[$username];

            $logo = $this->image('demo/shops/' . $shop['slug'] . '-logo.png', 256, 256, $shop['seed']);
            $cover = $this->image('demo/shops/' . $shop['slug'] . '-cover.png', 1280, 512, $shop['seed'] + 1);

            $this->insert('seller_profiles', [
                'user_id'              => $sellerId,
                'slug'                 => $shop['slug'],
                'partner_code'         => $shop['partner_code'],
                'display_name'         => $shop['display_name'],
                'owner_name'           => $shop['owner_name'],
                'tagline'              => $shop['tagline'],
                'description'          => $shop['description'],
                'craft_focus'          => $shop['craft_focus'],
                'logo_path'            => $logo,
                'cover_path'           => $cover,
                'address_line'         => $shop['address_line'],
                'village'              => $shop['village'],
                'district'             => $shop['district'],
                'regency'              => $shop['regency'],
                'province'             => $shop['province'],
                'postal_code'          => $shop['postal_code'],
                'landmark_name'        => $shop['landmark_name'],
                'landmark_distance_km' => $shop['landmark_distance_km'],
                'latitude'             => $shop['latitude'],
                'longitude'            => $shop['longitude'],
                'location_verified_at' => $now,
                'artisan_count'        => $shop['artisan_count'],
                'is_active'            => 1,
                'is_verified'          => 1,
                'verification_level'   => 'identitas',
                'verified_at'          => $now,
                'member_since'         => $shop['member_since'],
                'avg_response_minutes' => 18,
                'created_at'           => $shop['member_since'] . ' 08:00:00',
                'updated_at'           => $now,
            ]);

            $this->profiles[$username] = (int) $this->db->insertID();
        }
    }

    /**
     * Hours, facilities, story sections and quick replies — the rows that make
     * a storefront look operated rather than merely registered.
     */
    private function seedSellerPages(): void
    {
        // Monday-first, matching the `date('N')` that `isOpenNow()` compares on.
        $weekday = [
            1 => ['08:00:00', '17:00:00', 0],
            2 => ['08:00:00', '17:00:00', 0],
            3 => ['08:00:00', '17:00:00', 0],
            4 => ['08:00:00', '17:00:00', 0],
            5 => ['08:00:00', '17:00:00', 0],
            6 => ['08:00:00', '14:00:00', 0],
            7 => [null, null, 1],
        ];

        $saturdayWorkshop = [
            1 => ['08:00:00', '17:00:00', 0],
            2 => ['08:00:00', '17:00:00', 0],
            3 => ['08:00:00', '17:00:00', 0],
            4 => ['08:00:00', '17:00:00', 0],
            5 => ['08:00:00', '17:00:00', 0],
            6 => ['09:00:00', '13:00:00', 0],
            7 => [null, null, 1],
        ];

        $shops = [
            'gebyok.mandiri' => [
                'hours'      => $weekday,
                'facilities' => ['galeri', 'parkir_mobil', 'wifi', 'opsi_takeaway'],
                'stories'   => [
                    ['Kayu Jati Dijemur Delapan Bulan', 'Bahan baku', 'Pilih-pilih kayu jati berumur minimal 40 tahun sebelum masuk bengkel. Kayu yang terburu-buru diolah akan retak di kemarau.'],
                    ['Pahatan yang Tidak Dibuat Cetakan', 'Teknik', 'Motif klasik dipahat langsung, sehingga tidak ada dua gebyok yang benar-benar identik.'],
                ],
                'replies'   => [
                    ['Terima kasih', 'Terima kasih sudah menghubungi Gebyok Mandiri. Kami balas pada jam kerja 08.00–17.00 WIB.'],
                    ['Estimasi produksi', 'Untuk pesanan khusus, estimasi pengerjaan 21–45 hari kerja tergantung tingkat kerumitan ukiran.'],
                    ['Pengiriman', 'Kami mengirim lewat ekspedisi berat dengan packing kayu dan bubble wrap.'],
                ],
                'seed' => 110,
            ],
            'tenun.magelang' => [
                'hours'     => $saturdayWorkshop,
                'facilities' => ['kelas_mengamik', 'galeri', 'parkir_mobil', 'wc'],
                'stories'   => [
                    ['Lima Alat Tenun di Ruang Depan', 'Rumah sanggar', 'Pengunjung boleh duduk dan mencoba alat tenun selama jam buka.'],
                    ['Pewarnaan Alami dari Daun Tomentosa', 'Bahan', 'Benang dicelup tiga kali agar warna sogan tetap pekat setelah dicuci.'],
                ],
                'replies'   => [
                    ['Kelas menganyam', 'Kelas menganyam dibuka setiap Sabtu pukul 09.00 WIB, pendaftaran maksimal H-3.'],
                    ['Ukuran selendang', 'Selendang tersedia ukuran 200x60 cm dan 250x70 cm. Keduanya bisa dipesan melalui chat.'],
                    ['Perawatan tenun', 'Cuci kering atau cuci tangan dengan air dingin, jangan diperas.'],
                ],
                'seed' => 220,
            ],
            'keramik.magelang' => [
                'hours'     => $weekday,
                'facilities' => ['kelas_melukis', 'galeri', 'parkir_mobil', 'wifi', 'wc'],
                'stories'   => [
                    ['Dua Tungku Listrik dan Satu Tungku Kayu', 'Kilang', 'Tungku kayu dipakai untuk glasir bertekstur, sisanya dikendalikan suhunya lewat listrik.'],
                    ['Glasir Ramah Pangan', 'Keamanan', 'Seluruh koleksi makanan lulus uji larut timbal sebelum dikemas.'],
                ],
                'replies'   => [
                    ['Pemesanan grosir', 'Harga grosir mulai 24 pcs. Silakan sebutkan jumlah dan model yang dibutuhkan.'],
                    ['Kelas melukis glasir', 'Kelas melukis glasir berlangsung tiap Minggu, durasi dua jam, sudah termasuk bahan.'],
                    ['Penggantian pecah', 'Barang pecah dalam perjalanan kami ganti baru, cukup kirim foto unboxing.'],
                ],
                'seed' => 330,
            ],
        ];

        foreach ($shops as $username => $shop) {
            $sellerId = $this->users[$username];
            $seed     = $shop['seed'];

            foreach ($shop['hours'] as $day => [$opens, $closes, $closed]) {
                $this->insert('seller_business_hours', [
                    'seller_id'   => $sellerId,
                    'day_of_week' => $day,
                    'opens_at'    => $opens,
                    'closes_at'   => $closes,
                    'is_closed'   => $closed,
                ]);
            }

            foreach (array_values($shop['facilities']) as $position => $code) {
                $this->insert('seller_facilities', [
                    'seller_id' => $sellerId,
                    'facility'  => $code,
                    'label'     => SellerFacilityModel::FACILITIES[$code] ?? $code,
                    'position'  => $position,
                ]);
            }

            foreach ($shop['stories'] as $position => [$title, $subtitle, $body]) {
                $this->insert('seller_story_sections', [
                    'seller_id'  => $sellerId,
                    'title'      => $title,
                    'subtitle'   => $subtitle,
                    'body'       => $body,
                    'image_path' => $this->image(
                        'demo/shops/' . $this->profiles[$username] . '-story-' . $position . '.png',
                        640,
                        480,
                        $seed + $position
                    ),
                    'position'  => $position,
                    'is_active' => 1,
                ]);
            }

            foreach ($shop['replies'] as $position => [$title, $body]) {
                $this->insert('seller_quick_replies', [
                    'seller_id' => $sellerId,
                    'title'     => $title,
                    'body'      => $body,
                    'position'  => $position,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // ------------------------------------------------------------------
    // Catalog
    // ------------------------------------------------------------------

    private function seedCatalog(): void
    {
        $tree = [
            'ukiran-kayu' => ['Ukiran Kayu', 1, [
                'gebyok' => ['Gebyok', 1],
                'relief' => ['Relief', 2],
            ]],
            'tenun' => ['Tenun', 2, [
                'tenun-ikat' => ['Tenun Ikat', 1],
            ]],
            'keramik' => ['Keramik', 3, []],
            'anyaman' => ['Anyaman', 4, []],
        ];

        foreach ($tree as $slug => [$name, $position, $children]) {
            $this->categories[$slug] = $this->insert('product_categories', [
                'parent_id' => null,
                'name'      => $name,
                'slug'      => $slug,
                'position'  => $position,
                'is_active' => 1,
            ]);

            foreach ($children as $childSlug => [$childName, $childPosition]) {
                $this->categories[$childSlug] = $this->insert('product_categories', [
                    'parent_id' => $this->categories[$slug],
                    'name'      => $childName,
                    'slug'      => $childSlug,
                    'position'  => $childPosition,
                    'is_active' => 1,
                ]);
            }
        }

        $products = [
            [
                'seller'   => 'gebyok.mandiri',
                'category' => 'gebyok',
                'code'     => 'P-10001',
                'slug'     => 'gebyok-jati-ukir-naga',
                'name'     => 'Gebyok Jati Ukir Naga',
                'subtitle' => 'Pintu penyekat ruang tamu, ukir naga',
                'summary'  => 'Gebyok enam daun dari jati perhutani dengan motif naga yang dipahat tangan.',
                'description' => "Gebyok enam daun dengan tinggi 220 cm dan lebar 300 cm. Rangka dibuat dari jati perhutani berumur minimal 40 tahun, dikeringkan delapan bulan, lalu dipahat langsung tanpa cetakan.\n\nHarga sudah termasuk perakitan di tempat untuk wilayah Magelang dan sekitarnya.",
                'story'    => 'Motif naga ini diajarkan Pak Suryanto kepada anak-anak sanggar sejak 1998, dan hampir tidak berubah sampai sekarang.',
                'material' => 'Jati perhutani grade A',
                'finishing' => 'Melamik doff dua lapis',
                'price'    => 4500000,
                'stock'    => 2,
                'low'      => 1,
                'weight'   => 85000,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 1,
                'views'    => 412,
                'seed'     => 1001,
                'images'   => 2,
            ],
            [
                'seller'   => 'gebyok.mandiri',
                'category' => 'relief',
                'code'     => 'P-10002',
                'slug'     => 'relif-ukir-motif-klasik',
                'name'     => 'Relif Ukir Motif Klasik',
                'subtitle' => 'Panel dinding 60x90 cm',
                'summary'  => 'Panel relif jati untuk dinding kantor atau ruang keluarga.',
                'description' => 'Panel relif berukuran 60x90 cm dengan bingkai kayu jati. Siap dipasang, sudah dilubangi pengait di bagian belakang.',
                'story'    => null,
                'material' => 'Jati perhutani',
                'finishing' => 'Walur alami',
                'price'    => 1250000,
                'stock'    => 5,
                'low'      => 2,
                'weight'   => 12000,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 0,
                'views'    => 176,
                'seed'     => 1002,
                'images'   => 1,
            ],
            [
                'seller'   => 'gebyok.mandiri',
                'category' => 'gebyok',
                'code'     => 'P-10003',
                'slug'     => 'gebyok-pintu-ganda-minimalis',
                'name'     => 'Gebyok Pintu Ganda Minimalis',
                'subtitle' => 'Dibuat sesuai pesanan',
                'summary'  => 'Gebyok dua daun bergaya minimalis, dikerjakan setelah pesanan masuk.',
                'description' => 'Gebyok dua daun untuk pintu masuk utama. Ukuran dan motif disesuaikan dengan lebar ruang Anda setelah survey lokasi.',
                'story'    => null,
                'material' => 'Jati perhutani',
                'finishing' => 'Melamik semi doff',
                'price'    => 6750000,
                'stock'    => 0,
                'low'      => 0,
                'weight'   => 92000,
                'made'     => 1,
                'days'     => 30,
                'status'   => 'published',
                'featured' => 0,
                'views'    => 98,
                'seed'     => 1003,
                'images'   => 1,
            ],
            [
                'seller'   => 'tenun.magelang',
                'category' => 'tenun-ikat',
                'code'     => 'P-10004',
                'slug'     => 'tenun-ikat-sogan-magelang',
                'name'     => 'Tenun Ikat Sogan Magelang',
                'subtitle' => 'Sutera, warna alami',
                'summary'  => 'Kain tenun ikat sutera dengan pewarnaan alami daun tomentosa.',
                'description' => 'Kain tenun ikat berbahan sutera, ditenun tangan dengan alat tenun BUK. Tersedia dua ukuran panjang.',
                'story'    => 'Setiap helai dicelup tiga kali agar warna sogan tetap pekat setelah beberapa kali dicuci.',
                'material' => 'Sutera alami',
                'finishing' => 'Renda tangan',
                'price'    => 850000,
                'stock'    => 8,
                'low'      => 3,
                'weight'   => 450,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 1,
                'views'    => 305,
                'seed'     => 1004,
                'images'   => 2,
            ],
            [
                'seller'   => 'tenun.magelang',
                'category' => 'tenun-ikat',
                'code'     => 'P-10005',
                'slug'     => 'selendang-tenun-eksklusif',
                'name'     => 'Selendang Tenun Eksklusif',
                'subtitle' => '200x60 cm',
                'summary'  => 'Selendang tenun bermotif parang untuk acara resmi.',
                'description' => 'Selendang tenun ukuran 200x60 cm dengan motif parang dan pinggiran berumbai.',
                'story'    => null,
                'material' => 'Sutera dan katun',
                'finishing' => 'Pinggiran berumbai',
                'price'    => 650000,
                'stock'    => 12,
                'low'      => 4,
                'weight'   => 280,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 0,
                'views'    => 141,
                'seed'     => 1005,
                'images'   => 1,
            ],
            [
                'seller'   => 'keramik.magelang',
                'category' => 'keramik',
                'code'     => 'P-10006',
                'slug'     => 'teko-keramik-daur-ulang',
                'name'     => 'Teko Keramik Daur Ulang',
                'subtitle' => 'Kapasitas 900 ml',
                'summary'  => 'Teo teh dari tanah liat daur ulang dengan glasir matte.',
                'description' => 'Teo teh berkapasitas 900 ml, aman untuk mesin cuci piring dan microwave. Glasir matte tidak mudah tergores.',
                'story'    => null,
                'material' => 'Tanah liat daur ulang',
                'finishing' => 'Glasir matte',
                'price'    => 320000,
                'stock'    => 20,
                'low'      => 5,
                'weight'   => 620,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 1,
                'views'    => 268,
                'seed'     => 1006,
                'images'   => 1,
            ],
            [
                'seller'   => 'keramik.magelang',
                'category' => 'keramik',
                'code'     => 'P-10007',
                'slug'     => 'vas-keramik-tone-on-tone',
                'name'     => 'Vas Keramik Tone-on-Tone',
                'subtitle' => 'Tinggi 24 cm',
                'summary'  => 'Vas bunga dengan glasir satu warna bertekstur halus.',
                'description' => 'Vas setinggi 24 cm dengan glasir satu warna. Cocok untuk bunga kering maupun segar.',
                'story'    => null,
                'material' => 'Porselen',
                'finishing' => 'Glasir bertekstur',
                'price'    => 475000,
                'stock'    => 15,
                'low'      => 3,
                'weight'   => 900,
                'made'     => 0,
                'days'     => null,
                'status'   => 'published',
                'featured' => 0,
                'views'    => 122,
                'seed'     => 1007,
                'images'   => 1,
            ],
            [
                'seller'   => 'keramik.magelang',
                'category' => 'anyaman',
                'code'     => 'P-10008',
                'slug'     => 'tas-anyaman-bambu-magelang',
                'name'     => 'Tas Anyaman Bambu Magelang',
                'subtitle' => 'Masih berupa draf',
                'summary'  => 'Tas belanja dari bambu muda, menunggu foto akhir.',
                'description' => 'Tas belanja anyaman bambu muda dari Magelang.',
                'story'    => null,
                'material' => 'Bambu muda',
                'finishing' => 'Pernis kayu',
                'price'    => 185000,
                'stock'    => 0,
                'low'      => 0,
                'weight'   => 350,
                'made'     => 0,
                'days'     => null,
                'status'   => 'draft',
                'featured' => 0,
                'views'    => 0,
                'seed'     => 1008,
                'images'   => 0,
            ],
        ];

        foreach ($products as $product) {
            $sellerId = $this->users[$product['seller']];

            $id = $this->insert('products', [
                'seller_id'          => $sellerId,
                'category_id'        => $this->categories[$product['category']],
                'product_code'       => $product['code'],
                'slug'               => $product['slug'],
                'name'               => $product['name'],
                'subtitle'           => $product['subtitle'],
                'summary'            => $product['summary'],
                'description'        => $product['description'],
                'story'              => $product['story'],
                'material'           => $product['material'],
                'finishing'          => $product['finishing'],
                'price'              => $product['price'],
                'stock'              => $product['stock'],
                'low_stock_threshold' => $product['low'],
                'weight_gram'        => $product['weight'],
                'made_to_order'      => $product['made'],
                'production_days'    => $product['days'],
                'status'             => $product['status'],
                'is_active'          => 1,
                'is_featured'        => $product['featured'],
                'view_count'         => $product['views'],
                'published_at'       => $product['status'] === 'published' ? date('Y-m-d H:i:s', strtotime('-' . ($product['seed'] % 40) . ' days')) : null,
                'created_at'         => date('Y-m-d H:i:s', strtotime('-' . (($product['seed'] % 40) + 5) . ' days')),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            $this->products[$product['slug']] = $id;

            $this->seedProductImages($id, $product);
            $this->seedProductVariants($id, $product);
        }
    }

    /**
     * Generated tiles rather than a shared placeholder, so no card in the
     * catalog points at a missing file.
     */
    private function seedProductImages(int $productId, array $product): void
    {
        for ($position = 0; $position < $product['images']; $position++) {
            $this->insert('product_images', [
                'product_id' => $productId,
                'variant_id' => null,
                'file_path'  => $this->image(
                    'demo/products/' . $product['slug'] . '-' . $position . '.png',
                    960,
                    720,
                    $product['seed'] + $position
                ),
                'thumb_path' => null,
                'alt_text'   => $product['name'],
                'caption'    => $position === 0 ? $product['subtitle'] : null,
                'is_primary' => $position === 0 ? 1 : 0,
                'position'   => $position,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedProductVariants(int $productId, array $product): void
    {
        if ($product['slug'] !== 'tenun-ikat-sogan-magelang') {
            return;
        }

        $variants = [
            ['V-10004-A', 'TENUN-SOGAN-200', 'Panjang 200 cm', null, 8, 0],
            ['V-10004-B', 'TENUN-SOGAN-250', 'Panjang 250 cm', 150000, 6, 1],
        ];

        foreach ($variants as $position => [$code, $sku, $label, $price, $stock, $default]) {
            $this->insert('product_variants', [
                'product_id'   => $productId,
                'variant_code' => $code,
                'sku'          => $sku,
                'label'        => $label,
                'price'        => $price,
                'stock'        => $stock,
                'weight_gram'  => $price === null ? 450 : 680,
                'is_default'   => $default,
                'is_active'    => 1,
                'position'     => $position,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Articles
    // ------------------------------------------------------------------

    private function seedArticles(): void
    {
        $sections = [
            'proses' => 'Proses Produksi',
            'tips'   => 'Tips & Perawatan',
            'berita' => 'Berita Sanggar',
        ];

        foreach ($sections as $slug => $name) {
            $this->insert('blog_categories', [
                'parent_id' => null,
                'name'      => $name,
                'slug'      => $slug,
            ]);
        }

        $posts = [
            [
                'seller'   => 'gebyok.mandiri',
                'section'  => 'proses',
                'slug'     => 'dari-balik-pahatan-satu-minggu-gebyok',
                'title'    => 'Dari Balik Pahatan: Satu Minggu Mengerjakan Gebyok',
                'excerpt'  => 'Catatan harian sanggar selama mengerjakan satu gebyok enam daun, dari menggaris hingga perakitan.',
                'body'     => "Hari pertama selalu dipakai untuk menggaris. Garis tidak boleh salah karena pahat akan mengikuti.\n\nSelama enam hari berikutnya motif naga dibentuk pelan-pelan. Yang paling lama adalah bagian sisik, karena setiap sisik harus dalam dengan kedalaman yang sama supaya bayangan cahayanya rata.\n\nHari terakhir dipakai untuk amplas dan melamik lapis pertama.",
                'cover'    => 'dari-balik-pahatan',
                'status'   => 'published',
                'views'    => 512,
                'days'     => 12,
                'seed'     => 2001,
            ],
            [
                'seller'   => 'gebyok.mandiri',
                'section'  => 'tips',
                'slug'     => 'merawat-kriya-kayu-di-musim-hujan',
                'title'    => 'Merawat Kriya Kayu Agar Tidak Retak di Musim Hujan',
                'excerpt'  => 'Tiga kebiasaan sederhana yang menjaga kayu tetap stabil saat kelembapan naik.',
                'body'     => "Jangan menempelkan kriya kayu langsung ke dinding lembap; sisakan jarak minimal dua sentimeter supaya sirkulasi udara tetap jalan.\n\nLap debu dengan kain lembap, jangan basah. Air yang meresap akan membuat serat kayu membengkak dan kering tidak rata.\n\nSetiap enam bulan, oleskan wax tipis-tipis pada permukaan yang sering disentuh.",
                'cover'    => 'merawat-kriya-kayu',
                'status'   => 'published',
                'views'    => 344,
                'days'     => 27,
                'seed'     => 2002,
            ],
            [
                'seller'   => 'tenun.magelang',
                'section'  => 'berita',
                'slug'     => 'tenun-magelang-pameran-kriya-nusantara',
                'title'    => 'Tenun Magelang Masuk Pameran Kriya Nusantara',
                'excerpt'  => 'Tujuh kain pilihan kami dibawa ke pameran tahunan di Jakarta pada bulan depan.',
                'body'     => "Pameran Kriya Nusantara tahun ini memilih dua puluh sanggar dari luar Jawa dan Jawa Tengah, dan Tenun Magelang termasuk di dalamnya.\n\nKami akan membawa tujuh kain, dua di antaranya masih dalam proses penyelesaian pinggiran. Pengunjung bisa melihat alat tenun kami bekerja langsung di stan.",
                'cover'    => 'pameran-kriya-nusantara',
                'status'   => 'published',
                'views'    => 189,
                'days'     => 5,
                'seed'     => 2003,
            ],
            [
                'seller'   => 'tenun.magelang',
                'section'  => 'proses',
                'slug'     => 'catatan-pewarnaan-alami',
                'title'    => 'Catatan Pewarnaan Alami (draf)',
                'excerpt'  => 'Masih menunggu hasil uji cuci ketiga.',
                'body'     => 'Draf: hasil uji cuci ketiga belum selesai.',
                'cover'    => null,
                'status'   => 'draft',
                'views'    => 0,
                'days'     => 2,
                'seed'     => 2004,
            ],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($posts as $post) {
            $published = $post['status'] === 'published';

            $this->insert('blog_posts', [
                'seller_id'    => $this->users[$post['seller']],
                'category_id'  => $this->blogCategory($post['section']),
                'slug'         => $post['slug'],
                'title'        => $post['title'],
                'excerpt'      => $post['excerpt'],
                'body'         => $post['body'],
                'cover_path'   => $post['cover'] === null
                    ? null
                    : $this->image('demo/blog/' . $post['cover'] . '.png', 960, 540, $post['seed']),
                'status'       => $post['status'],
                'is_published' => $published ? 1 : 0,
                'view_count'   => $post['views'],
                'published_at' => $published
                    ? date('Y-m-d H:i:s', strtotime('-' . $post['days'] . ' days'))
                    : null,
                'created_at'   => date('Y-m-d H:i:s', strtotime('-' . ($post['days'] + 2) . ' days')),
                'updated_at'   => $now,
            ]);
        }
    }

    /**
     * @var array<string, int>
     */
    private array $blogCategories = [];

    private function blogCategory(string $slug): int
    {
        if ($this->blogCategories === []) {
            foreach ($this->db->table('blog_categories')->get()->getResultArray() as $row) {
                $this->blogCategories[(string) $row['slug']] = (int) $row['id'];
            }
        }

        return $this->blogCategories[$slug];
    }

    // ------------------------------------------------------------------
    // Customer, cart and order
    // ------------------------------------------------------------------

    private function seedCustomer(): void
    {
        $customerId = $this->users['sari.pembeli'];
        $now        = date('Y-m-d H:i:s');

        $this->insert('user_profiles', [
            'user_id'         => $customerId,
            'full_name'       => 'Sari Wulandari',
            'nickname'        => 'Sari',
            'bio'             => 'Kolektor kriya kayu dan penikmat kopi manual.',
            'birth_date'      => '1994-06-11',
            'gender'          => 'P',
            'identity_status' => 'terverifikasi',
            'orders_total'    => 2,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $this->insert('addresses', [
            'user_id'          => $customerId,
            'label'            => 'Rumah',
            'recipient_name'   => 'Sari Wulandari',
            'recipient_phone'  => '081223344556',
            'address_line'     => 'Jl. Kaliurang KM 6 No. 12, Condongcatur',
            'village'          => 'Condongcatur',
            'district'         => 'Depok',
            'regency'          => 'Sleman',
            'province'         => 'DI Yogyakarta',
            'postal_code'      => '55283',
            'latitude'         => '-7.7467000',
            'longitude'        => '110.4051000',
            'landmark'         => 'UGM Gate 3',
            'delivery_notes'   => 'Rumah pagar hijau, titip ke satpam bila kosong.',
            'is_default'       => 1,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    /**
     * One settled order per seller, each with its items, shipment, status
     * timeline and a review, so every storefront has real sales and real
     * ratings behind its counters.
     */
    private function seedOrder(): void
    {
        $customerId = $this->users['sari.pembeli'];

        $settled = [
            'gebyok.mandiri' => [
                'number'   => 'LKP-' . date('Y') . '-000140',
                'days'     => 16,
                'shipping' => 350000,
                'courier'  => ['jne', 'JNE Regular', 'REG', 'JNE001234567890'],
                'lines'    => [
                    ['gebyok-jati-ukir-naga', 1, 4500000, 'Mohon diplester dulu sebelum dikirim.'],
                    ['relif-ukir-motif-klasik', 2, 1250000, null],
                ],
                'review'   => [
                    'product' => 'gebyok-jati-ukir-naga',
                    'title'   => 'Ukirannya rapi sekali',
                    'body'    => 'Datang dengan packing kayu, tidak ada lecet sama sekali. Motif naganya persis seperti foto.',
                ],
            ],
            'tenun.magelang' => [
                'number'   => 'LKP-' . date('Y') . '-000141',
                'days'     => 11,
                'shipping' => 60000,
                'courier'  => ['jnt', 'J&T Express', 'EZ', 'JT881234567890'],
                'lines'    => [
                    ['tenun-ikat-sogan-magelang', 1, 850000, null],
                    ['selendang-tenun-eksklusif', 1, 650000, null],
                ],
                'review'   => [
                    'product' => 'tenun-ikat-sogan-magelang',
                    'title'   => 'Warnanya lebih pekat dari fotonya',
                    'body'    => 'Sutera halus dan pinggirannya rapi. Sampai dua hari lebih cepat dari estimasi.',
                ],
            ],
            'keramik.magelang' => [
                'number'   => 'LKP-' . date('Y') . '-000142',
                'days'     => 8,
                'shipping' => 45000,
                'courier'  => ['sicepat', 'SiCepat Regular', 'REG', 'SC009876543210'],
                'lines'    => [
                    ['teko-keramik-daur-ulang', 2, 320000, 'Bisa dibungkus kado?'],
                ],
                'review'   => [
                    'product' => 'teko-keramik-daur-ulang',
                    'title'   => 'Glasirnya awet',
                    'body'    => 'Sudah dicuci puluhan kali warnanya tidak berubah, pegangan teko juga tidak panas.',
                ],
            ],
        ];

        foreach ($settled as $seller => $spec) {
            $this->seedSettledOrder($customerId, $seller, $spec);
        }

        $this->seedActiveOrder($customerId);
        $this->seedCart($customerId);
    }

    /**
     * @param array{number: string, days: int, shipping: int, courier: array{0: string, 1: string, 2: string, 3: string}, lines: list<array{0: string, 1: int, 2: int, 3: string|null}>, review: array{product: string, title: string, body: string}} $spec
     */
    private function seedSettledOrder(int $customerId, string $seller, array $spec): void
    {
        $sellerId = $this->users[$seller];
        $placedAt = date('Y-m-d H:i:s', strtotime('-' . $spec['days'] . ' days'));
        $now      = date('Y-m-d H:i:s');

        $subtotal = 0;

        foreach ($spec['lines'] as [$slug, $quantity, $unitPrice]) {
            $subtotal += $unitPrice * $quantity;
        }

        // Money stays in whole rupiah: an exact 5% fee is `subtotal / 20`.
        $fee = intdiv($subtotal, 20);

        // `$days` is how long ago the order was placed. Everything after it
        // happens inside those days and moves forward in time: work starts a
        // few hours later, the piece is ready at D-5 and it reaches the
        // customer at D-8, so the history never reads backwards.
        $ago = static fn (int $daysAgo): string => date('Y-m-d H:i:s', strtotime('-' . $daysAgo . ' days'));
        $started = date('Y-m-d H:i:s', strtotime('+3 hours', strtotime($placedAt)));

        $orderId = $this->insert('orders', [
            'order_number'            => $spec['number'],
            'customer_id'             => $customerId,
            'seller_id'               => $sellerId,
            'status'                  => 'completed',
            'fulfillment_type'        => 'ship',
            'production_progress'     => 100,
            'payment_method'          => 'manual',
            'payment_status'          => 'paid',
            'currency'                => 'IDR',
            'subtotal'                => $subtotal,
            'shipping_total'          => $spec['shipping'],
            'grand_total'             => $subtotal + $spec['shipping'],
            'platform_fee'            => $fee,
            'seller_earning'          => $subtotal + $spec['shipping'] - $fee,
            'ship_recipient_name'     => 'Sari Wulandari',
            'ship_recipient_phone'    => '081223344556',
            'ship_address_line'       => 'Jl. Kaliurang KM 6 No. 12, Condongcatur',
            'ship_village'            => 'Condongcatur',
            'ship_district'           => 'Depok',
            'ship_regency'            => 'Sleman',
            'ship_province'           => 'DI Yogyakarta',
            'ship_postal_code'        => '55283',
            'ship_latitude'           => '-7.7467000',
            'ship_longitude'          => '110.4051000',
            'ship_landmark'           => 'UGM Gate 3',
            'ship_notes'              => 'Rumah pagar hijau, titip ke satpam bila kosong.',
            'courier_code'            => $spec['courier'][0],
            'courier_name'            => $spec['courier'][1],
            'tracking_number'         => $spec['courier'][3],
            'estimated_delivery_at'   => date('Y-m-d', strtotime('-' . ($spec['days'] - 7) . ' days')),
            'customer_note'           => $spec['lines'][0][3] ?? null,
            'placed_at'               => $placedAt,
            'paid_at'                 => $placedAt,
            'shipped_at'              => $ago($spec['days'] - 6),
            'delivered_at'            => $ago($spec['days'] - 7),
            'completed_at'            => $ago($spec['days'] - 8),
            'created_at'              => $placedAt,
            'updated_at'              => $now,
        ]);

        $reviewItemId = 0;

        foreach ($spec['lines'] as [$slug, $quantity, $unitPrice, $note]) {
            $product = $this->productBySlug($slug);

            $itemId = $this->insert('order_items', [
                'order_id'      => $orderId,
                'product_id'    => $product['id'],
                'variant_id'    => null,
                'product_name'  => $product['name'],
                'variant_label' => null,
                'sku'           => null,
                'image_path'    => $this->firstImage($product['id']),
                'unit_price'    => $unitPrice,
                'quantity'      => $quantity,
                'subtotal'      => $unitPrice * $quantity,
                'note'          => $note,
                'created_at'    => $placedAt,
            ]);

            if ($slug === $spec['review']['product']) {
                $reviewItemId = $itemId;
            }
        }

        $this->insert('order_shipments', [
            'order_id'        => $orderId,
            'courier_code'    => $spec['courier'][0],
            'courier_name'    => $spec['courier'][1],
            'service_level'   => $spec['courier'][2],
            'tracking_number' => $spec['courier'][3],
            'shipped_at'      => $ago($spec['days'] - 6),
            'delivered_at'    => $ago($spec['days'] - 7),
            'created_at'      => $ago($spec['days'] - 6),
        ]);

        $timeline = [
            [null, 'pending_payment', 'Pesanan dibuat.', $customerId, $placedAt],
            ['pending_payment', 'awaiting_artisan', 'Pembayaran diterima, pesanan diteruskan ke sanggar.', $customerId, $placedAt],
            ['awaiting_artisan', 'in_production', 'Mulai dikerjakan di sanggar.', $sellerId, $started],
            ['in_production', 'ready_to_ship', 'Selesai dan siap dikemas.', $sellerId, $ago($spec['days'] - 5)],
            ['ready_to_ship', 'shipped', 'Diserahkan ke ' . $spec['courier'][1] . '.', $sellerId, $ago($spec['days'] - 6)],
            ['shipped', 'delivered', 'Diterima penerima di Sleman.', $customerId, $ago($spec['days'] - 7)],
            ['delivered', 'completed', 'Pesanan diselesaikan.', $customerId, $ago($spec['days'] - 8)],
        ];

        foreach ($timeline as [$from, $to, $note, $actor, $at]) {
            $this->insert('order_status_history', [
                'order_id'    => $orderId,
                'from_status' => $from,
                'to_status'   => $to,
                'note'        => $note,
                'actor_id'    => $actor,
                'created_at'  => $at,
            ]);
        }

        $reviewProduct = $this->productBySlug($spec['review']['product']);

        $this->insert('reviews', [
            'product_id'    => $reviewProduct['id'],
            'order_id'      => $orderId,
            'order_item_id' => $reviewItemId,
            'customer_id'   => $customerId,
            'seller_id'     => $sellerId,
            'rating'        => 5,
            'title'         => $spec['review']['title'],
            'body'          => $spec['review']['body'],
            'variant_label' => null,
            'status'        => 'published',
            'created_at'    => $ago($spec['days'] - 8),
            'updated_at'    => $ago($spec['days'] - 8),
        ]);
    }

    /**
     * A younger order still with the artisan, so the seller's "to ship" bucket
     * and the customer's active-order screen are both non-empty.
     */
    private function seedActiveOrder(int $customerId): void
    {
        $sellerId = $this->users['keramik.magelang'];
        $product  = $this->productBySlug('teko-keramik-daur-ulang');
        $placedAt = date('Y-m-d H:i:s', strtotime('-3 days'));

        $orderId = $this->insert('orders', [
            'order_number'         => 'LKP-' . date('Y') . '-000143',
            'customer_id'          => $customerId,
            'seller_id'            => $sellerId,
            'status'               => 'in_production',
            'fulfillment_type'     => 'ship',
            'production_progress'  => 40,
            'payment_method'       => 'manual',
            'payment_status'       => 'paid',
            'currency'             => 'IDR',
            'subtotal'             => 640000,
            'shipping_total'       => 45000,
            'grand_total'          => 685000,
            'platform_fee'         => 32000,
            'seller_earning'       => 653000,
            'ship_recipient_name'  => 'Sari Wulandari',
            'ship_recipient_phone' => '081223344556',
            'ship_address_line'    => 'Jl. Kaliurang KM 6 No. 12, Condongcatur',
            'ship_village'         => 'Condongcatur',
            'ship_district'        => 'Depok',
            'ship_regency'         => 'Sleman',
            'ship_province'        => 'DI Yogyakarta',
            'ship_postal_code'     => '55283',
            'ship_latitude'        => '-7.7467000',
            'ship_longitude'       => '110.4051000',
            'customer_note'        => 'Bisa dikirim akhir pekan saja.',
            'placed_at'            => $placedAt,
            'paid_at'              => $placedAt,
            'created_at'           => $placedAt,
            'updated_at'           => date('Y-m-d H:i:s'),
        ]);

        $this->insert('order_items', [
            'order_id'      => $orderId,
            'product_id'    => $product['id'],
            'variant_id'    => null,
            'product_name'  => $product['name'],
            'image_path'    => $this->firstImage($product['id']),
            'unit_price'    => 320000,
            'quantity'      => 2,
            'subtotal'      => 640000,
            'created_at'    => $placedAt,
        ]);

        $timeline = [
            [null, 'pending_payment', 'Pesanan dibuat.', $customerId, $placedAt],
            ['pending_payment', 'awaiting_artisan', 'Pembayaran diterima.', $customerId, $placedAt],
            ['awaiting_artisan', 'in_production', 'Sedang dibakar pada tungku pertama.', $sellerId, date('Y-m-d H:i:s', strtotime('-2 days'))],
        ];

        foreach ($timeline as [$from, $to, $note, $actor, $at]) {
            $this->insert('order_status_history', [
                'order_id'    => $orderId,
                'from_status' => $from,
                'to_status'   => $to,
                'note'        => $note,
                'actor_id'    => $actor,
                'created_at'  => $at,
            ]);
        }
    }

    private function seedCart(int $customerId): void
    {
        $product = $this->db->table('products')->where('slug', 'vas-keramik-tone-on-tone')->get()->getRowArray();
        $now     = date('Y-m-d H:i:s');

        $cartId = $this->insert('carts', [
            'customer_id' => $customerId,
            'status'      => 'active',
            'currency'    => 'IDR',
            'updated_at'  => $now,
        ]);

        $this->insert('cart_items', [
            'cart_id'    => $cartId,
            'seller_id'  => (int) $product['seller_id'],
            'product_id' => (int) $product['id'],
            'variant_id' => null,
            'quantity'   => 1,
            'unit_price' => (int) $product['price'],
            'note'       => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // ------------------------------------------------------------------
    // Derived counters
    // ------------------------------------------------------------------

    /**
     * Recompute every "sold", "orders" and "rating" counter from the rows this
     * seeder just wrote.
     *
     * Hand-written counters are how a demo dataset ends up claiming 61 sales
     * against three orders, or a rating average with no review behind it.
     * Deriving them keeps the catalog cards, the storefront header, the review
     * list and the dashboard telling the same story.
     */
    private function reconcileAggregates(): void
    {
        // A sale is recognised once the parcel ships, which is when
        // `earningsBetween()` starts counting it as revenue.
        $salesStatuses = ['shipped', 'delivered', 'completed'];

        $orders = [];

        foreach ($this->db->table('orders')->get()->getResultArray() as $row) {
            $orders[(int) $row['id']] = $row;
        }

        $unitsSold   = [];
        $ordersCount = [];
        $sellerUnits = [];

        foreach ($this->db->table('order_items')->get()->getResultArray() as $row) {
            $order = $orders[(int) $row['order_id']] ?? null;

            if ($order === null) {
                continue;
            }

            $productId = (int) $row['product_id'];
            $sellerId  = (int) $order['seller_id'];

            $ordersCount[$productId][(int) $row['order_id']] = true;

            if (! in_array($order['status'], $salesStatuses, true)) {
                continue;
            }

            $quantity = (int) $row['quantity'];

            $unitsSold[$productId]  = ($unitsSold[$productId] ?? 0) + $quantity;
            $sellerUnits[$sellerId] = ($sellerUnits[$sellerId] ?? 0) + $quantity;
        }

        $productRatings = [];
        $sellerRatings  = [];

        $published = $this->db->table('reviews')
            ->where('status', 'published')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        foreach ($published as $row) {
            $rating    = (int) $row['rating'];
            $productId = (int) $row['product_id'];
            $sellerId  = (int) $row['seller_id'];

            $productRatings[$productId]['sum']   = ($productRatings[$productId]['sum'] ?? 0) + $rating;
            $productRatings[$productId]['count'] = ($productRatings[$productId]['count'] ?? 0) + 1;
            $sellerRatings[$sellerId]['sum']     = ($sellerRatings[$sellerId]['sum'] ?? 0) + $rating;
            $sellerRatings[$sellerId]['count']   = ($sellerRatings[$sellerId]['count'] ?? 0) + 1;
        }

        foreach ($this->db->table('products')->get()->getResultArray() as $row) {
            $id = (int) $row['id'];

            $this->db->table('products')->where('id', $id)->update([
                'sold_count'     => $unitsSold[$id] ?? 0,
                'orders_count'   => count($ordersCount[$id] ?? []),
                'rating_count'   => $productRatings[$id]['count'] ?? 0,
                'rating_average' => $this->average($productRatings[$id] ?? null),
            ]);
        }

        foreach ($this->db->table('seller_profiles')->get()->getResultArray() as $row) {
            $userId = (int) $row['user_id'];

            $this->db->table('seller_profiles')->where('id', (int) $row['id'])->update([
                'sold_count'     => $sellerUnits[$userId] ?? 0,
                'rating_count'   => $sellerRatings[$userId]['count'] ?? 0,
                'rating_average' => $this->average($sellerRatings[$userId] ?? null),
            ]);
        }
    }

    /**
     * A rating average as a fixed two-decimal string, or `0.00` with no reviews.
     *
     * @param array{sum: int, count: int}|null $ratings
     */
    private function average(?array $ratings): string
    {
        if ($ratings === null || $ratings['count'] === 0) {
            return '0.00';
        }

        return number_format($ratings['sum'] / $ratings['count'], 2, '.', '');
    }

    // ------------------------------------------------------------------
    // Analytics
    // ------------------------------------------------------------------

    /**
     * Three weeks of counters so the seller dashboard reports visits, product
     * views and chat starts instead of zeros.
     *
     * Traffic is a plausible daily curve; orders and revenue are read back from
     * the orders already written, so the dashboard cannot report a sale that
     * does not exist.
     */
    private function seedDailyStats(): void
    {
        $now    = date('Y-m-d H:i:s');
        $perDay = $this->orderStatsByDay();

        foreach (['gebyok.mandiri', 'tenun.magelang', 'keramik.magelang'] as $offset => $username) {
            $sellerId = $this->users[$username];
            $lead     = 11 + $offset * 3;

            for ($days = 17; $days >= 0; $days--) {
                $date   = date('Y-m-d', strtotime('-' . $days . ' days'));
                $wiggle = (int) (($days + $offset) % 5);
                $actual = $perDay[$date][$sellerId] ?? null;

                $this->insert('seller_daily_stats', [
                    'seller_id'          => $sellerId,
                    'stat_date'          => $date,
                    'visit_count'        => 40 + $wiggle * 9 + $lead,
                    'visitor_count'      => 32 + $wiggle * 7 + $lead,
                    'product_view_count' => 95 + $wiggle * 14 + $lead * 2,
                    'chat_started_count' => 2 + ($wiggle % 3),
                    'order_count'        => $actual['orders'] ?? 0,
                    'revenue_total'      => $actual['revenue'] ?? 0,
                    'completed_count'    => $actual['completed'] ?? 0,
                    'top_product_id'     => null,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]);
            }
        }
    }

    /**
     * `shipped_at` date, then seller id, to the figures `seller_daily_stats`
     * holds for that day.
     *
     * @return array<string, array<int, array{orders: int, revenue: int, completed: int}>>
     */
    private function orderStatsByDay(): array
    {
        $byDay = [];

        foreach ($this->db->table('orders')->get()->getResultArray() as $order) {
            if ($order['shipped_at'] === null) {
                continue;
            }

            $day      = substr((string) $order['shipped_at'], 0, 10);
            $sellerId = (int) $order['seller_id'];

            $byDay[$day][$sellerId]['orders']    = ($byDay[$day][$sellerId]['orders'] ?? 0) + 1;
            $byDay[$day][$sellerId]['revenue']   = ($byDay[$day][$sellerId]['revenue'] ?? 0) + (int) $order['seller_earning'];
            $byDay[$day][$sellerId]['completed'] = ($byDay[$day][$sellerId]['completed'] ?? 0)
                + ($order['status'] === 'completed' ? 1 : 0);
        }

        return $byDay;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function isSeeded(): bool
    {
        return $this->db->table('users')
            ->where('username', self::MARKER)
            ->countAllResults() > 0;
    }

    /**
     * Insert one row and return its generated id, with the connection's prefix
     * applied so the same call works on the prefixed test database.
     *
     * @param array<string, mixed> $data
     */
    private function insert(string $table, array $data): int
    {
        $this->db->table($table)->insert($data);

        return (int) $this->db->insertID();
    }

    private function firstImage(int $productId): ?string
    {
        $row = $this->db->table('product_images')
            ->where('product_id', $productId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('position', 'ASC')
            ->get()
            ->getRowArray();

        return $row === null ? null : (string) $row['file_path'];
    }

    /**
     * A product row looked up by slug, with the generated id cast to an int.
     *
     * @return array<string, mixed>
     */
    private function productBySlug(string $slug): array
    {
        $row = $this->db->table('products')->where('slug', $slug)->get()->getRowArray();

        if ($row === null) {
            throw new \RuntimeException('Demo product missing: ' . $slug);
        }

        $row['id'] = (int) $row['id'];

        return $row;
    }

    /**
     * Generate a deterministic pattern tile under `writable/uploads/`.
     *
     * The path is derived from the caller's slug, so a re-run reuses the file
     * instead of piling up new ones. Anything already on disk is left alone.
     */
    private function image(string $relative, int $width, int $height, int $seed): string
    {
        $absolute = WRITEPATH . 'uploads/' . ltrim($relative, '/');

        if (is_file($absolute)) {
            return $relative;
        }

        $directory = dirname($absolute);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return $relative;
        }

        mt_srand($seed);

        $palette = self::PALETTE;
        $base    = $palette[$seed % count($palette)];
        $accent  = $palette[($seed + 3) % count($palette)];
        $light   = [min(255, $base[0] + 96), min(255, $base[1] + 96), min(255, $base[2] + 96)];

        $image = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image, $base[0], $base[1], $base[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        // A woven grid: every other cell is skipped, and a third are dropped
        // at random, which reads as cloth rather than a checkerboard.
        $cell = max(24, intdiv($width, 16));
        $tone = imagecolorallocate($image, $accent[0], $accent[1], $accent[2]);

        for ($y = 0; $y < $height; $y += $cell) {
            for ($x = 0; $x < $width; $x += $cell) {
                if ((intdiv($x, $cell) + intdiv($y, $cell)) % 2 !== 0 || mt_rand(0, 2) === 0) {
                    continue;
                }

                imagefilledrectangle(
                    $image,
                    $x,
                    $y,
                    min($width - 1, $x + $cell - 1),
                    min($height - 1, $y + $cell - 1),
                    $tone
                );
            }
        }

        // One sheared band gives each tile a direction to read in. GD clips
        // anything outside the canvas, so the corners stay honest.
        $band   = (int) ($height / 3);
        $pale   = imagecolorallocate($image, $light[0], $light[1], $light[2]);
        $offset = mt_rand(0, $width);

        // GD wants a flat list of x/y pairs plus an explicit point count.
        imagepolygon($image, [
            $offset, 0,
            $offset + $band, 0,
            $offset + $band - intdiv($height, 2), $height,
            $offset - intdiv($height, 2), $height,
        ], 4, $pale);

        // An outline, not a fill, so the woven grid stays visible.
        $inset = (int) max(4, min($width, $height) / 48);
        imagerectangle(
            $image,
            $inset,
            $inset,
            $width - $inset - 1,
            $height - $inset - 1,
            $pale
        );

        imagepng($image, $absolute);
        imagedestroy($image);
        mt_srand();

        return $relative;
    }

    private function note(string $message): void
    {
        if (! $this->silent && is_cli()) {
            CLI::write($message, 'green');
        }
    }
}
