<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductCategoryModel;
use App\Models\SellerDailyStatModel;
use App\Models\SellerProfileModel;

/**
 * The landing page.
 *
 * Everything here is public read-only data: featured products, highlighted
 * sanggar and the newest journal entries. No user input reaches a query on this
 * page.
 */
class HomeController extends BaseController
{
    public function index()
    {
        $products   = new \App\Models\ProductModel();
        $categories = new ProductCategoryModel();
        $sellers    = new SellerProfileModel();

        return view('home/index', [
            'title'      => 'Lokapren — Kriya Magelang dari Pengrajin Lokal',
            'featured'   => $products->newRows(
                $products->newQuery()
                    ->select('products.*, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.rating_average AS shop_rating, seller_profiles.rating_count AS shop_rating_count')
                    ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
                    ->where('products.status', \App\Models\ProductModel::STATUS_PUBLISHED)
                    ->where('products.is_active', 1)
                    ->where('products.deleted_at', null)
                    ->where('products.is_featured', 1)
                    ->orderBy('products.sold_count', 'DESC'),
                8
            ),
            'latest'     => $products->newRows(
                $products->newQuery()
                    ->select('products.*, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.rating_average AS shop_rating, seller_profiles.rating_count AS shop_rating_count')
                    ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
                    ->where('products.status', \App\Models\ProductModel::STATUS_PUBLISHED)
                    ->where('products.is_active', 1)
                    ->where('products.deleted_at', null)
                    ->orderBy('products.published_at', 'DESC'),
                8
            ),
            'sellers'    => $sellers->newRows(
                $sellers->newQuery()
                    ->where('is_active', 1)
                    ->where('deleted_at', null)
                    ->where('is_verified', 1)
                    ->orderBy('rating_average', 'DESC')
                    ->orderBy('rating_count', 'DESC'),
                6
            ),
            'categories' => $categories->withProductCounts(),
            'posts'      => (new \App\Models\BlogPostModel())->published(3),
            // Aggregate numbers for the stats band, counted in one pass.
            'stats'      => $this->marketplaceStats(),
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Headline counts for the landing page.
     *
     * @return array{sellers: int, products: int, artisans: int}
     */
    private function marketplaceStats(): array
    {
        $db = db_connect();

        $products = (int) $db->table('products')
            ->where('status', \App\Models\ProductModel::STATUS_PUBLISHED)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->countAllResults();

        $sellers = (int) $db->table('seller_profiles')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->countAllResults();

        // Summed per seller rather than counting rows, so a sanggar with six
        // artisans is not reported as six sanggar.
        $artisans = (int) $db->table('seller_profiles')
            ->selectSum('artisan_count', 'total')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->get()
            ->getRow('total');

        return [
            'sellers'  => $sellers,
            'products' => $products,
            'artisans' => $artisans,
        ];
    }
}
