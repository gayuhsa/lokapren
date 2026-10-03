<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\RuntimeException;

/**
 * Turns a cart into an order that is ready to be paid.
 *
 * This class exists to enforce one rule: every amount on the resulting order is
 * recomputed here from `product_variants`, `shipping_methods` and
 * `promo_codes`. Submitted prices, totals, discounts, fees and store ids are
 * never read, because a browser can send any of them.
 *
 * Order lines keep the seller that has to fulfil them, which is what lets each
 * store see only its own part of a multi-store order.
 */
final class Checkout
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly Cart $cart,
        private readonly Shipping $shipping,
    ) {
    }

    /**
     * Everything the checkout page needs, with no amount taken from the request.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function quote(int $userId): array
    {
        $cart    = $this->cart->forUser($userId);
        $methods = $this->shipping->methods();
        $code    = $this->storedPromo($userId);
        $promo   = null;

        if ($code !== null) {
            try {
                $promo = $this->shipping->promo($code, $cart['subtotal']);
            } catch (RuntimeException) {
                $promo = null;
            }
        }

        $selected = $methods[0] ?? null;

        return [
            'cart'      => $cart,
            'methods'   => $methods,
            'promoCode' => $code,
            'promo'     => $promo,
            'totals'    => $this->totals($cart['subtotal'], $selected === null ? 0 : (int) $selected['cost'], $promo),
        ];
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array<string, mixed> The created order row.
     *
     * @throws RuntimeException
     */
    public function place(
        int $userId,
        array $address,
        string $shippingCode,
        string $paymentCode,
        string $courierNote = '',
    ): array {
        $cart    = $this->cart->forUser($userId);
        $method  = $this->shipping->require($shippingCode);
        $payment = $this->paymentMethod($paymentCode);

        if ($cart['items'] === []) {
            throw new RuntimeException('Keranjang masih kosong.');
        }

        foreach ($cart['items'] as $item) {
            if (! $item['sellable']) {
                throw new RuntimeException($item['product_name'] . ' belum bisa dipesan. Perbarui keranjangmu.');
            }
        }

        $address = $this->validateAddress($address);

        $code  = $this->storedPromo($userId);
        $promo = null;

        if ($code !== null) {
            try {
                $promo = $this->shipping->promo($code, $cart['subtotal']);
            } catch (RuntimeException) {
                // A code that no longer qualifies is dropped rather than
                // blocking the customer who has a valid cart in front of them.
                $this->cart->applyPromo($userId, null);
                $code  = null;
            }
        }

        $totals = $this->totals($cart['subtotal'], (int) $method['cost'], $promo);

        $this->db->transBegin();

        try {
            foreach ($cart['items'] as $item) {
                $this->reserveStock((int) $item['product_variant_id'], (int) $item['qty'], (string) $item['variant_name']);
            }

            $orderId = $this->insertOrder($userId, $address, $totals, $courierNote);

            foreach ($cart['items'] as $item) {
                $this->insertOrderItem($orderId, $item);
            }

            $this->insertDelivery($orderId, $method, (int) $totals['shipping_fee']);
            $this->insertPayment($orderId, $payment, (int) $totals['grand_total']);

            if ($promo !== null) {
                $this->consumePromo($promo['code'], $orderId);
            }

            $this->db->transCommit();
        } catch (RuntimeException $e) {
            $this->db->transRollback();

            throw $e;
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', $e->getMessage());

            throw new RuntimeException('Checkout gagal, silakan coba lagi.', 0, $e);
        }

        $this->cart->clear($userId);
        $this->cart->applyPromo($userId, null);

        return $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();
    }

    /**
     * Totals derived only from trusted rows.
     *
     * @param array{code: string, discount: int, label: string}|null $promo
     *
     * @return array<string, mixed>
     */
    public function totals(int $subtotal, int $shippingFee, ?array $promo): array
    {
        $discount = min($promo['discount'] ?? 0, $subtotal);

        return [
            'subtotal'       => $subtotal,
            'shipping_fee'   => $shippingFee,
            'discount'       => $discount,
            'discount_label' => $promo['label'] ?? null,
            'promo_code'     => $promo['code'] ?? null,
            'tax'            => 0,
            'grand_total'    => max(0, $subtotal + $shippingFee - $discount),
            'saved'          => $discount,
        ];
    }

    private function storedPromo(int $userId): ?string
    {
        $cart = $this->db->table('carts')->where('user_id', $userId)->get()->getRowArray();

        $stored = $cart['promo_code'] ?? null;

        return is_string($stored) && trim($stored) !== '' ? trim($stored) : null;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function paymentMethod(string $code): array
    {
        $payment = $this->db->table('payment_methods')
            ->where('code', $code)
            ->where('is_active', true)
            ->get()
            ->getRowArray();

        if ($payment === null) {
            throw new RuntimeException('Metode pembayaran tidak tersedia.');
        }

        return $payment;
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array<string, string>
     *
     * @throws RuntimeException
     */
    private function validateAddress(array $address): array
    {
        $clean = [
            'recipient_name'  => trim((string) ($address['recipient_name'] ?? '')),
            'recipient_phone' => trim((string) ($address['recipient_phone'] ?? '')),
            'address_line'    => trim((string) ($address['address_line'] ?? '')),
            'subdistrict'     => trim((string) ($address['subdistrict'] ?? '')),
            'city'            => trim((string) ($address['city'] ?? '')),
            'postal_code'     => trim((string) ($address['postal_code'] ?? '')),
        ];

        foreach (['recipient_name', 'recipient_phone', 'address_line', 'city'] as $required) {
            if ($clean[$required] === '') {
                throw new RuntimeException('Lengkapi data pengiriman terlebih dahulu.');
            }
        }

        if (mb_strlen($clean['recipient_name']) > 150 || mb_strlen($clean['address_line']) > 255) {
            throw new RuntimeException('Alamat terlalu panjang untuk dicatat.');
        }

        return $clean;
    }

    /**
     * Decrements stock with the guard inside the same statement, so two
     * customers checking out the last relief at once cannot both win.
     *
     * @throws RuntimeException
     */
    private function reserveStock(int $variantId, int $qty, string $variantName): void
    {
        $affected = $this->db->table('product_variants')
            ->set('stock', 'stock - ' . $qty, false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->where('id', $variantId)
            ->where('stock >=', $qty)
            ->update();

        if ($affected === 0) {
            throw new RuntimeException(
                'Stok ' . $variantName . ' baru saja habis. Perbarui keranjang untuk melanjutkan.',
            );
        }
    }

    /**
     * @param array<string, string> $address
     * @param array<string, mixed>  $totals
     *
     * @throws RuntimeException
     */
    private function insertOrder(int $userId, array $address, array $totals, string $courierNote): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('orders')->insert([
            'order_code'      => $this->orderCode(),
            'customer_id'     => $userId,
            'status'          => 'pending',
            'subtotal'        => $totals['subtotal'],
            'delivery_fee'    => $totals['shipping_fee'],
            'discount_amount' => $totals['discount'],
            'tax_amount'      => $totals['tax'],
            'total'           => $totals['grand_total'],
            'recipient_name'  => $address['recipient_name'],
            'recipient_phone' => $address['recipient_phone'],
            'address_line'    => $address['address_line'],
            'subdistrict'     => $address['subdistrict'] !== '' ? $address['subdistrict'] : null,
            'city'            => $address['city'],
            'postal_code'     => $address['postal_code'] !== '' ? $address['postal_code'] : null,
            'courier_note'    => $courierNote !== '' ? mb_substr($courierNote, 0, 500) : null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $item
     */
    private function insertOrderItem(int $orderId, array $item): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('order_items')->insert([
            'order_id'           => $orderId,
            'store_id'           => (int) $item['store_id'],
            'product_id'         => (int) $item['product_id'],
            'product_variant_id' => (int) $item['product_variant_id'],
            'product_name'       => $item['product_name'],
            'variant_name'       => $item['variant_name'],
            'unit_price'         => (int) $item['unit_price'],
            'qty'                => (int) $item['qty'],
            'line_total'         => (int) $item['line_total'],
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $method
     */
    private function insertDelivery(int $orderId, array $method, int $cost): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('order_deliveries')->insert([
            'order_id'      => $orderId,
            'service_type'  => (string) $method['code'],
            'courier_label' => (string) $method['name'],
            'cost'          => $cost,
            'eta_minutes'   => $method['eta_minutes'] !== null ? (int) $method['eta_minutes'] : null,
            'status'        => 'pending',
            'progress'      => 0,
            'requested_at'  => $now,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function insertPayment(int $orderId, array $payment, int $amount): void
    {
        $now    = date('Y-m-d H:i:s');
        $expiry = $payment['expires_minutes'] !== null ? (int) $payment['expires_minutes'] : null;

        $this->db->table('order_payments')->insert([
            'order_id'          => $orderId,
            'payment_method_id' => (int) $payment['id'],
            'method_code'       => (string) $payment['code'],
            'status'            => 'pending',
            'amount'            => $amount,
            'reference'         => $payment['code'] === 'qris' ? 'QRIS-' . strtoupper(bin2hex(random_bytes(5))) : null,
            'expires_at'        => $expiry === null ? null : date('Y-m-d H:i:s', time() + $expiry * 60),
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);
    }

    private function consumePromo(string $code, int $orderId): void
    {
        $promo = $this->db->table('promo_codes')->where('code', $code)->get()->getRowArray();

        if ($promo === null) {
            return;
        }

        $this->db->table('promo_codes')
            ->where('id', (int) $promo['id'])
            ->update([
                'used_count' => (int) $promo['used_count'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->db->table('order_promos')->insert([
            'order_id'      => $orderId,
            'promo_code_id' => (int) $promo['id'],
        ]);
    }

    private function orderCode(): string
    {
        $sequence = 0;

        do {
            $code = sprintf('LKP-%s-%04d', date('Ymd'), ++$sequence);
        } while ($this->db->table('orders')->where('order_code', $code)->get()->getRowArray() !== null);

        return $code;
    }
}