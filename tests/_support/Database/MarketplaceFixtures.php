<?php

declare(strict_types=1);

namespace Tests\Support\Database;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Seeds the minimum rows needed to exercise the marketplace models.
 *
 * Users are written straight into `users` with the query builder: the tests
 * exercise application models, not Shield's registration flow, and a bare row
 * is enough to satisfy the foreign keys.
 */
trait MarketplaceFixtures
{
    /**
     * Counter so auto-incremented ids stay unique across a test case.
     */
    private static int $fixtureSeq = 0;

    /**
     * Insert a raw row and return its auto-increment id.
     *
     * `$builder->insert()` returns a result object, not an id, so the id has to
     * be read back from the connection.
     *
     * @param array<string, mixed> $data
     */
    protected function insertFixture(string $table, array $data): int
    {
        $this->db->table($table)->insert($data);

        return (int) $this->db->insertID();
    }

    /**
     * Current timestamp in the format the migrations use.
     */
    protected function fixtureNow(): string
    {
        return date('Y-m-d H:i:s');
    }

    protected function makeUser(string $username = 'seller'): int
    {
        self::$fixtureSeq++;

        return $this->insertFixture('users', [
            'username' => $username . '_' . self::$fixtureSeq,
            'active'   => 1,
        ]);
    }

    /**
     * Create a seller with a storefront and return `[$sellerId, $profileId]`.
     *
     * The seller is also put in Shield's `seller` group, because a profile row
     * alone does not open the `/seller` area — the `role:seller` filter checks
     * Shield's group membership, not the storefront table.
     *
     * @return array{0: int, 1: int}
     */
    protected function makeSeller(string $prefix = 'sanggar'): array
    {
        $sellerId  = $this->makeUser('seller');
        $profileId = $this->insertFixture('seller_profiles', [
            'user_id'      => $sellerId,
            'slug'         => $prefix . '-' . self::$fixtureSeq,
            'partner_code' => 'AB-' . str_pad((string) self::$fixtureSeq, 4, '0', STR_PAD_LEFT),
            'display_name' => 'Sanggar ' . self::$fixtureSeq,
            'created_at'   => $this->fixtureNow(),
            'updated_at'   => $this->fixtureNow(),
        ]);

        $this->addUserToGroup($sellerId, 'seller');

        return [$sellerId, $profileId];
    }

    /**
     * Put a user into a Shield group, which is what `role:` filters check.
     */
    protected function addUserToGroup(int $userId, string $group): void
    {
        $this->insertFixture(config('Auth')->tables['groups_users'], [
            'user_id'    => $userId,
            'group'      => $group,
            'created_at' => $this->fixtureNow(),
        ]);
    }

    protected function makeCategory(string $name = 'Kalpataru'): int
    {
        self::$fixtureSeq++;

        // product_categories carries no timestamps.
        return $this->insertFixture('product_categories', [
            'name'      => $name . ' ' . self::$fixtureSeq,
            'slug'      => 'cat-' . self::$fixtureSeq,
            'is_active' => 1,
        ]);
    }

    /**
     * @return array{0: int, 1: int} `[$sellerId, $productId]`
     */
    protected function makeProduct(int $sellerId, int $categoryId, array $overrides = []): array
    {
        self::$fixtureSeq++;

        $data = array_merge([
            'seller_id'    => $sellerId,
            'category_id'  => $categoryId,
            'product_code' => 'P-' . str_pad((string) self::$fixtureSeq, 5, '0', STR_PAD_LEFT),
            'slug'         => 'product-' . self::$fixtureSeq,
            'name'         => 'Kalpataru Relief ' . self::$fixtureSeq,
            'price'        => 450000,
            'stock'        => 5,
            'status'       => 'published',
            'is_active'    => 1,
            'created_at'   => $this->fixtureNow(),
            'updated_at'   => $this->fixtureNow(),
        ], $overrides);

        return [$sellerId, $this->insertFixture('products', $data)];
    }

    /**
     * @return array{0: int, 1: int, 2: int} `[$customerId, $orderId, $orderItemId]`
     */
    protected function makeOrder(int $customerId, int $sellerId, int $productId, array $overrides = []): array
    {
        self::$fixtureSeq++;

        $orderId = $this->insertFixture('orders', array_merge([
            'order_number'   => 'LKP-' . date('Y') . '-' . str_pad((string) self::$fixtureSeq, 6, '0', STR_PAD_LEFT),
            'customer_id'    => $customerId,
            'seller_id'      => $sellerId,
            'status'         => 'awaiting_artisan',
            'currency'       => 'IDR',
            'subtotal'       => 450000,
            'grand_total'    => 450000,
            'seller_earning' => 450000,
            'placed_at'      => $this->fixtureNow(),
            'created_at'     => $this->fixtureNow(),
            'updated_at'     => $this->fixtureNow(),
        ], $overrides));

        $orderItemId = $this->insertFixture('order_items', [
            'order_id'     => $orderId,
            'product_id'   => $productId,
            'product_name' => 'Kalpataru Relief',
            'unit_price'   => 450000,
            'quantity'     => 1,
            'subtotal'     => 450000,
            'created_at'   => $this->fixtureNow(),
        ]);

        return [$customerId, $orderId, $orderItemId];
    }
}