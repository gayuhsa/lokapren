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
            'status'     => 'published',
            'is_featured' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ], $overrides);

        $this->db->table('products')->insert($row);

        return $this->db->table('products')->where('id', $this->db->insertID())->get()->getRowArray();
    }
}
