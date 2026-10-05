<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A customer ↔ seller thread, optionally scoped to a product or an order.
 *
 * The table has exactly two participant columns and no group/permission model,
 * so access control is simply "is the caller `customer_id` or `seller_id`?"
 * (AGENTS.md: "Chat conversations may only be accessed by their participants".)
 */
class ConversationModel extends BaseModel
{
    protected $table = 'conversations';

    protected $returnType = 'array';

    protected $useSoftDeletes = false;

    /**
     * `customer_id` and `seller_id` are server-owned. Both come from the
     * authenticated Shield user and its conversation target, never the request.
     */
    protected $allowedFields = [
        'product_id',
        'order_id',
        'subject',
    ];

    protected array $casts = [
        'customer_id'          => 'int',
        'seller_id'            => 'int',
        'product_id'           => '?int',
        'order_id'             => '?int',
        'customer_unread_count' => 'int',
        'seller_unread_count'  => 'int',
    ];

    protected $validationRules = [
        'product_id' => 'permit_empty|is_natural_no_zero',
        'order_id'   => 'permit_empty|is_natural_no_zero',
        'subject'    => 'permit_empty|max_length[200]',
    ];

    /**
     * Locate the thread for a customer/seller/product triple.
     *
     * `UNIQUE (customer_id, seller_id, product_id)` does not collapse rows
     * where `product_id IS NULL` on MariaDB or SQLite, so the null comparison
     * is explicit here.
     */
    public function findThread(int $customerId, int $sellerId, ?int $productId = null): ?array
    {
        $builder = $this->newQuery()
            ->where('customer_id', $customerId)
            ->where('seller_id', $sellerId);

        if ($productId === null) {
            $builder->where('product_id', null);
        } else {
            $builder->where('product_id', $productId);
        }

        return $this->newRow($builder);
    }

    /**
     * Find or create the thread for a customer/seller/product triple.
     */
    public function firstOrCreateThread(int $customerId, int $sellerId, ?int $productId = null, ?int $orderId = null): int
    {
        $thread = $this->findThread($customerId, $sellerId, $productId);

        if ($thread !== null) {
            return (int) $thread['id'];
        }

        $now = date('Y-m-d H:i:s');

        return $this->insertRow([
            'customer_id' => $customerId,
            'seller_id'   => $sellerId,
            'product_id'  => $productId,
            'order_id'    => $orderId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    /**
     * Load a conversation only when `$userId` is one of its two participants.
     *
     * Returns null for a non-participant so the caller can 404 rather than
     * confirm that someone else's thread exists.
     */
    public function findForParticipant(int $conversationId, int $userId): ?array
    {
        $builder = $this->newQuery()
            ->where('id', $conversationId)
            ->groupStart()
            ->where('customer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->groupEnd();

        return $this->newRow($builder);
    }

    /**
     * Threads a user takes part in, most recently active first.
     *
     * @return list<array<string, mixed>>
     */
    public function forParticipant(int $userId, int $limit = 50, int $offset = 0): array
    {
        $builder = $this->newQuery()
            ->groupStart()
            ->where('customer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->groupEnd()
            ->orderBy('last_message_at', 'DESC')
            ->orderBy('id', 'DESC');

        return $this->newRows($builder, $limit, $offset);
    }

    /**
     * Refresh the thread header after a message is sent.
     *
     * The unread counter is bumped for the *other* participant, so a customer
     * sending a message raises `seller_unread_count` and vice versa.
     */
    public function touchLastMessage(int $conversationId, string $preview, bool $fromCustomer): void
    {
        $column = $fromCustomer ? 'seller_unread_count' : 'customer_unread_count';
        $now    = date('Y-m-d H:i:s');

        $this->newQuery()
            ->where('id', $conversationId)
            ->set([
                'last_message_at'     => $now,
                'last_message_preview' => mb_substr($preview, 0, 255),
                'updated_at'          => $now,
            ])
            // Raw SQL: this is an expression, not a literal to escape.
            ->set($column, $column . ' + 1', false)
            ->update();
    }

    /**
     * The unread counter belonging to `$userId` in this thread.
     */
    public function unreadCountFor(int $conversationId, int $userId): int
    {
        $conversation = $this->findForParticipant($conversationId, $userId);

        if ($conversation === null) {
            return 0;
        }

        return (int) $conversation['customer_id'] === $userId
            ? (int) $conversation['customer_unread_count']
            : (int) $conversation['seller_unread_count'];
    }

    /**
     * Clear the unread counter for one side of the thread.
     */
    public function markRead(int $conversationId, int $userId): void
    {
        $conversation = $this->findForParticipant($conversationId, $userId);

        if ($conversation === null) {
            return;
        }

        $column = (int) $conversation['customer_id'] === $userId
            ? 'customer_unread_count'
            : 'seller_unread_count';

        $this->updateWhere([$column => 0], ['id' => $conversationId]);
    }

    /**
     * Total unread messages across every thread a user takes part in.
     *
     * The counters live on this table with one column per participant, so a
     * user is either the customer or the seller in each thread — never both —
     * and the two sides are summed separately.
     */
    public function totalUnreadFor(int $userId): int
    {
        $customerSide = $this->newQuery()
            ->selectSum('customer_unread_count', 'total')
            ->where('customer_id', $userId)
            ->get()
            ->getRowArray();

        $sellerSide = $this->newQuery()
            ->selectSum('seller_unread_count', 'total')
            ->where('seller_id', $userId)
            ->get()
            ->getRowArray();

        return (int) ($customerSide['total'] ?? 0) + (int) ($sellerSide['total'] ?? 0);
    }
}