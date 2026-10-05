<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\OrderModel;

/**
 * The seller's order queue.
 *
 * The seller id comes from Shield and every lookup goes through
 * `OrderModel::findForSeller()` / `OrderService::advanceAsSeller()`, so an
 * order belonging to another seller is a 404 and a status change is rejected
 * unless the state machine allows it.
 */
class OrderController extends BaseController
{
    public function index()
    {
        $sellerId = $this->requireUserId();
        $bucket   = (string) ($this->request->getGet('status') ?: 'all');
        $page     = max(1, (int) $this->request->getGet('page'));
        $limit    = 20;

        return view('seller/orders/index', [
            'title'      => 'Pesanan Masuk',
            'orders'     => $this->orderService->listForSeller($sellerId, $bucket, $limit, ($page - 1) * $limit),
            'counts'     => $this->orderService->statusCountsFor($sellerId),
            'status'     => $bucket,
            'page'       => $page,
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function show(int $id)
    {
        $sellerId = $this->requireUserId();
        $order    = $this->orderService->findForSeller($id, $sellerId);

        if ($order === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('seller/orders/show', [
            'title'      => 'Pesanan ' . $order['order_number'],
            'order'      => $order,
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Move an order to its next state.
     *
     * `to_status` is not free text: it is checked against the model's transition
     * map, so a seller cannot invent a status or skip a required step, and the
     * tracking number is only stored when the order is actually being shipped.
     */
    public function updateStatus(int $id)
    {
        $sellerId  = $this->requireUserId();
        $toStatus  = (string) $this->request->getPost('to_status');

        if (! in_array($toStatus, OrderModel::statuses(), true)) {
            return redirect()
                ->back()
                ->with('error', 'Status tidak dikenal.');
        }

        $result = $this->orderService->advanceAsSeller($id, $sellerId, $toStatus, [
            'tracking_number'    => (string) $this->request->getPost('tracking_number') ?: null,
            'production_progress' => $this->request->getPost('production_progress') ?: null,
            'note'               => (string) $this->request->getPost('note') ?: null,
        ]);

        return redirect()
            ->route('seller_order_show', [$id])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
