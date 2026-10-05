<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Order line items.
 *
 * This table is a pure snapshot of what was bought: name, variant label, SKU and
 * image are copied at checkout so a later product edit cannot alter a historical
 * invoice (AGENTS.md: order totals must come from trusted data).
 *
 * Nothing here is user-editable — `$allowedFields` is empty by design, so the
 * rows can only be written by the checkout service.
 */
class OrderItemModel extends BaseModel
{
    protected $table = 'order_items';

    protected $returnType = 'array';

    /**
     * The table only carries `created_at`; there is nothing to update after
     * insert, so timestamps are disabled.
     */
    protected $useTimestamps = false;

    protected $allowedFields = [];

    protected $useSoftDeletes = false;

    protected array $casts = [
        'unit_price' => 'int',
        'quantity'   => 'int',
        'subtotal'   => 'int',
    ];

    /**
     * Lines for an order.
     *
     * @return list<array<string, mixed>>
     */
    public function forOrder(int $orderId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('order_id', $orderId)
                ->orderBy('id', 'ASC')
        );
    }

    /**
     * Order total recomputed from the stored lines, for cross-checking the
     * `orders.subtotal` snapshot at read time.
     */
public function subtotalFor(int $orderId): int
    {
        $row = $this->newQuery()
            ->select('SUM(quantity * unit_price) AS subtotal')
            ->where('order_id', $orderId)
            ->get()
            ->getRowArray();

        return (int) ($row['subtotal'] ?? 0);
    }
    /**
     * Find the purchased line for a (order, product) pair.
     *
     * @return array<string, mixed>|null
     */
    public function findForProduct(int $orderId, int $productId): ?array
    {
        return $this->newRow(
            $this->newQuery()
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
        );
    }

    /**
     * Product ids in an order, for bulk stock/rating updates.
     *
     * @return list<int>
     */
    public function productIdsFor(int $orderId): array
    {
        $rows = $this->newRows(
            $this->newQuery()
                ->select('product_id')
                ->where('order_id', $orderId)
                ->groupBy('product_id')
        );

        return array_map(static fn (array $row): int => (int) $row['product_id'], $rows);
    }
}