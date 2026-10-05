<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ReviewModel;
use App\Models\SellerProfileModel;

/**
 * Reviews a customer leaves for a delivered order.
 *
 * The route carries the order id rather than a product id, because a review is
 * only valid for something the customer actually bought. The shipped form is
 * one block per order item, posting `order_item_id`, a scalar `rating` and a
 * `body`; every id is resolved against the order the customer already owns, so
 * a posted id can never attach a review to another order's product.
 */
class ReviewController extends BaseController
{
    public function store(int $orderId)
    {
        $userId = $this->requireUserId();
        $order  = $this->orderService->findForUser($orderId, $userId);

        if ($order === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->orderService->canReview($order, $userId)) {
            return redirect()
                ->route('order_show', [$orderId])
                ->with('error', 'Ulasan hanya bisa diberikan untuk pesanan yang sudah diterima.');
        }

        // The posted item must belong to *this* order.
        $itemId = (int) $this->request->getPost('order_item_id');
        $item   = null;

        foreach ($order['items'] as $candidate) {
            if ((int) $candidate['id'] === $itemId) {
                $item = $candidate;
                break;
            }
        }

        if ($item === null) {
            return redirect()
                ->route('order_show', [$orderId])
                ->with('error', 'Produk ini tidak termasuk dalam pesanan tersebut.');
        }

        $reviews = new ReviewModel();

        // Re-submitting the same form must not duplicate a review.
        if ($reviews->findForOrderItem($itemId) !== null) {
            return redirect()
                ->route('order_show', [$orderId])
                ->with('error', 'Ulasan untuk produk ini sudah diberikan.');
        }

        $rating = $this->request->getPost('rating');

        if (! is_numeric($rating) || (int) $rating < 1 || (int) $rating > 5) {
            return redirect()
                ->route('order_show', [$orderId])
                ->withInput()
                ->with('error', 'Pilih nilai bintang untuk produk ini.');
        }

        $body = trim((string) $this->request->getPost('body'));

        if (mb_strlen($body) > 1000) {
            return redirect()
                ->route('order_show', [$orderId])
                ->withInput()
                ->with('error', 'Ulasan maksimal 1000 karakter.');
        }

        $now     = date('Y-m-d H:i:s');
        $db      = db_connect();
        $sellerId = (int) $order['seller_id'];

        $db->transStart();

        $reviews->insertRow([
            'product_id'    => (int) $item['product_id'],
            'order_id'      => $orderId,
            'order_item_id' => $itemId,
            'customer_id'   => $userId,
            'seller_id'     => $sellerId,
            'rating'        => (int) $rating,
            'body'          => $body === '' ? null : $body,
            'status'        => ReviewModel::STATUS_PUBLISHED,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        $this->refreshSellerRating($sellerId, $now);

        // The product page renders `products.rating_average` / `rating_count`,
        // so both aggregates must move together with the new row.
        $reviews->refreshProductRating((int) $item['product_id']);

        if ($db->transStatus() === false) {
            $db->transRollback();

            return redirect()->route('order_show', [$orderId])
                ->with('error', 'Ulasan gagal disimpan. Silakan coba lagi.');
        }

        $db->transCommit();

        return redirect()
            ->route('order_show', [$orderId])
            ->with('success', 'Terima kasih, ulasan Anda tersimpan.');
    }

    /**
     * Recompute the seller's cached rating from published reviews.
     *
     * Written in SQL so it reflects every review rather than only the new one,
     * and so two concurrent reviews cannot leave a stale average behind.
     */
    private function refreshSellerRating(int $sellerId, string $now): void
    {
        $row = db_connect()->table('reviews')
            ->select('AVG(rating) AS average, COUNT(*) AS total', false)
            ->where('seller_id', $sellerId)
            ->where('status', ReviewModel::STATUS_PUBLISHED)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        $average = $row === null ? 0.0 : (float) $row['average'];
        $count   = $row === null ? 0 : (int) $row['total'];

        (new SellerProfileModel())->newQuery()
            ->where('user_id', $sellerId)
            ->set([
                'rating_average' => round($average, 2),
                'rating_count'   => $count,
                'updated_at'     => $now,
            ])
            ->update();
    }
}
