<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\ProductModel;
use App\Models\StoreModel;
use CodeIgniter\Shield\Entities\User;

/**
 * Creates marketplace rows for tests.
 *
 * Rows are inserted through the query builder with explicit timestamps rather
 * than through model::insert(). Two reasons:
 *
 * - created_at/updated_at are NOT NULL, and $useTimestamps only applies when a
 *   model does the insert.
 * - user_id and store_code are deliberately absent from StoreModel's
 *   $allowedFields, because they are server-controlled and must never be mass
 *   assigned from a request payload. See Store::createForUser().
 *
 * @internal
 */
trait CreatesMarketplace
{
    protected static int $storeSequence = 0;

    protected static int $productSequence = 0;

    protected static int $categorySequence = 0;

    protected static int $variantSequence = 0;

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeStore(User $owner, array $overrides = []): array
    {
        self::$storeSequence++;

        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'user_id'            => $owner->id,
            'store_code'         => sprintf('UJI%04d', self::$storeSequence),
            'name'               => 'Toko Uji ' . self::$storeSequence,
            'slug'               => 'toko-uji-' . self::$storeSequence,
            'verification_status' => 'verified',
            'is_active'          => true,
            'created_at'         => $now,
            'updated_at'         => $now,
        ], $overrides);

        $this->db->table('stores')->insert($row);

        return $this->db->table('stores')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $store
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeProduct(array $store, array $overrides = []): array
    {
        self::$productSequence++;

        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'store_id'   => $store['id'],
            'name'       => 'Produk Uji ' . self::$productSequence,
            'slug'       => 'produk-uji-' . self::$productSequence,
            'status'     => 'active',
            'is_featured' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $this->db->table('products')->insert($row);

        return $this->db->table('products')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeCategory(array $overrides = []): array
    {
        self::$categorySequence++;

        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'name'      => 'Kategori Uji ' . self::$categorySequence,
            'slug'      => 'kategori-uji-' . self::$categorySequence,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $this->db->table('categories')->insert($row);

        return $this->db->table('categories')->where('id', $this->db->insertID())->get()->getRowArray();
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeVariant(array $product, array $overrides = []): array
    {
        self::$variantSequence++;

        $now = date('Y-m-d H:i:s');

        $row = array_merge([
            'product_id' => $product['id'],
            'name'       => 'Varian Uji ' . self::$variantSequence,
            'sku'        => sprintf('UJI-V%05d', self::$variantSequence),
            'price'      => 100000,
            'stock'      => 5,
            'is_active'  => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $this->db->table('product_variants')->insert($row);

        return $this->db->table('product_variants')->where('id', $this->db->insertID())->get()->getRowArray();
    }
}
