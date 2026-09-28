<?php

declare(strict_types=1);

namespace Tests\database;

use App\Models\ProductReviewModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MarketplaceSchemaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /**
     * Run every namespace (App, Shield, Settings) so the marketplace tables are
     * created against the same schema the application uses in production.
     */
    protected $namespace = null;

    private const MARKETPLACE_TABLES = [
        'stores',
        'categories',
        'products',
        'product_variants',
        'product_images',
        'store_posts',
        'orders',
        'order_items',
        'order_deliveries',
        'delivery_events',
        'conversations',
        'messages',
        'product_reviews',
        'review_photos',
        'review_helpful_votes',
        'store_visits',
    ];

    public function testEveryMarketplaceTableIsCreated(): void
    {
        $prefix  = $this->db->DBPrefix;
        $tables  = $this->db->listTables();
        $present = [];

        foreach ($tables as $table) {
            $present[] = str_starts_with($table, $prefix)
                ? substr($table, strlen($prefix))
                : $table;
        }

        foreach (self::MARKETPLACE_TABLES as $table) {
            $this->assertContains($table, $present, "Table {$table} should exist.");
        }
    }

    public function testOrderCodeMustBeUnique(): void
    {
        $orderId = $this->createOrder('LKP-2025-0001');
        $this->assertIsInt($orderId);

        $this->expectException(DatabaseException::class);

        $this->createOrder('LKP-2025-0001');
    }

    public function testOrderMustBelongToAnExistingShieldUser(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->table('orders')->insert([
            'order_code'      => 'LKP-2025-9999',
            'customer_id'     => 987654,
            'status'          => 'pending',
            'subtotal'        => 450000,
            'delivery_fee'    => 20000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total'           => 470000,
            'recipient_name'  => 'Budi Santoso',
            'recipient_phone' => '081234567890',
            'address_line'    => 'Villa Menoreh Asri No. 8',
            'subdistrict'     => 'Borobudur',
            'city'            => 'Magelang',
            'postal_code'     => '56553',
            'courier_note'    => null,
            'created_at'      => '2026-02-16 10:00:00',
            'updated_at'      => '2026-02-16 10:00:00',
        ]);
    }

    public function testCustomerCanOnlyReviewTheSameProductOncePerOrder(): void
    {
        $userId    = $this->createUser();
        $storeId   = $this->createStore($userId);
        $productId = $this->createProduct($storeId);
        $orderId   = $this->createOrder('LKP-2025-0002', $userId);

        $this->createOrderItem($orderId, $storeId, $productId);

        $review = [
            'product_id' => $productId,
            'order_id'   => $orderId,
            'customer_id' => $userId,
            'rating'     => 5,
            'comment'    => 'Karya ukiran Ibu Sri sungguh di luar ekspektasi.',
            'created_at' => '2026-02-16 11:00:00',
            'updated_at' => '2026-02-16 11:00:00',
        ];

        $this->db->table('product_reviews')->insert($review);

        $this->expectException(DatabaseException::class);
        $this->db->table('product_reviews')->insert($review);
    }

    public function testReviewRatingRangeIsEnforcedByTheModel(): void
    {
        // CI4's Forge cannot emit CHECK constraints portably (the SQLite3 Forge
        // has no support for them), so the 1-5 rating range is enforced by the
        // model rather than by a database constraint.
        $model = new ProductReviewModel();

        $this->assertFalse($model->validate(['rating' => 0]));
        $this->assertFalse($model->validate(['rating' => 6]));
        $this->assertTrue($model->validate(['rating' => 1]));
        $this->assertTrue($model->validate(['rating' => 5]));
    }

    public function testDeletingAStoreCascadesToItsProducts(): void
    {
        $userId    = $this->createUser();
        $storeId   = $this->createStore($userId);
        $productId = $this->createProduct($storeId);

        $this->assertSame(1, $this->db->table('products')->where('id', $productId)->countAllResults());

        $this->db->table('stores')->where('id', $storeId)->delete();

        $this->assertSame(0, $this->db->table('products')->where('id', $productId)->countAllResults());
    }

    private function createUser(): int
    {
        $this->db->table('users')->insert(['username' => 'seller_' . uniqid()]);

        return (int) $this->db->insertID();
    }

    private function createStore(int $userId): int
    {
        $this->db->table('stores')->insert([
            'user_id'       => $userId,
            'store_code'    => 'BDR-' . uniqid(),
            'name'          => 'Omah Kriya Borobudur',
            'slug'          => 'omah-kriya-' . uniqid(),
            'tagline'       => 'Sentra Budaya Candirejo',
            'description'   => 'Melestarikan warisan ukiran kayu jati.',
            'address_line'  => 'Jl. Balaputradewa No. 14',
            'subdistrict'   => 'Borobudur',
            'city'          => 'Magelang',
            'phone'         => '081234567890',
            'created_at'    => '2026-02-16 08:00:00',
            'updated_at'    => '2026-02-16 08:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function createProduct(int $storeId): int
    {
        $this->db->table('products')->insert([
            'store_id'   => $storeId,
            'category_id' => null,
            'name'       => 'Plakat Relief Kalpataru Jati Tua',
            'slug'       => 'plakat-relief-kalpataru-' . uniqid(),
            'description' => 'Ukiran relief 3 dimensi motif stupa candi.',
            'attributes'  => '{"material":"Kayu Jati Perhutani"}',
            'status'     => 'active',
            'is_featured' => false,
            'created_at' => '2026-02-16 08:00:00',
            'updated_at' => '2026-02-16 08:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function createVariant(int $productId): int
    {
        $this->db->table('product_variants')->insert([
            'product_id' => $productId,
            'name'       => 'Ukuran L (35x28 cm)',
            'price'      => 450000,
            'stock'      => 4,
            'is_active'  => true,
            'created_at' => '2026-02-16 08:00:00',
            'updated_at' => '2026-02-16 08:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function createOrderItem(int $orderId, int $storeId, int $productId): int
    {
        $this->db->table('order_items')->insert([
            'order_id'           => $orderId,
            'store_id'           => $storeId,
            'product_id'         => $productId,
            'product_variant_id' => $this->createVariant($productId),
            'product_name'       => 'Plakat Relief Kalpataru',
            'variant_name'       => 'Ukuran L (35x28 cm)',
            'unit_price'         => 450000,
            'qty'                => 1,
            'line_total'         => 450000,
            'created_at'         => '2026-02-16 10:00:00',
            'updated_at'         => '2026-02-16 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }

    private function createOrder(string $orderCode, ?int $customerId = null): int
    {
        $this->db->table('orders')->insert([
            'order_code'      => $orderCode,
            'customer_id'     => $customerId ?? $this->createUser(),
            'status'          => 'pending',
            'subtotal'        => 450000,
            'delivery_fee'    => 20000,
            'discount_amount' => 0,
            'tax_amount'      => 0,
            'total'           => 470000,
            'recipient_name'  => 'Budi Santoso',
            'recipient_phone' => '081234567890',
            'address_line'    => 'Villa Menoreh Asri No. 8, Borobudur',
            'subdistrict'     => 'Borobudur',
            'city'            => 'Magelang',
            'postal_code'     => '56553',
            'courier_note'    => 'Titip di resepsionis villa.',
            'created_at'      => '2026-02-16 10:00:00',
            'updated_at'      => '2026-02-16 10:00:00',
        ]);

        return (int) $this->db->insertID();
    }
}
