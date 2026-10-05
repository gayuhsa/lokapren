<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Append-only audit of order status transitions, written by
 * {@see OrderModel::transition()}.
 *
 * There is no `update()` path in application use: if a row needs changing, the
 * order was updated incorrectly.
 */
class OrderStatusHistoryModel extends BaseModel
{
    protected $table = 'order_status_history';

    protected $returnType = 'array';

    /**
     * Only `created_at` exists and rows are never modified.
     */
    protected $useTimestamps = false;

    protected $allowedFields = [
        'order_id',
        'from_status',
        'to_status',
        'note',
        'actor_id',
    ];

    protected $validationRules = [
        'order_id'    => 'required|is_natural_no_zero',
        'from_status' => 'permit_empty|max_length[20]',
        'to_status'   => 'required|max_length[20]',
        'note'        => 'permit_empty|max_length[255]',
        'actor_id'    => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * Transition trail for an order, oldest first.
     */
    public function forOrder(int $orderId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('order_id', $orderId)
                ->orderBy('created_at', 'ASC')
                ->orderBy('id', 'ASC')
        );
    }

    /**
     * Transitions a specific actor performed, for audit tooling.
     *
     * @return list<array<string, mixed>>
     */
    public function byActor(int $actorId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('actor_id', $actorId)
                ->orderBy('created_at', 'DESC')
        );
    }
}