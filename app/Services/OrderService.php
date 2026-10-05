<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderShipmentModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\ProductModel;
use App\Models\ReviewModel;
use App\Models\SellerDailyStatModel;
use App\Models\SellerProfileModel;

/**
 * Order reads and status transitions for both sides of the trade.
 *
 * Ownership is resolved before any write: a customer may only read their own
 * order, a seller may only read and advance an order that belongs to their
 * sanggar. The state machine itself lives on `OrderModel` so it cannot be
 * bypassed, and this service adds the two rules a model should not know about —
 * who is allowed to make the change, and which timestamps the change implies.
 */
class OrderService
{
    /**
     * Transitions a seller is *not* offered in the status form.
     *
     * `OrderModel::TRANSITIONS` already decides what is reachable; this map
     * only hides the targets the seller UI should not offer, so a seller never
     * sees "Dibatalkan" as a step they can pick (the order detail page still
     * offers a separate cancel action for the states that allow it).
     *
     * @var array<string, list<string>>
     */
    private const SELLER_HIDDEN_ACTIONS = [
        'cancel' => [OrderModel::STATUS_CANCELLED],
    ];

    private OrderModel $orders;
    private OrderItemModel $items;
    private OrderShipmentModel $shipments;
    private OrderStatusHistoryModel $history;
    private ReviewModel $reviews;
    private SellerDailyStatModel $stats;
    private SellerProfileModel $sellers;
    private ProductModel $products;

    public function __construct()
    {
        $this->orders    = new OrderModel();
        $this->items     = new OrderItemModel();
        $this->shipments = new OrderShipmentModel();
        $this->history   = new OrderStatusHistoryModel();
        $this->reviews   = new ReviewModel();
        $this->stats     = new SellerDailyStatModel();
        $this->sellers   = new SellerProfileModel();
        $this->products  = new ProductModel();
    }

    /**
     * The buyer's order-history tabs, mapped to real order statuses.
     *
     * The tab key is the only thing a request may choose; the statuses behind
     * it live here so a crafted `?status=` cannot select an arbitrary set.
     *
     * @var array<string, list<string>>
     */
    public const CUSTOMER_BUCKETS = [
        'all'        => [],
        'unpaid'     => [OrderModel::STATUS_PENDING_PAYMENT],
        'processing' => [
            OrderModel::STATUS_AWAITING_ARTISAN,
            OrderModel::STATUS_IN_PRODUCTION,
            OrderModel::STATUS_READY_TO_SHIP,
        ],
        'shipped'    => [OrderModel::STATUS_SHIPPED],
        'done'       => [OrderModel::STATUS_DELIVERED, OrderModel::STATUS_COMPLETED],
        'cancelled'  => [OrderModel::STATUS_CANCELLED, OrderModel::STATUS_REFUNDED],
    ];

    /**
     * Status labels in Indonesian for the buyer-facing timeline.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            OrderModel::STATUS_PENDING_PAYMENT  => 'Menunggu Pembayaran',
            OrderModel::STATUS_AWAITING_ARTISAN => 'Menunggu Pengerjaan Artisan',
            OrderModel::STATUS_IN_PRODUCTION    => 'Sedang Dikerjakan',
            OrderModel::STATUS_READY_TO_SHIP    => 'Siap Dikirim',
            OrderModel::STATUS_SHIPPED          => 'Dalam Pengiriman',
            OrderModel::STATUS_DELIVERED        => 'Sudah Diterima',
            OrderModel::STATUS_COMPLETED        => 'Selesai',
            OrderModel::STATUS_CANCELLED        => 'Dibatalkan',
            OrderModel::STATUS_REFUNDED         => 'Dana Dikembalikan',
        ];
    }

    /**
     * The step a given status represents in the buyer's progress bar.
     *
     * @return array<string, array{label: string, step: int, total: int}>
     */
    public static function buyerProgress(): array
    {
        $total = 5;

        return [
            OrderModel::STATUS_PENDING_PAYMENT  => ['label' => 'Menunggu Pembayaran', 'step' => 1, 'total' => $total],
            OrderModel::STATUS_AWAITING_ARTISAN => ['label' => 'Diterima Sanggar', 'step' => 2, 'total' => $total],
            OrderModel::STATUS_IN_PRODUCTION    => ['label' => 'Sedang Dikerjakan', 'step' => 3, 'total' => $total],
            OrderModel::STATUS_READY_TO_SHIP    => ['label' => 'Siap Dikirim', 'step' => 4, 'total' => $total],
            OrderModel::STATUS_SHIPPED          => ['label' => 'Dalam Pengiriman', 'step' => 5, 'total' => $total],
            OrderModel::STATUS_DELIVERED        => ['label' => 'Diterima', 'step' => 5, 'total' => $total],
            OrderModel::STATUS_COMPLETED        => ['label' => 'Selesai', 'step' => 5, 'total' => $total],
            OrderModel::STATUS_CANCELLED        => ['label' => 'Dibatalkan', 'step' => 0, 'total' => $total],
            OrderModel::STATUS_REFUNDED         => ['label' => 'Dana Dikembalikan', 'step' => 0, 'total' => $total],
        ];
    }

    /**
     * The seller's progress rail, which starts at "order received" rather than
     * at payment.
     *
     * @return array<string, array{label: string, step: int, total: int}>
     */
    public static function sellerProgress(): array
    {
        $total = 4;

        return [
            OrderModel::STATUS_PENDING_PAYMENT  => ['label' => 'Pesanan Masuk', 'step' => 1, 'total' => $total],
            OrderModel::STATUS_AWAITING_ARTISAN => ['label' => 'Diterima Sanggar', 'step' => 2, 'total' => $total],
            OrderModel::STATUS_IN_PRODUCTION    => ['label' => 'Sedang Dikerjakan', 'step' => 3, 'total' => $total],
            OrderModel::STATUS_READY_TO_SHIP    => ['label' => 'Siap Dikirim', 'step' => 4, 'total' => $total],
            OrderModel::STATUS_SHIPPED          => ['label' => 'Dalam Pengiriman', 'step' => 4, 'total' => $total],
            OrderModel::STATUS_DELIVERED        => ['label' => 'Diterima Pembeli', 'step' => 4, 'total' => $total],
            OrderModel::STATUS_COMPLETED        => ['label' => 'Selesai', 'step' => 4, 'total' => $total],
            OrderModel::STATUS_CANCELLED        => ['label' => 'Dibatalkan', 'step' => 0, 'total' => $total],
            OrderModel::STATUS_REFUNDED         => ['label' => 'Dana Dikembalikan', 'step' => 0, 'total' => $total],
        ];
    }

    /**
     * One order with its items, shipment and status history, loaded only when
     * the caller is the buyer or the seller.
     *
     * @return array<string, mixed>|null Null when the order does not exist or
     *                                    the caller is not a party to it.
     */
    public function findForUser(int $orderId, int $userId): ?array
    {
        $order = $this->orders->find($orderId);

        if ($order === null) {
            return null;
        }

        $isCustomer = (int) $order['customer_id'] === $userId;
        $isSeller   = (int) $order['seller_id'] === $userId;

        if (! $isCustomer && ! $isSeller) {
            return null;
        }

        return $this->decorate($order, $userId, $isSeller ? 'seller' : 'customer');
    }

    /**
     * Load an order for the seller who owns it.
     *
     * Same ownership rule as `findForUser()`: a null return means "not yours",
     * and the controller turns that into a 404.
     *
     * @return array<string, mixed>|null
     */
    public function findForSeller(int $orderId, int $sellerId): ?array
    {
        $order = $this->orders->findForSeller($orderId, $sellerId);

        if ($order === null) {
            return null;
        }

        return $this->decorate($order, $sellerId, 'seller');
    }

    /**
     * The seller's order list for a dashboard bucket.
     *
     * @return list<array<string, mixed>>
     */
    public function listForSeller(int $sellerId, string $bucket = 'all', int $limit = 20, int $offset = 0): array
    {
        $rows = $this->orders->forSellerDashboard($sellerId, $bucket, $limit, $offset);

        foreach ($rows as $index => $row) {
            $rows[$index] = $this->decorate($row, $sellerId, 'seller');
        }

        return $rows;
    }

    /**
     * How many orders sit in each seller dashboard bucket.
     *
     * Counted in one grouped query rather than one query per bucket.
     *
     * @return array<string, int>
     */
    public function statusCountsFor(int $sellerId): array
    {
        $counts = ['all' => 0, 'to_ship' => 0, 'in_transit' => 0, 'done' => 0, 'cancelled' => 0];

        $rows = $this->orders->newQuery()
            ->select('status, COUNT(*) AS total', false)
            ->where('seller_id', $sellerId)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $counts['all'] += $total;

            $bucket = match ((string) $row['status']) {
                OrderModel::STATUS_AWAITING_ARTISAN,
                OrderModel::STATUS_IN_PRODUCTION,
                OrderModel::STATUS_READY_TO_SHIP => 'to_ship',
                OrderModel::STATUS_SHIPPED           => 'in_transit',
                OrderModel::STATUS_DELIVERED,
                OrderModel::STATUS_COMPLETED         => 'done',
                OrderModel::STATUS_CANCELLED         => 'cancelled',
                default                              => null,
            };

            if ($bucket !== null) {
                $counts[$bucket] += $total;
            }
        }

        return $counts;
    }

    /**
     * Attach the child rows and the viewer-specific labels to an order.
     *
     * @return array<string, mixed>
     */
    private function decorate(array $order, int $userId, string $viewer): array
    {
        $order['items']        = $this->items->forOrder((int) $order['id']);
        $order['shipment']     = $this->shipments->forOrder((int) $order['id']);
        $order['history']      = $this->history->forOrder((int) $order['id']);
        $order['viewer']       = $viewer;
        $order['can_review']   = $this->canReview($order, $userId);
        // Which lines already carry a review, so the order page only offers
        // the form for the ones that are still waiting for one.
        $order['reviewed_item_ids'] = $this->reviews->reviewedItemIds((int) $order['id']);
        $order['status_label'] = self::statusLabels()[$order['status']] ?? (string) $order['status'];

        // The sanggar behind the order, so both the buyer's detail page and the
        // seller's own page can name the shop without a second lookup.
        $shop                   = $this->sellers->findForUser((int) $order['seller_id']);
        $order['shop_name']     = $shop['display_name'] ?? null;
        $order['shop_slug']     = $shop['slug'] ?? null;
        $order['shop_logo']     = $shop['logo_path'] ?? null;

        if ($viewer === 'customer') {
            $order['progress'] = self::buyerProgress()[$order['status']]
                ?? ['label' => $order['status'], 'step' => 0, 'total' => 5];
        } else {
            $order['progress'] = self::sellerProgress()[$order['status']]
                ?? ['label' => $order['status'], 'step' => 0, 'total' => 4];
            // Only the actions the state machine allows from here, with their
            // Indonesian labels, so the view renders a form the seller can
            // actually submit.
            $order['actions'] = $this->sellerActionsFor($order);
        }

        return $order;
    }

    /**
     * Advance an order as the seller who owns it.
     *
     * @param array{tracking_number?: string|null, production_progress?: int|null, note?: string|null} $input
     *
     * @return array{ok: bool, message: string}
     */
    public function advanceAsSeller(int $orderId, int $sellerId, string $toStatus, array $input = []): array
    {
        $order = $this->orders->findForSeller($orderId, $sellerId);

        if ($order === null) {
            return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }

        if (! OrderModel::canTransition((string) $order['status'], $toStatus)) {
            return [
                'ok'      => false,
                'message' => 'Status tidak dapat diubah dari ' . (self::statusLabels()[$order['status']] ?? $order['status']) . '.',
            ];
        }

        $tracking = isset($input['tracking_number']) ? trim((string) $input['tracking_number']) : null;
        $progress = isset($input['production_progress']) ? (int) $input['production_progress'] : null;
        $note     = isset($input['note']) ? trim((string) $input['note']) : null;

        $this->orders->transStart();

        // Production progress is the seller's own working note, so it is
        // clamped to a sane band before being written.
        if ($progress !== null) {
            $this->orders->updateWhere(
                ['production_progress' => max(0, min(100, $progress))],
                ['id' => $orderId]
            );
        }

        if ($tracking !== null && $tracking !== '' && $toStatus === OrderModel::STATUS_SHIPPED) {
            $shippedAt = date('Y-m-d H:i:s');

            // Checkout creates the shipment row, but an order seeded or staged
            // mid-flight may reach `shipped` without one — the resi must not be
            // dropped on the floor in that case.
            $shipment = $this->shipments->newRow(
                $this->shipments->newQuery()->where('order_id', $orderId)
            );

            if ($shipment === null) {
                $this->shipments->insertRow([
                    'order_id'        => $orderId,
                    'tracking_number' => $tracking,
                    'shipped_at'      => $shippedAt,
                    'created_at'      => $shippedAt,
                ]);
            } else {
                $this->shipments->updateWhere(
                    ['tracking_number' => $tracking, 'shipped_at' => $shippedAt],
                    ['order_id' => $orderId]
                );
            }
        }

        $ok = $this->orders->transition($orderId, $toStatus, $sellerId, $note);

        if (! $ok) {
            $this->orders->transRollback();

            return ['ok' => false, 'message' => 'Status gagal diperbarui.'];
        }

        // Revenue is counted once per order, on its first arrival at `shipped` —
        // the point the goods have actually left the seller. Checkout does not
        // count it, because an order can still be cancelled afterwards. The
        // seller's own earning is used rather than the customer total, so a
        // future platform fee does not inflate a sanggar's omzet.
        if ($toStatus === OrderModel::STATUS_SHIPPED) {
            $this->stats->increment($sellerId, $this->stats->today(), [
                'revenue_total' => (int) $order['seller_earning'],
            ]);
        }

        // Completion is also the moment a seller earns the order for good.
        if ($toStatus === OrderModel::STATUS_COMPLETED) {
            $this->stats->increment($sellerId, $this->stats->today(), [
                'completed_count' => 1,
            ]);
        }

        $this->orders->transCommit();

        return ['ok' => true, 'message' => 'Status pesanan diperbarui.'];
    }

    /**
     * Cancel an order. Both the buyer and the seller may do this while the
     * order has not shipped yet, which is what the state machine allows.
     *
     * @return array{ok: bool, message: string}
     */
    public function cancelForUser(int $orderId, int $userId, ?string $reason = null): array
    {
        $order = $this->findForUser($orderId, $userId);

        if ($order === null) {
            return ['ok' => false, 'message' => 'Pesanan tidak ditemukan.'];
        }

        if (! OrderModel::canTransition((string) $order['status'], OrderModel::STATUS_CANCELLED)) {
            return ['ok' => false, 'message' => 'Pesanan yang sudah dikirim tidak dapat dibatalkan.'];
        }

        $this->orders->transStart();

        $ok = $this->orders->transition(
            $orderId,
            OrderModel::STATUS_CANCELLED,
            $userId,
            $reason ?? 'Dibatalkan oleh ' . ($order['viewer'] === 'seller' ? 'sanggar' : 'pembeli') . '.'
        );

        if (! $ok) {
            $this->orders->transRollback();

            return ['ok' => false, 'message' => 'Pesanan gagal dibatalkan.'];
        }

        // Checkout took the stock away and counted the order, so cancelling has
        // to give both back — otherwise a cancelled order permanently shrinks
        // the sanggar's inventory and overstates their daily order count. A
        // made-to-order piece never had stock decremented, so nothing returns.
        $this->restockItems($order);

        // The counter has to come off the day checkout added it, not the day the
        // buyer cancelled. An order placed before midnight and cancelled after
        // would otherwise create a new row going negative and leave yesterday's
        // count too high.
        $placedOn = substr((string) ($order['created_at'] ?? ''), 0, 10);
        $statDate = $placedOn !== '' ? $placedOn : $this->stats->today();

        if ($this->hasPlacedOrderCount($order)) {
            $this->stats->increment((int) $order['seller_id'], $statDate, [
                'order_count' => -1,
            ]);
        }

        $this->orders->transCommit();

        return ['ok' => true, 'message' => 'Pesanan dibatalkan.'];
    }

    /**
     * Whether checkout actually counted this order on the given day.
     *
     * Checkout increments `order_count` only for orders that reached it, so a
     * cancellation may only reverse a row that exists. Checking first keeps the
     * counter from going negative on a day that has no row yet.
     *
     * @param array<string, mixed> $order
     */
    private function hasPlacedOrderCount(array $order): bool
    {
        $placedOn = substr((string) ($order['created_at'] ?? ''), 0, 10);

        if ($placedOn === '') {
            return false;
        }

        return $this->stats->newQuery()
            ->select('order_count')
            ->where('seller_id', (int) $order['seller_id'])
            ->where('stat_date', $placedOn)
            ->countAllResults() > 0;
    }

    /**
     * Put an order's reserved stock back after a cancellation.
     *
     * @param array<string, mixed> $order A decorated order, so `items` is present.
     */
    private function restockItems(array $order): void
    {
        $db    = db_connect();
        $stamp = date('Y-m-d H:i:s');

        foreach ($order['items'] as $item) {
            $product = $this->products->find((int) $item['product_id']);

            if ($product === null || $product['made_to_order']) {
                continue;
            }

            $quantity = (int) $item['quantity'];

            // Variant stock is authoritative when the product tracks variants,
            // so the variant row is the one to return the quantity to.
            if ($item['variant_id'] !== null) {
                $db->table('product_variants')
                    ->set('stock', 'stock + ' . $quantity, false)
                    ->set('updated_at', $stamp)
                    ->where('id', (int) $item['variant_id'])
                    ->update();
            } else {
                $db->table('products')
                    ->set('stock', 'stock + ' . $quantity, false)
                    ->set('updated_at', $stamp)
                    ->where('id', (int) $item['product_id'])
                    ->update();
            }

            // Checkout bumps this counter, so cancelling has to take it back or
            // a cancelled order would keep counting towards "terlaris".
            $db->table('products')
                ->set('sold_count', 'sold_count - ' . $quantity, false)
                ->where('id', (int) $item['product_id'])
                ->update();
        }
    }

    /**
     * The status options a seller may pick from for this order's current state.
     *
     * @return list<array{value: string, label: string}>
     */
    public function sellerActionsFor(array $order): array
    {
        $options = [];

        foreach (OrderModel::allowedTransitionsFrom((string) $order['status']) as $target) {
            if (! in_array($target, self::SELLER_HIDDEN_ACTIONS['cancel'], true)) {
                $options[] = ['value' => $target, 'label' => self::statusLabels()[$target] ?? $target];
            }
        }

        return $options;
    }

    /**
     * Whether this user may still review this order.
     *
     * Reviews are gated on a real purchase: the buyer must have an order that
     * reached at least "delivered", and a review may only be written once per
     * order item.
     */
    public function canReview(array $order, int $userId): bool
    {
        if ($order['viewer'] !== 'customer' || (int) $order['customer_id'] !== $userId) {
            return false;
        }

        if (! in_array($order['status'], [
            OrderModel::STATUS_DELIVERED,
            OrderModel::STATUS_COMPLETED,
        ], true)) {
            return false;
        }

        foreach ($order['items'] as $item) {
            if (! $this->reviews->findForOrderItem((int) $item['id'])) {
                return true;
            }
        }

        return false;
    }
}
