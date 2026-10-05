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
    public function forCustomerHistory(int $customerId, int $limit = 50, int $offset = 0): array
    {
        $builder = $this->newQuery()
            ->where('customer_id', $customerId)
            ->orderBy('placed_at', 'DESC');

        return $this->newRows($builder, $limit, $offset);
    }

    /**
     * Seller earnings for a date range, from the immutable snapshot on the
     * order rather than a separate cash-book table.
     */
    public function earningsBetween(int $sellerId, string $from, string $to): int
    {
        $row = $this->newQuery()
            ->selectSum('seller_earning', 'total')
            ->where('seller_id', $sellerId)
            ->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_COMPLETED])
            ->where('placed_at >=', $from)
            ->where('placed_at <=', $to)
            ->get()
            ->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Order count grouped by destination regency, for the dashboard map.
     *
     * @return list<array<string, mixed>>
     */
    public function buyerGeography(int $sellerId, string $from, string $to): array
    {
        $builder = $this->newQuery()
            ->select('ship_regency_id AS regency_id, COUNT(*) AS order_count, SUM(grand_total) AS revenue_total')
            ->where('seller_id', $sellerId)
            // `!= NULL` never matches in SQL, so the IS NOT NULL test is explicit.
            ->where('ship_regency_id IS NOT NULL', null, false)
            ->where('placed_at >=', $from)
            ->where('placed_at <=', $to)
            ->groupBy('ship_regency_id')
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