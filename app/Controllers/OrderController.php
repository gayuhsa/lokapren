<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\OrderModel;
use App\Services\OrderService;

/**
 * The customer's own order history and order detail.
 *
 * Ownership is resolved by `OrderService::findForUser()`, which checks the
 * order's `customer_id` against the signed-in user. The `:num` in the route is
 * only a lookup hint — an order belonging to someone else is a 404, not a 403,
 * so the page never confirms that another customer's order exists.
 */
class OrderController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();

        $bucket = (string) $this->request->getGet('status');
        $page   = max(1, (int) $this->request->getGet('page'));
        $limit  = 10;

        $orders = new OrderModel();

        // The tab groups are resolved against the model's own status constants,
        // so an unknown `?status=` cannot widen the result set.
        $statuses = [];
        foreach (OrderService::CUSTOMER_BUCKETS as $key => $group) {
            if ($key === $bucket) {
                $statuses = $group;
                break;
            }
        }

        if ($bucket !== '' && $bucket !== 'all' && $statuses === []) {
            $statuses = ['__no_such_status__'];
        }

        $rows = $orders->forCustomerHistory($userId, $limit, ($page - 1) * $limit, $statuses);
        $total = $orders->newQuery()->where('customer_id', $userId);
        if ($statuses !== []) {
            $total->whereIn('status', $statuses);
        }
        $totalRows = $total->countAllResults();

        return view('orders/index', [
            'title'       => 'Pesanan Saya',
            'orders'      => $rows,
            'status'      => $bucket === '' ? 'all' : $bucket,
            'buckets'     => OrderService::CUSTOMER_BUCKETS,
            'counts'      => $orders->statusCountsForCustomer($userId, OrderService::CUSTOMER_BUCKETS),
            'page'        => $page,
            'total'       => $totalRows,
            'pages'       => max(1, (int) ceil($totalRows / $limit)),
            'cart_count'  => $this->cartCount(),
            'unread_chat' => $this->unreadChatCount(),
        ]);
    }

    public function show(int $id)
    {
        $userId = $this->requireUserId();
        $order  = $this->orderService->findForUser($id, $userId);

        if ($order === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('orders/show', [
            'title'       => 'Pesanan ' . $order['order_number'],
            'order'       => $order,
            'can_review'  => $this->orderService->canReview($order, $userId),
            'cart_count'  => $this->cartCount(),
            'unread_chat' => $this->unreadChatCount(),
        ]);
    }

    /**
     * The customer cancels their own order.
     *
     * Only `pending`, `paid` and `processing` are cancellable, and that check
     * lives in the model transition map. The reason is text and is escaped in
     * the view.
     */
    public function cancel(int $id)
    {
        $userId = $this->requireUserId();

        $result = $this->orderService->cancelForUser(
            $id,
            $userId,
            (string) $this->request->getPost('reason')
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()
            ->route('order_show', [$id])
            ->with('success', 'Pesanan dibatalkan.');
    }

    /**
     * Opens (or reuses) the conversation about this order's seller.
     *
     * The seller id is read from the order the customer already owns, never
     * from the request.
     */
    public function createChat(int $id)
    {
        $userId = $this->requireUserId();
        $order  = $this->orderService->findForUser($id, $userId);

        if ($order === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Reuse an existing thread with this seller when one exists so the
        // customer does not end up with a duplicate conversation per order.
        $conversation = $this->chatService->findOrCreateWithSeller($userId, (int) $order['seller_id']);

        // `null` only happens when both parties are the same user, which the
        // state machine above already prevents — but a fatal error on a null
        // index would be a poor answer, so it degrades to the inbox instead.
        if ($conversation === null) {
            return redirect()->route('chat_index')->with('error', 'Percakapan tidak dapat dibuka.');
        }

        return redirect()->route('chat_thread', [(int) $conversation['id']]);
    }

    /**
     * Public tracking view for a single order.
     */
    public function tracking(int $id)
    {
        $userId = $this->requireUserId();
        $order  = $this->orderService->findForUser($id, $userId);

        if ($order === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('orders/tracking', [
            'title'      => 'Lacak ' . $order['order_number'],
            'order'      => $order,
            'cart_count' => $this->cartCount(),
        ]);
    }
}
