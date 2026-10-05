<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductCategoryModel;
use App\Models\ReviewModel;

/**
 * Shared data for the header, footer and any view that renders them.
 *
 * Keeping this in one place means the cart and chat badges are resolved the
 * same way everywhere, and both are read through services that already scope by
 * the signed-in user.
 */
class LayoutController extends BaseController
{
    /**
     * The product categories shown in the header's craft menu.
     *
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return (new ProductCategoryModel())->withProductCounts();
    }

    /**
     * A short list of verified sanggar for the footer and the home page.
     *
     * @return list<array<string, mixed>>
     */
    public function sanggar(int $limit = 6): array
    {
        $sellers = new \App\Models\SellerProfileModel();

        return $sellers->newRows(
            $sellers->newQuery()
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->where('is_verified', 1)
                ->orderBy('rating_average', 'DESC'),
            $limit
        );
    }

    /**
     * The newest reviews for the home page's social-proof band.
     *
     * @return list<array<string, mixed>>
     */
    public function latestReviews(int $limit = 6): array
    {
        $reviews = new ReviewModel();

        return $reviews->newRows(
            $reviews->newQuery()
                ->select('reviews.*, products.name AS product_name, products.slug AS product_slug, user_profiles.full_name AS customer_name, user_profiles.photo_path AS customer_photo')
                ->join('products', 'products.id = reviews.product_id', 'left')
                ->join('user_profiles', 'user_profiles.user_id = reviews.customer_id', 'left')
                ->where('reviews.status', ReviewModel::STATUS_PUBLISHED)
                ->orderBy('reviews.created_at', 'DESC'),
            $limit
        );
    }
}
