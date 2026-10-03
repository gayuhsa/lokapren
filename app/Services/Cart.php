<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Exceptions\RuntimeException;

/**
 * The cart a signed-in customer assembles an order from.
 *
 * Every read re-derives prices and stock from `product_variants`, so a cart
 * cannot hold a price the customer did not receive from the catalogue, and a
 * line whose variant was deactivated or whose stock ran out is reported rather
 * than silently repriced.
 */
final class Cart
{
    private const MAX_QTY_PER_LINE = 20;

    public function __construct(private readonly \CodeIgniter\Database\ConnectionInterface $db)
    {
    }

    public function forUser(int $userId): array
    {
        $cartId = $this->cartId($userId);

        return $cartId === null ? $this->emptyCart() : $this->read($cartId);
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     issues: list<string>,
     *     count: int,
     *     subtotal: int,
     *     stores: list<array<string, mixed>>
     * }
     */
    public function emptyCart(): array
    {
        return [
            'items'   => [],
            'issues'  => [],
            'count'   => 0,
            'subtotal' => 0,
            'stores'  => [],
        ];
    }

    /**
     * @throws RuntimeException When the variant cannot be bought at all.
     */
    public function add(int $userId, int $variantId, int $qty = 1): void
    {
        $qty = max(1, min($qty, self::MAX_QTY_PER_LINE));

        $variant = $this->buyableVariant($variantId);

        $cartId = $this->cartId($userId);

        if ($cartId === null) {
            $cartId = $this->openCart($userId);
        }

        $existing = $this->db->table('cart_items')
            ->where('cart_id', $cartId)
            ->where('product_variant_id', $variantId)
            ->get()
            ->getRowArray();

        $wanted = ($existing === null ? 0 : (int) $existing['qty']) + $qty;

        if ($wanted > (int) $variant['stock']) {
            throw new RuntimeException('Stok ' . $variant['name'] . ' tidak cukup untuk jumlah itu.');
        }

        $data = [
            'qty'       => $wanted,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing === null) {
            $this->db->table('cart_items')->insert(array_merge($data, [
                'cart_id'            => $cartId,
                'product_id'         => (int) $variant['product_id'],
                'product_variant_id' => $variantId,
                'store_id'           => (int) $variant['store_id'],
                'created_at'         => date('Y-m-d H:i:s'),
            ]));

            return;
        }

        $this->db->table('cart_items')
            ->where('id', (int) $existing['id'])
            ->where('cart_id', $cartId)
            ->update($data);
    }

    /**
     * @throws RuntimeException When the requested quantity exceeds stock.
     */
    public function setQty(int $userId, int $lineId, int $qty): void
    {
        if ($qty <= 0) {
            $this->remove($userId, $lineId);

            return;
        }

        $qty = min($qty, self::MAX_QTY_PER_LINE);

        $line = $this->ownedLine($userId, $lineId);
        $variant = $this->buyableVariant((int) $line['product_variant_id']);

        if ($qty > (int) $variant['stock']) {
            throw new RuntimeException('Stok ' . $variant['name'] . ' tidak cukup untuk jumlah itu.');
        }

        $this->db->table('cart_items')
            ->where('id', $lineId)
            ->where('cart_id', (int) $line['cart_id'])
            ->update(['qty' => $qty, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function remove(int $userId, int $lineId): void
    {
        $line = $this->ownedLine($userId, $lineId);

        $this->db->table('cart_items')
            ->where('id', $lineId)
            ->where('cart_id', (int) $line['cart_id'])
            ->delete();
    }

    public function clear(int $userId): void
    {
        $cartId = $this->cartId($userId);

        if ($cartId !== null) {
            $this->db->table('cart_items')->where('cart_id', $cartId)->delete();
        }
    }

    public function applyPromo(int $userId, ?string $code): void
    {
        $cartId = $this->cartId($userId);

        if ($cartId === null) {
            return;
        }

        $this->db->table('carts')
            ->where('id', $cartId)
            ->update(['promo_code' => $code, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function count(int $userId): int
    {
        $cartId = $this->cartId($userId);

        if ($cartId === null) {
            return 0;
        }

        $sum = $this->db->table('cart_items')
            ->selectSum('qty')
            ->where('cart_id', $cartId)
            ->get()
            ->getRowArray();

        return (int) ($sum['qty'] ?? 0);
    }

    /**
     * A cart row is only reachable through the signed-in customer who owns it,
     * so one customer cannot read or edit another's line by guessing its id.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function ownedLine(int $userId, int $lineId): array
    {
        $cartId = $this->cartId($userId);

        $line = $cartId === null ? null : $this->db->table('cart_items')
            ->where('id', $lineId)
            ->where('cart_id', $cartId)
            ->get()
            ->getRowArray();

        if ($line === null) {
            throw new RuntimeException('Baris keranjang tidak ditemukan.');
        }

        return $line;
    }

    private function cartId(int $userId): ?int
    {
        $row = $this->db->table('carts')->where('user_id', $userId)->get()->getRowArray();

        return $row === null ? null : (int) $row['id'];
    }

    private function openCart(int $userId): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table('carts')->insert([
            'user_id'    => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function buyableVariant(int $variantId): array
    {
        $variant = $this->db->table('product_variants v')
            ->select(
                'v.id, v.product_id, v.name, v.price, v.stock, v.is_active,'
                . ' p.slug AS product_slug, p.status AS product_status, p.store_id,'
                . ' s.verification_status, s.is_active AS store_active',
            )
            ->join('products p', 'p.id = v.product_id')
            ->join('stores s', 's.id = p.store_id')
            ->where('v.id', $variantId)
            ->get()
            ->getRowArray();

        if ($variant === null) {
            throw new RuntimeException('Varian ini sudah tidak tersedia.');
        }

        // A verified, open store is part of being sellable. Checking it here as
        // well as on read means an unverified seller can never get a line into
        // a cart in the first place.
        if (! (bool) $variant['is_active'] || $variant['product_status'] !== 'active') {
            throw new RuntimeException('Varian ini sudah tidak tersedia.');
        }

        if (! (bool) $variant['store_active'] || $variant['verification_status'] !== 'verified') {
            throw new RuntimeException('Sanggar ini belum terverifikasi di Lokapren.');
        }

        if ((int) $variant['stock'] <= 0) {
            throw new RuntimeException('Stok ' . $variant['name'] . ' habis.');
        }

        return $variant;
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     issues: list<string>,
     *     count: int,
     *     subtotal: int,
     *     stores: list<array<string, mixed>>
     * }
     */
    private function read(int $cartId): array
    {
        // The column list carries a compiled subquery, so it has to be finished
        // before the outer builder claims the alias registry. See
        // CatalogQuery::listingColumns() for the same reason.
        $columns = 'ci.id, ci.qty, ci.store_id, ci.product_variant_id, ci.product_id,'
            . ' v.name AS variant_name, v.price, v.stock, v.is_active AS variant_active,'
            . ' p.name AS product_name, p.slug AS product_slug, p.status AS product_status,'
            . ' s.name AS store_name, s.slug AS store_slug, s.verification_status, s.is_active AS store_active,'
            . ' ' . CatalogQuery::firstImageSql($this->db, 'pi.url') . ' AS image_url';

        $rows = $this->db->table('cart_items ci')
            ->select($columns, false)
            ->join('product_variants v', 'v.id = ci.product_variant_id')
            ->join('products p', 'p.id = ci.product_id')
            ->join('stores s', 's.id = ci.store_id')
            ->where('ci.cart_id', $cartId)
            ->orderBy('ci.id', 'ASC')
            ->get()
            ->getResultArray();

        $items = [];
        $issues = [];
        $subtotal = 0;
        $count = 0;
        $stores = [];

        foreach ($rows as $row) {
            $qty      = (int) $row['qty'];
            $price    = (int) $row['price'];
            $stock    = (int) $row['stock'];
            $sellable = (bool) $row['variant_active']
                && $row['product_status'] === 'active'
                && (bool) $row['store_active']
                && $row['verification_status'] === 'verified';

            if (! $sellable) {
                $issues[] = $row['product_name'] . ' sudah tidak tersedia dan tidak bisa dipesan.';

                continue;
            }

            if ($qty > $stock) {
                $issues[] = $row['product_name'] . ' tersisa ' . $stock . ', kurangi jumlahnya di keranjang.';
            }

            $items[] = [
                'id'                => (int) $row['id'],
                'product_id'        => (int) $row['product_id'],
                'product_variant_id' => (int) $row['product_variant_id'],
                'product_name'      => $row['product_name'],
                'product_slug'      => $row['product_slug'],
                'variant_name'      => $row['variant_name'],
                'image_url'         => $row['image_url'],
                'store_name'        => $row['store_name'],
                'store_slug'        => $row['store_slug'],
                'store_id'          => (int) $row['store_id'],
                'unit_price'        => $price,
                'qty'               => $qty,
                'line_total'        => $price * $qty,
                'stock'             => $stock,
                'sellable'          => $sellable && $qty <= $stock,
            ];

            $subtotal += $price * $qty;
            $count     += $qty;

            $stores[(int) $row['store_id']] ??= [
                'id'   => (int) $row['store_id'],
                'name' => $row['store_name'],
                'slug' => $row['store_slug'],
            ];
        }

        return [
            'items'    => $items,
            'issues'   => $issues,
            'count'    => $count,
            'subtotal' => $subtotal,
            'stores'   => array_values($stores),
        ];
    }
}