<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Exceptions\RuntimeException;

/**
 * Shipping options offered at checkout, and the promo codes that discount them.
 *
 * Fees come from `shipping_methods` and `promo_codes` only. Nothing the browser
 * sends about a price is ever read, so a tampered form cannot lower a fee or
 * invent a discount.
 */
final class Shipping
{
    public function __construct(private readonly \CodeIgniter\Database\ConnectionInterface $db)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function methods(): array
    {
        return $this->db->table('shipping_methods')
            ->where('is_active', true)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('cost', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function method(string $code): ?array
    {
        $method = $this->db->table('shipping_methods')
            ->where('code', $code)
            ->where('is_active', true)
            ->get()
            ->getRowArray();

        return $method ?: null;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function require(string $code): array
    {
        $method = $this->method($code);

        if ($method === null) {
            throw new RuntimeException('Metode pengiriman tidak tersedia.');
        }

        return $method;
    }

    /**
     * Resolves a promo code and returns the discount it would give.
     *
     * @return array{code: string, discount: int, label: string}
     *
     * @throws RuntimeException When the code is unknown, inactive, expired,
     *                          exhausted, or below its minimum subtotal.
     */
    public function promo(string $code, int $subtotal): array
    {
        $code  = trim($code);
        $promo = $this->db->table('promo_codes')->where('code', $code)->get()->getRowArray();

        if ($promo === null || ! (bool) $promo['is_active']) {
            throw new RuntimeException('Kode promo tidak dikenal.');
        }

        $now = date('Y-m-d H:i:s');

        if ($promo['starts_at'] !== null && $promo['starts_at'] > $now) {
            throw new RuntimeException('Kode promo belum berlaku.');
        }

        if ($promo['expires_at'] !== null && $promo['expires_at'] < $now) {
            throw new RuntimeException('Kode promo sudah kedaluwarsa.');
        }

        if ($promo['max_uses'] !== null && (int) $promo['used_count'] >= (int) $promo['max_uses']) {
            throw new RuntimeException('Kuota kode promo ini sudah habis.');
        }

        if ($subtotal < (int) $promo['min_subtotal']) {
            throw new RuntimeException(
                'Kode promo belum terpenuhi untuk Rp' . Rupiah::number($promo['min_subtotal']) . '.',
            );
        }

        $discount = (int) $promo['discount_amount']
            + (int) floor($subtotal * ((int) $promo['discount_percent'] / 100));

        if ($discount <= 0) {
            throw new RuntimeException('Kode promo ini tidak memberi potongan.');
        }

        // A discount can never exceed what is being paid for.
        $discount = min($discount, $subtotal);

        return [
            'code'     => (string) $promo['code'],
            'discount' => $discount,
            'label'    => 'Potongan ' . Rupiah::format($discount),
        ];
    }
}