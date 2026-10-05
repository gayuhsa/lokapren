<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Product review, one per purchased order line.
 *
 * `UNIQUE (order_id, product_id)` plus `UNIQUE order_item_id` means purchase is
 * structurally provable and a customer cannot review the same line twice.
 *
 * `customer_id` and `seller_id` are server-owned: they are copied from the
 * order being reviewed, never from the request.
 */
class ReviewModel extends BaseModel
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'reviews';

    protected $returnType = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'rating',
        'title',
        'body',
        'seller_reply',
    ];

    protected array $casts = [
        'rating' => 'int',
    ];

    protected $validationRules = [
        'order_id'      => 'required|is_natural_no_zero',
        'order_item_id' => 'required|is_natural_no_zero',
        'product_id'    => 'required|is_natural_no_zero',
        'rating'        => 'required|is_natural|greater_than[0]|less_than_equal_to[5]',
        'title'         => 'permit_empty|max_length[150]',
        'body'          => 'permit_empty|max_length[5000]',
    ];

    /**
     * Published reviews for a product, newest first.
     *
     * The reviewer's display name comes from `user_profiles` so the product
     * page can show "Budi dari CHAPTER I" without a second query; `users.id` is
     * never rendered.
     *
     * @return list<array<string, mixed>>
     */
    public function publishedFor(int $productId, int $limit = 20, int $offset = 0): array
    {
        return $this->newRows(
            $this->newQuery()
                ->select('reviews.*, user_profiles.full_name AS customer_full_name, user_profiles.photo_path AS customer_photo')
                ->join('user_profiles', 'user_profiles.user_id = reviews.customer_id', 'left')
                ->where('reviews.product_id', $productId)
                ->where('reviews.status', self::STATUS_PUBLISHED)
                ->orderBy('reviews.created_at', 'DESC'),
            $limit,
            $offset
        );
    }

    /**
     * Newest reviews across every product a seller owns, for the storefront
     * and the profile page.
     *
     * @return list<array<string, mixed>>
     */
    public function latestForSeller(int $sellerId, int $limit = 6): array
    {
        return $this->newRows(
            $this->newQuery()
                ->select('reviews.*, products.name AS product_name, products.slug AS product_slug, user_profiles.full_name AS customer_full_name, user_profiles.photo_path AS customer_photo')
                ->join('products', 'products.id = reviews.product_id', 'left')
                ->join('user_profiles', 'user_profiles.user_id = reviews.customer_id', 'left')
                ->where('products.seller_id', $sellerId)
                ->where('reviews.status', self::STATUS_PUBLISHED)
                // `products` and `reviews` both have a `deleted_at`, so the
                // column has to be qualified once `products` is joined in.
                ->where('reviews.deleted_at', null)
                ->orderBy('reviews.created_at', 'DESC'),
            $limit
        );
    }

    /**
     * The review a customer already left for an order line, if any.
     *
     * @return array<string, mixed>|null
     */
    public function findForOrderItem(int $orderItemId): ?array
    {
        return $this->newRow(
            $this->newQuery()->where('order_item_id', $orderItemId)
        );
    }

    /**
     * Order lines of one order that already carry a review, so the order page
     * can hide the form for them instead of re-offering a duplicate submit.
     *
     * @return list<int>
     */
    public function reviewedItemIds(int $orderId): array
    {
        $rows = $this->newQuery()
            ->select('order_item_id')
            ->where('order_id', $orderId)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): int => (int) $row['order_item_id'], $rows);
    }

    /**
     * Star breakdown for the catalog's rating histogram.
     *
     * @return array<int, int> keys 1..5
     */
    public function ratingBreakdown(int $productId): array
    {
        $rows = $this->newRows(
            $this->newQuery()
                ->select('rating, COUNT(*) AS total')
                ->where('product_id', $productId)
                ->where('status', self::STATUS_PUBLISHED)
                ->groupBy('rating')
        );

        $breakdown = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($rows as $row) {
            $breakdown[(int) $row['rating']] = (int) $row['total'];
        }

        return $breakdown;
    }

    /**
     * Recompute and persist `products.rating_average` / `rating_count`.
     *
     * The aggregate is integer maths in SQL so the stored value never depends
     * on floating-point rounding. The write targets `products`, not `reviews`,
     * so it goes through its own builder rather than this model's.
     */
    public function refreshProductRating(int $productId): void
    {
        $row = $this->newQuery()
            ->selectSum('rating', 'rating_sum')
            ->select('COUNT(*) AS rating_count')
            ->where('product_id', $productId)
            ->where('status', self::STATUS_PUBLISHED)
            ->get()
            ->getRowArray();

        $count = (int) ($row['rating_count'] ?? 0);

        if ($count === 0) {
            $this->db->table('products')
                ->where('id', $productId)
                ->update(['rating_average' => 0, 'rating_count' => 0]);

            return;
        }

        // Two decimal places, truncated rather than rounded, in integer maths.
        $hundredths = (int) ((int) $row['rating_sum'] * 100 / $count);

        $this->db->table('products')
            ->where('id', $productId)
            ->update([
                'rating_average' => sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100),
                'rating_count'   => $count,
            ]);
    }
}