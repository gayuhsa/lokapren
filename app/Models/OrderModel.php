<?php

declare(strict_types=1);

namespace App\Models;

/**
 * One order per seller.
 *
 * Every money column, the address snapshot, `status` and `customer_id` /
 * `seller_id` are server-owned and absent from `$allowedFields`. The full
 * ordered list of writable columns is intentionally empty for the customer and
 * seller paths; orders are created by the checkout service inside a
 * transaction, not by a generic model save.
 *
 * Status transitions are enforced here rather than in a controller so the rule
 * cannot be bypassed by any code path (AGENTS.md: "Order ownership and status
 * transitions must be validated server-side").
 */
class OrderModel extends BaseModel
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_AWAITING_ARTISAN = 'awaiting_artisan';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_READY_TO_SHIP = 'ready_to_ship';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Allowed state machine. Terminal states map to an empty list.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        self::STATUS_PENDING_PAYMENT  => [self::STATUS_AWAITING_ARTISAN, self::STATUS_CANCELLED],
        self::STATUS_AWAITING_ARTISAN => [self::STATUS_IN_PRODUCTION, self::STATUS_CANCELLED],
        self::STATUS_IN_PRODUCTION    => [self::STATUS_READY_TO_SHIP, self::STATUS_CANCELLED],
        self::STATUS_READY_TO_SHIP    => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
        self::STATUS_SHIPPED          => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED        => [self::STATUS_COMPLETED],
        self::STATUS_COMPLETED        => [],
        self::STATUS_CANCELLED        => [],
        self::STATUS_REFUNDED         => [],
    ];

    /**
     * Which lifecycle timestamp each target status stamps.
     *
     * @var array<string, string>
     */
    private const STATUS_TIMESTAMP = [
        self::STATUS_AWAITING_ARTISAN => 'paid_at',
        self::STATUS_SHIPPED          => 'shipped_at',
        self::STATUS_DELIVERED        => 'delivered_at',
        self::STATUS_COMPLETED        => 'completed_at',
        self::STATUS_CANCELLED        => 'cancelled_at',
    ];

    protected $table = 'orders';

    protected $returnType = 'array';

    /**
     * Orders are financial records and are never soft deleted.
     */
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'production_progress',
        'customer_note',
    ];

    protected array $casts = [
        'subtotal'            => 'int',
        'shipping_total'      => 'int',
        'service_total'       => 'int',
        'tax_total'           => 'int',
        'donation_total'      => 'int',
        'grand_total'         => 'int',
        'platform_fee'        => 'int',
        'seller_earning'      => 'int',
        'production_progress' => 'int',
    ];

    protected $validationRules = [
        'production_progress' => 'permit_empty|is_natural|less_than_equal_to[100]',
        'customer_note'       => 'permit_empty|max_length[500]',
    ];

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return array_keys(self::TRANSITIONS);
    }

    /**
     * @return list<string>
     */
    public static function allowedTransitionsFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::allowedTransitionsFrom($from), true);
    }

    /**
     * Load an order for its customer, or null when it belongs to someone else.
     */
    public function findForCustomer(int $orderId, int $customerId): ?array
    {
        return $this->newRow(
            $this->newQuery()
                ->where('id', $orderId)
                ->where('customer_id', $customerId)
        );
    }

    /**
     * Load an order for its seller, or null when it belongs to someone else.
     */
    public function findForSeller(int $orderId, int $sellerId): ?array
    {
        return $this->newRow(
            $this->newQuery()
                ->where('id', $orderId)
                ->where('seller_id', $sellerId)
        );
    }

    /**
     * Apply a status transition and append the audit row, atomically.
     *
     * Returns false when the transition is not allowed; the caller should treat
     * that as a 422 rather than retrying.
     */
    public function transition(int $orderId, string $to, ?int $actorId = null, ?string $note = null): bool
    {
        $order = $this->newRow(
            $this->newQuery()
                ->select('id, status, customer_id, seller_id')
                ->where('id', $orderId)
        );

        if ($order === null) {
            return false;
        }

        $from = (string) $order['status'];

        if (! self::canTransition($from, $to)) {
            return false;
        }

        $now    = date('Y-m-d H:i:s');
        $update = ['status' => $to, 'updated_at' => $now];

        if (isset(self::STATUS_TIMESTAMP[$to])) {
            $update[self::STATUS_TIMESTAMP[$to]] = $now;
        }

        $this->db->transStart();

        $this->newQuery()->where('id', $orderId)->update($update);

        $this->db->table('order_status_history')->insert([
            'order_id'    => $orderId,
            'from_status' => $from,
            'to_status'   => $to,
            'note'        => $note,
            'actor_id'    => $actorId,
            'created_at'  => $now,
        ]);

        return $this->db->transComplete();
    }

    /**
     * Seller-facing order list for the dashboard.
     *
     * `$bucket` is whitelisted so request input cannot choose a column.
     */
    public function forSellerDashboard(int $sellerId, string $bucket = 'all', int $limit = 50, int $offset = 0): array
    {
        $builder = $this->newQuery()->where('seller_id', $sellerId);

        match ($bucket) {
            'to_ship'      => $builder->whereIn('status', [self::STATUS_AWAITING_ARTISAN, self::STATUS_IN_PRODUCTION, self::STATUS_READY_TO_SHIP]),
            'in_transit'   => $builder->where('status', self::STATUS_SHIPPED),
            'done'         => $builder->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_COMPLETED]),
            'cancelled'    => $builder->where('status', self::STATUS_CANCELLED),
            default        => $builder,
        };

        return $this->newRows($builder->orderBy('placed_at', 'DESC'), $limit, $offset);
    }

    /**
     * Customer-facing order history, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function forCustomerHistory(int $customerId, int $limit = 50, int $offset = 0, array $statuses = []): array
    {
        $builder = $this->newQuery()
            ->select('orders.*, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.logo_path AS shop_logo, seller_profiles.village AS shop_village, seller_profiles.regency AS shop_regency')
            ->join('seller_profiles', 'seller_profiles.user_id = orders.seller_id', 'left')
            ->where('orders.customer_id', $customerId)
            ->orderBy('orders.placed_at', 'DESC');

        // The status tab filters in SQL, not after paging: slicing first and
        // filtering afterwards would show a short page and misreport the
        // number of pages.
        if ($statuses !== []) {
            $builder->whereIn('orders.status', $statuses);
        }

        return $this->newRows($builder, $limit, $offset);
    }

/**
 * Sum of `grand_total` across a customer's orders in the given statuses.
 *
 * Used for the account page's lifetime-spending figure, so it queries every
 * matching order rather than adding up the ten most recent ones.
 *
 * @param list<string> $statuses
 */
public function totalForCustomer(int $customerId, array $statuses): int
{
    $row = $this->newQuery()
        ->selectSum('grand_total', 'total')
        ->where('customer_id', $customerId)
        ->whereIn('status', $statuses)
        ->get()
        ->getRowArray();

    return (int) ($row['total'] ?? 0);
}

/**
 * How many orders a customer has in total, ignoring the limit on the account
 * page's recent-orders list.
 *
 * @param list<string> $statuses
 */
public function countForCustomer(int $customerId, array $statuses = []): int
{
    $builder = $this->newQuery()
        ->where('customer_id', $customerId);

    if ($statuses !== []) {
        $builder->whereIn('status', $statuses);
    }

    return $builder->countAllResults();
}

/**
 * Order totals per `OrderService::CUSTOMER_BUCKETS` tab, for the
 * badge counts in the buyer's order history.
 *
 * @param array<string, list<string>> $buckets
 *
 * @return array<string, int>
 */
    public function statusCountsForCustomer(int $customerId, array $buckets): array
    {
        $rows = $this->newQuery()
            ->select('status, COUNT(*) AS total', false)
            ->where('customer_id', $customerId)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $byStatus = [];
        foreach ($rows as $row) {
            $byStatus[(string) $row['status']] = (int) $row['total'];
        }

        $counts = [];

        foreach ($buckets as $key => $statuses) {
            if ($statuses === []) {
                $counts[$key] = array_sum($byStatus);
                continue;
            }

            $counts[$key] = array_sum(array_map(
                static fn (string $status): int => $byStatus[$status] ?? 0,
                $statuses
            ));
        }

        return $counts;
    }

    /**
     * Seller earnings for a date range, from the immutable snapshot on the
     * order rather than a separate cash-book table.
     *
     * Revenue is recognised on the order's first arrival at `shipped`, which is
     * what `OrderService` writes into the daily rollup, so this reads the same
     * window. Counting only delivered and completed would report less than the
     * seller's own dashboard shows.
     */
    public function earningsBetween(int $sellerId, string $from, string $to): int
    {
        $row = $this->newQuery()
            ->selectSum('seller_earning', 'total')
            ->where('seller_id', $sellerId)
            ->whereIn('status', [
                self::STATUS_SHIPPED,
                self::STATUS_DELIVERED,
                self::STATUS_COMPLETED,
            ])
            ->where('placed_at >=', $from)
            ->where('placed_at <=', $to)
            ->get()
            ->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Order count grouped by destination city/regency, for the dashboard's
     * buyer-geography breakdown.
     *
     * Geography is the free text the customer typed, so this groups on the
     * label rather than a normalised reference table.
     *
     * @return list<array<string, mixed>>
     */
    public function buyerGeography(int $sellerId, string $from, string $to): array
    {
        $builder = $this->newQuery()
            ->select('ship_regency AS regency, COUNT(*) AS order_count, SUM(grand_total) AS revenue_total')
            ->where('seller_id', $sellerId)
            // `!= ''` never matches in SQL, so the non-empty test is explicit.
            ->where('ship_regency !=', '')
            ->where('placed_at >=', $from)
            ->where('placed_at <=', $to)
            ->groupBy('ship_regency')
            ->orderBy('order_count', 'DESC');

        return $this->newRows($builder);
    }

    /**
     * Generate the next order number for the current year.
     *
     * The unique index on `order_number` is the real guard; the loop simply
     * avoids burning auto-increment ids on a collision.
     */
    public function nextOrderNumber(): string
    {
        $prefix = 'LKP-' . date('Y') . '-';

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $candidate = $prefix . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $exists = $this->newQuery()
                ->where('order_number', $candidate)
                ->countAllResults();

            if ($exists === 0) {
                return $candidate;
            }
        }

        throw \RuntimeException('Unable to allocate a unique order number.');
    }
}