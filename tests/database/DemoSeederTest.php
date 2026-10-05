<?php

declare(strict_types=1);

use App\Database\Seeds\DemoSeeder;
use App\Models\SellerFacilityModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * The demo dataset every storefront, account and dashboard screen renders.
 *
 * The seeder is written to be read as much as inserted, so these tests hold it
 * to the two promises it makes: the data is internally consistent (counters,
 * totals and timelines all agree with the rows behind them), and it is
 * browsable (the images it points at actually exist on disk).
 *
 * @internal
 */
final class DemoSeederTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    /**
     * Seeds run before every test: `$refresh` re-runs the migrations first, so
     * each case sees the dataset exactly as `spark db:seed` would produce it.
     */
    protected $seed = DemoSeeder::class;

    private const CUSTOMER = 'sari.pembeli';

    /**
     * @var list<string>
     */
    private const SELLERS = ['gebyok.mandiri', 'tenun.magelang', 'keramik.magelang'];

    private const SALES_STATUSES = ['shipped', 'delivered', 'completed'];

    public function testDemoAccountsComeWithShieldIdentitiesAndPasswords(): void
    {
        $users      = $this->usersByUsername();
        $identities = config('Auth')->tables['identities'];

        foreach (array_merge([self::CUSTOMER], self::SELLERS) as $username) {
            $user = $users[$username] ?? null;

            $this->assertNotNull($user, 'Missing demo user: ' . $username);
            $this->assertSame('1', (string) $user['active'], $username . ' must be usable on first login');
        }

        $passwords = $this->db
            ->table($identities)
            ->where('type', 'email_password')
            ->get()
            ->getResultArray();

        $hashed = [];

        foreach ($passwords as $identity) {
            $this->assertTrue(
                password_verify(DemoSeeder::PASSWORD, $identity['secret2']),
                'Identity ' . $identity['id'] . ' does not match the published demo password'
            );
            $this->assertStringContainsString('@', $identity['secret'], 'The identity doubles as the email');
            $hashed[(int) $identity['user_id']] = true;
        }

        foreach (array_merge([self::CUSTOMER], self::SELLERS) as $username) {
            $this->assertArrayHasKey((int) $users[$username]['id'], $hashed, $username . ' has no password identity');
        }
    }

    public function testOnlyTheTwoDocumentedRolesAreAssigned(): void
    {
        $groups = config('Auth')->tables['groups_users'];

        $rows = $this->db->table($groups)->get()->getResultArray();
        $byUser = [];

        foreach ($rows as $row) {
            $byUser[(int) $row['user_id']][] = $row['group'];
        }

        $users = $this->usersByUsername();

        $this->assertSame(['customer'], $byUser[(int) $users[self::CUSTOMER]['id']]);

        foreach (self::SELLERS as $username) {
            $this->assertSame(['seller'], $byUser[(int) $users[$username]['id']], $username);
        }

        foreach ($rows as $row) {
            $this->assertContains($row['group'], ['customer', 'seller']);
            $this->assertNotEmpty($row['created_at'], 'auth_groups_users.created_at is NOT NULL');
        }
    }

    public function testSellerStorefrontsHaveHoursFacilitiesStoriesAndReplies(): void
    {
        $profiles = $this->rows('seller_profiles');
        $users    = $this->usersByUsername();

        $this->assertCount(3, $profiles);

        foreach (self::SELLERS as $username) {
            $userId = (int) $users[$username]['id'];
            $profile = $this->findBy($profiles, 'user_id', $userId);

            $this->assertNotNull($profile, 'No seller profile for ' . $username);
            $this->assertNotEmpty($profile['display_name']);
            $this->assertNotEmpty($profile['slug']);
            $this->assertSame('Jawa Tengah', $profile['province']);
            $this->assertNotSame('', (string) $profile['latitude']);

            $hours = $this->where($this->rows('seller_business_hours'), 'seller_id', $userId);
            $this->assertCount(7, $hours, $username . ' needs one row per weekday');

            $days = array_map(static fn (array $row): int => (int) $row['day_of_week'], $hours);
            sort($days);
            $this->assertSame([1, 2, 3, 4, 5, 6, 7], $days, 'day_of_week is ISO, Monday first');

            foreach ($hours as $hour) {
                if ((int) $hour['is_closed'] === 1) {
                    $this->assertNull($hour['opens_at'], 'A closed day has no opening time');
                } else {
                    $this->assertNotNull($hour['opens_at']);
                    $this->assertGreaterThan($hour['opens_at'], (string) $hour['closes_at']);
                }
            }

            $facilities = $this->where($this->rows('seller_facilities'), 'seller_id', $userId);
            $this->assertNotEmpty($facilities, $username . ' has no facilities');

            foreach ($facilities as $facility) {
                $this->assertArrayHasKey(
                    $facility['facility'],
                    SellerFacilityModel::FACILITIES,
                    'Unknown facility code: ' . $facility['facility']
                );
                $this->assertSame(
                    SellerFacilityModel::FACILITIES[$facility['facility']],
                    $facility['label'],
                    'Label drifted from the vocabulary for ' . $facility['facility']
                );
            }

            $this->assertCount(2, $this->where($this->rows('seller_story_sections'), 'seller_id', $userId));
            $this->assertCount(3, $this->where($this->rows('seller_quick_replies'), 'seller_id', $userId));
        }
    }

    public function testCatalogTreeProductsAndImages(): void
    {
        $categories = $this->rows('product_categories');
        $products   = $this->rows('products');
        $users      = $this->usersByUsername();

        $this->assertCount(7, $categories);
        $this->assertCount(8, $products);

        $roots  = array_filter($categories, static fn (array $row): bool => $row['parent_id'] === null);
        $leaves = array_filter($categories, static fn (array $row): bool => $row['parent_id'] !== null);

        $this->assertCount(4, $roots);
        $this->assertCount(3, $leaves);

        foreach ($categories as $category) {
            $this->assertNotEmpty($category['slug']);
            $this->assertSame(1, (int) $category['is_active']);

            if ($category['parent_id'] !== null) {
                $this->assertArrayHasKey((int) $category['parent_id'], $categories, 'Orphan category');
            }
        }

        $codes     = [];
        $published = [];
        $userIds   = array_map(static fn (array $user): int => (int) $user['id'], $users);

        foreach ($products as $product) {
            $this->assertArrayHasKey((int) $product['category_id'], $categories, 'Orphan product');
            $this->assertContains((int) $product['seller_id'], $userIds, 'Product belongs to nobody');
            $this->assertArrayNotHasKey($product['product_code'], $codes, 'Duplicate product code');
            $codes[$product['product_code']] = true;
            $this->assertSame(1, (int) $product['is_active']);

            if ($product['status'] === 'published') {
                $published[] = $product;
                $this->assertNotNull($product['published_at'], $product['slug'] . ' needs a publish date');
                $this->assertGreaterThan(0, (int) $product['price'], 'Rupiah prices are positive integers');
            } else {
                $this->assertSame('draft', $product['status']);
                $this->assertNull($product['published_at']);
            }
        }

        $this->assertCount(7, $published);

        $images = $this->rows('product_images');

        foreach ($products as $product) {
            $own = $this->where($images, 'product_id', (int) $product['id']);

            if ($product['status'] === 'draft') {
                $this->assertSame([], $own, 'A draft should not have a cover photo');

                continue;
            }

            $this->assertNotEmpty($own, $product['slug'] . ' has no image');
            $this->assertGreaterThan(0, count(array_filter($own, static fn (array $row): int => (int) $row['is_primary'])));

            foreach ($own as $image) {
                $this->assertNotEmpty($image['file_path']);
                $this->assertStringNotContainsString('media/', $image['file_path'], 'Views add the media/ prefix');
            }
        }

        $this->assertNotEmpty($this->rows('product_variants'), 'No product variants were seeded');
    }

    public function testArticlesAreGroupedIntoCategories(): void
    {
        $posts       = $this->rows('blog_posts');
        $categories  = $this->rows('blog_categories');
        $this->assertCount(4, $posts);
        $this->assertCount(3, $categories);

        $published = 0;

        foreach ($posts as $post) {
            $this->assertArrayHasKey((int) $post['category_id'], $categories, 'Article has no category');
            $this->assertNotEmpty($post['slug']);

            if ($post['status'] === 'published') {
                $published++;
                $this->assertNotNull($post['published_at']);
                $this->assertNotEmpty($post['body']);
            }
        }

        $this->assertSame(3, $published);
    }

    public function testOrdersAgreeWithTheirItemsTimelineAndMoney(): void
    {
        $orders   = $this->rows('orders');
        $items    = $this->rows('order_items');
        $history  = $this->rows('order_status_history');
        $reviews  = $this->rows('reviews');
        $users    = $this->usersByUsername();

        $this->assertCount(4, $orders);
        $this->assertCount(3, $reviews);

        $completed = 0;

        foreach ($orders as $order) {
            $subtotal  = (int) $order['subtotal'];
            $shipping  = (int) $order['shipping_total'];
            $grand     = (int) $order['grand_total'];
            $fee       = (int) $order['platform_fee'];
            $earning   = (int) $order['seller_earning'];

            $this->assertSame($subtotal + $shipping, $grand, $order['order_number'] . ' adds up');
            $this->assertSame(intdiv($subtotal, 20), $fee, $order['order_number'] . ' charges exactly 5%');
            $this->assertSame($grand - $fee, $earning, $order['order_number'] . ' keeps the fee for the platform');
            $this->assertSame('IDR', $order['currency']);
            $this->assertSame('paid', $order['payment_status']);

            $customer = $users[self::CUSTOMER]['id'];
            $this->assertSame((int) $customer, (int) $order['customer_id'], 'Every order belongs to the demo customer');
            $this->assertContains($order['seller_id'], array_map(
                static fn (string $username): int => (int) $users[$username]['id'],
                self::SELLERS
            ));

            $ownItems = $this->where($items, 'order_id', (int) $order['id']);
            $this->assertNotEmpty($ownItems, $order['order_number'] . ' has no lines');

            $lineTotal = 0;

            foreach ($ownItems as $item) {
                $this->assertSame(
                    (int) $item['unit_price'] * (int) $item['quantity'],
                    (int) $item['subtotal'],
                    'Line total mismatch in ' . $order['order_number']
                );
                $lineTotal += (int) $item['subtotal'];
            }

            $this->assertSame($subtotal, $lineTotal, $order['order_number'] . ' subtotal matches its lines');

            $ownHistory = array_values($this->where($history, 'order_id', (int) $order['id']));

            $this->assertNotEmpty($ownHistory, $order['order_number'] . ' has no history');

            $order['status'] === 'completed' && $completed++;

            $expectedCount = $order['status'] === 'completed' ? 7 : 3;
            $this->assertCount($expectedCount, $ownHistory, $order['order_number'] . ' timeline length');

            $last = $ownHistory[count($ownHistory) - 1];
            $this->assertSame($order['status'], $last['to_status'], $order['order_number'] . ' ends where it claims');

            $previous = null;

            foreach ($ownHistory as $entry) {
                if ($previous !== null) {
                    $this->assertLessThanOrEqual(
                        strtotime((string) $entry['created_at']),
                        strtotime((string) $previous),
                        'Timeline for ' . $order['order_number'] . ' runs backwards'
                    );
                }

                $previous = $entry['created_at'];
            }
        }

        $this->assertSame(3, $completed);

        foreach ($reviews as $review) {
            $this->assertSame('published', $review['status']);
            $this->assertSame(5, (int) $review['rating']);
            $this->assertNotEmpty($review['body']);
            $this->assertNotEmpty($review['title']);
        }

        $carts = $this->where($this->rows('carts'), 'status', 'active');
        $this->assertCount(1, $carts, 'The demo customer should have exactly one open cart');
        $this->assertNotEmpty($this->where($this->rows('cart_items'), 'cart_id', (int) $carts[0]['id']));

        $this->assertNotEmpty($this->rows('addresses'), 'No address book was seeded');
        $this->assertNotEmpty($this->rows('user_profiles'));
    }

    public function testAggregatedCountersAreDerivedFromTheRowsBehindThem(): void
    {
        $products = $this->rows('products');
        $items    = $this->rows('order_items');
        $orders   = [];

        foreach ($this->rows('orders') as $order) {
            $orders[(int) $order['id']] = $order;
        }

        $reviewsByProduct = [];
        $reviewsBySeller  = [];

        foreach ($this->rows('reviews') as $review) {
            if ($review['status'] !== 'published') {
                continue;
            }

            $reviewsByProduct[(int) $review['product_id']][] = (int) $review['rating'];
            $reviewsBySeller[(int) $review['seller_id']][]   = (int) $review['rating'];
        }

        $unitsByProduct = [];
        $unitsBySeller  = [];
        $ordersByProduct = [];

        foreach ($items as $item) {
            $order = $orders[(int) $item['order_id']] ?? null;

            if ($order === null) {
                continue;
            }

            $productId = (int) $item['product_id'];
            $sellerId  = (int) $order['seller_id'];
            $quantity  = (int) $item['quantity'];

            $ordersByProduct[$productId][(int) $item['order_id']] = true;

            if (in_array($order['status'], self::SALES_STATUSES, true)) {
                $unitsByProduct[$productId] = ($unitsByProduct[$productId] ?? 0) + $quantity;
                $unitsBySeller[$sellerId]   = ($unitsBySeller[$sellerId] ?? 0) + $quantity;
            }
        }

        $soldTotal = 0;

        foreach ($products as $product) {
            $id         = (int) $product['id'];
            $expected   = $unitsByProduct[$id] ?? 0;
            $soldTotal += $expected;

            $this->assertSame($expected, (int) $product['sold_count'], $product['slug'] . ' sold_count');
            $this->assertSame(count($ordersByProduct[$id] ?? []), (int) $product['orders_count'], $product['slug'] . ' orders_count');
            $this->assertCounterAgrees(
                $reviewsByProduct[$id] ?? [],
                (int) $product['rating_count'],
                (string) $product['rating_average'],
                $product['slug']
            );
        }

        $this->assertGreaterThan(0, $soldTotal, 'Nothing was marked as sold');

        foreach ($this->rows('seller_profiles') as $profile) {
            $userId = (int) $profile['user_id'];

            $this->assertSame(
                $unitsBySeller[$userId] ?? 0,
                (int) $profile['sold_count'],
                $profile['display_name'] . ' sold_count'
            );
            $this->assertCounterAgrees(
                $reviewsBySeller[$userId] ?? [],
                (int) $profile['rating_count'],
                (string) $profile['rating_average'],
                $profile['display_name']
            );
        }
    }

    public function testDailyStatsCoverEveryShippedOrder(): void
    {
        $stats = $this->rows('seller_daily_stats');
        $users = $this->usersByUsername();

        $expected = count(self::SELLERS) * 18;
        $this->assertCount($expected, $stats, 'One row per seller per day over the demo window');

        $shipped = [];

        foreach ($this->rows('orders') as $order) {
            if ($order['shipped_at'] === null) {
                continue;
            }

            $shipped[] = $order;
        }

        $this->assertCount(3, $shipped);

        $expectedRevenue = 0;

        foreach ($shipped as $order) {
            $expectedRevenue += (int) $order['seller_earning'];

            $day = substr((string) $order['shipped_at'], 0, 10);
            $row = null;

            foreach ($stats as $stat) {
                if ((int) $stat['seller_id'] === (int) $order['seller_id'] && $stat['stat_date'] === $day) {
                    $row = $stat;

                    break;
                }
            }

            $this->assertNotNull($row, 'No stats row for the day ' . $order['order_number'] . ' shipped');
            $this->assertGreaterThan(0, (int) $row['order_count'], 'Shipment not counted on ' . $day);
        }

        $orderCount = array_sum(array_map(static fn (array $row): int => (int) $row['order_count'], $stats));
        $revenue    = array_sum(array_map(static fn (array $row): int => (int) $row['revenue_total'], $stats));

        $this->assertSame(count($shipped), $orderCount, 'Stats claim a different number of orders');
        $this->assertSame($expectedRevenue, $revenue, 'Stats claim a different revenue total');

        $sellerIds = array_map(static fn (array $user): int => (int) $user['id'], $users);

        foreach ($stats as $row) {
            $this->assertContains((int) $row['seller_id'], $sellerIds, 'Stats row for a stranger');
            $this->assertGreaterThan(0, (int) $row['visit_count']);
        }
    }

    public function testEveryGeneratedImageExistsOnDisk(): void
    {
        $paths = [];

        foreach ($this->rows('product_images') as $image) {
            $paths[] = $image['file_path'];
            $paths[] = $image['thumb_path'];
        }

        foreach ($this->rows('seller_profiles') as $profile) {
            $paths[] = $profile['logo_path'];
            $paths[] = $profile['cover_path'];
        }

        foreach ($this->rows('seller_story_sections') as $story) {
            $paths[] = $story['image_path'];
        }

        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

            $absolute = WRITEPATH . 'uploads/' . ltrim($path, '/');

            $this->assertFileExists($absolute, 'Missing generated image: ' . $path);
            $this->assertGreaterThan(0, filesize($absolute), $path . ' is empty');
        }
    }

    public function testRunningTheSeederTwiceAddsNothing(): void
    {
        $tables = [
            'users', 'seller_profiles', 'product_categories', 'products', 'product_images',
            'blog_posts', 'orders', 'order_items', 'order_status_history', 'reviews',
            'seller_daily_stats', 'carts', 'cart_items',
        ];

        $before = [];

        foreach ($tables as $table) {
            $before[$table] = $this->countRows($table);
        }

        $this->seed(DemoSeeder::class);

        foreach ($tables as $table) {
            $this->assertSame($before[$table], $this->countRows($table), $table . ' grew on a second run');
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function rows(string $table): array
    {
        $rows = $this->db->table($table)->get()->getResultArray();
        $keyed = [];

        foreach ($rows as $row) {
            $keyed[$row['id']] = $row;
        }

        return $keyed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function usersByUsername(): array
    {
        $map = [];

        foreach ($this->rows('users') as $row) {
            $map[$row['username']] = $row;
        }

        return $map;
    }

    /**
     * @param array<int|string, array<string, mixed>> $rows
     *
     * @return array<int|string, array<string, mixed>>
     */
    private function where(array $rows, string $column, int|string $value): array
    {
        return array_values(array_filter(
            $rows,
            static fn (array $row): bool => (string) $row[$column] === (string) $value
        ));
    }

    /**
     * @param array<int|string, array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private function findBy(array $rows, string $column, int $value): ?array
    {
        $matches = $this->where($rows, $column, $value);

        return $matches === [] ? null : $matches[0];
    }

    private function countRows(string $table): int
    {
        return $this->db->table($table)->countAllResults();
    }

    /**
     * @param list<int> $ratings
     */
    private function assertCounterAgrees(array $ratings, int $count, string $average, string $label): void
    {
        $this->assertSame(count($ratings), $count, $label . ' rating_count');

        $expected = $ratings === []
            ? '0.00'
            : number_format(array_sum($ratings) / count($ratings), 2, '.', '');

        $this->assertSame($expected, number_format((float) $average, 2, '.', ''), $label . ' rating_average');
    }
}
