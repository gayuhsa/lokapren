<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Seeds the delivery options and payment methods the checkout design offers.
 *
 * Fees and codes are reference data, not user input, so they live in tables
 * rather than being hard-coded in the view. Idempotent on `code`.
 *
 * Run with: php spark db:seed CheckoutOptionSeeder
 */
class CheckoutOptionSeeder extends LokaprenSeeder
{
    private const SHIPPING = [
        [
            'code'         => 'pesan_antar_terima',
            'name'         => 'Pesan Antar Terima',
            'description'  => 'Pengantaran langsung dari bilik sanggar perajin ke penginapan atau rumah Anda di kawasan Borobudur, Muntilan, dan Magelang kota dengan armada ramah lingkungan.',
            'eta_text'     => 'Tiba 1-3 Hari Kerja',
            'eta_minutes'  => 4320,
            'packing_text' => 'Besek Bambu + Karton Khusus',
            'cost'         => 20000,
        ],
        [
            'code'         => 'layanan_istimewa',
            'name'         => 'Layanan Istimewa',
            'description'  => 'Sameday kurir kriya pilihan yang menunggu karya sampai bilik, dengan jam tiba yang disepakati sebelumnya.',
            'eta_text'     => 'Tiba Hari yang Sama (1-3 Jam)',
            'eta_minutes'  => 180,
            'packing_text' => 'Besek Bambu + Karton Khusus + Story Kriya',
            'cost'         => 35000,
        ],
        [
            'code'         => 'ekspedisi_reguler',
            'name'         => 'Ekspedisi Reguler & Kargo Nasional',
            'description'  => 'JNE / J&T Reguler untuk kiriman antar kota, dibungkus bubble wrap standar.',
            'eta_text'     => 'Estimasi 2-3 Hari Kerja',
            'eta_minutes'  => 4320,
            'packing_text' => 'Bubble Wrap Standar',
            'cost'         => 38000,
        ],
        [
            'code'         => 'cargo',
            'name'         => 'Ekspedisi Kargo Kayu & Keramik',
            'description'  => 'Peti kayu khusus untuk pahatan relief batu jati dan gerabah berukuran besar.',
            'eta_text'     => 'Estimasi 3-5 Hari Kerja',
            'eta_minutes'  => 7200,
            'packing_text' => 'Peti Kayu Custom',
            'cost'         => 65000,
        ],
    ];

    private const PAYMENTS = [
        [
            'code'           => 'qris',
            'name'           => 'QRIS',
            'description'    => 'Bayar dari m-Banking atau dompet digital apa pun yang memindai satu QR.',
            'instructions'   => 'Pindai kode QR dari aplikasi m-Banking BCA, Mandiri, BRI, BNI atau dompet digital GoPay, OVO, ShopeePay, DANA, LinkAja tanpa perlu unggah bukti transfer.',
            'fee'            => 0,
            'expires_minutes' => 15,
        ],
        [
            'code'           => 'virtual_account',
            'name'           => 'Virtual Account Bank',
            'description'    => 'Transfer ke nomor rekening virtual yang berlaku 24 jam.',
            'instructions'   => 'Pilih bank BCA, Mandiri, BRI, BNI atau BSI, lalu transfer ke nomor virtual account yang ditampilkan.',
            'fee'            => 4000,
            'expires_minutes' => 1440,
        ],
        [
            'code'           => 'wallet',
            'name'           => 'Dompet Digital Langsung',
            'description'    => 'Konfirmasi lewat push notification aplikasi.',
            'instructions'   => 'Pilih GoPay App, ShopeePay atau OVO lalu selesaikan pembayaran dari notifikasi yang muncul.',
            'fee'            => 0,
            'expires_minutes' => 30,
        ],
    ];

    public function run(): void
    {
        foreach (self::SHIPPING as $index => $method) {
            $id = $this->upsertRow('shipping_methods', 'code', $method['code'], $method + [
                'sort_order' => $index,
                'is_active'  => true,
            ]);

            $this->report(sprintf('  shipping  %-22s %-44s id=%d', $method['code'], $method['name'], $id));
        }

        foreach (self::PAYMENTS as $index => $method) {
            $id = $this->upsertRow('payment_methods', 'code', $method['code'], $method + [
                'sort_order' => $index,
                'is_active'  => true,
            ]);

            $this->report(sprintf('  payment   %-22s %-44s id=%d', $method['code'], $method['name'], $id));
        }

        foreach (self::promos() as $promo) {
            $id = $this->upsertRow('promo_codes', 'code', $promo['code'], $promo + ['is_active' => true]);

            $this->report(sprintf('  promo     %-22s id=%d', $promo['code'], $id));
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function promos(): array
    {
        return [
            [
                'code'            => 'LOKALBANGGA',
                'discount_amount' => 50000,
                'min_subtotal'    => 300000,
                'max_uses'        => null,
            ],
            [
                'code'            => 'MAGELANGKRIYA',
                'discount_amount' => 0,
                'discount_percent' => 10,
                'min_subtotal'    => 500000,
                'max_uses'        => 200,
            ],
        ];
    }
}