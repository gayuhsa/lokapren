<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Creates the checkout reference rows that CheckoutOptionSeeder would seed in
 * production, so tests exercise real fee and discount logic instead of mocking
 * it away.
 *
 * @internal
 */
trait CreatesCheckoutOptions
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeShippingMethod(string $code, int $cost, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'code'         => $code,
            'name'         => ucwords(str_replace('_', ' ', $code)),
            'description'  => 'Layanan uji.',
            'eta_text'     => '1-3 Hari Kerja',
            'eta_minutes'  => 4320,
            'packing_text' => 'Besek Bambu',
            'cost'         => $cost,
            'sort_order'   => 0,
            'is_active'    => true,
            'created_at'   => $now,
            'updated_at'   => $now,
        ], $overrides);

        $this->db->table('shipping_methods')->insert($row);

        return $this->db->table('shipping_methods')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makePaymentMethod(string $code, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'code'            => $code,
            'name'            => ucwords(str_replace('_', ' ', $code)),
            'description'     => 'Metode uji.',
            'instructions'    => 'Ikuti instruksi metode uji.',
            'fee'             => 0,
            'expires_minutes' => 60,
            'sort_order'      => 0,
            'is_active'       => true,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $overrides);

        $this->db->table('payment_methods')->insert($row);

        return $this->db->table('payment_methods')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makePromo(string $code, array $overrides = []): array
    {
        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'code'             => $code,
            'discount_amount'  => 0,
            'discount_percent' => 0,
            'min_subtotal'     => 0,
            'max_uses'         => null,
            'used_count'       => 0,
            'is_active'        => true,
            'created_at'       => $now,
            'updated_at'       => $now,
        ], $overrides);

        $this->db->table('promo_codes')->insert($row);

        return $this->db->table('promo_codes')->where('code', $code)->get()->getRowArray();
    }

    /**
     * A usable address payload for the place-order endpoint.
     *
     * @return array<string, string>
     */
    protected function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'recipient_name'  => 'Budi Santoso',
            'recipient_phone' => '081234567890',
            'address_line'    => 'Jl. Raya Borobudur No. 12',
            'subdistrict'     => 'Borobudur',
            'city'            => 'Muntilan',
            'postal_code'     => '56473',
            'courier_note'    => '',
        ], $overrides);
    }
}