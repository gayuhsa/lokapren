<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConversationModel;
use App\Models\OrderModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ReviewModel;
use App\Models\SellerBusinessHourModel;
use App\Models\SellerDailyStatModel;
use App\Models\SellerFacilityModel;
use App\Models\SellerMediaModel;
use App\Models\SellerProfileModel;
use App\Models\SellerStorySectionModel;

/**
 * Read-only queries for the public catalog, product detail and seller
 * storefront.
 *
 * Everything here composes query-builder scopes rather than CI5 relations, and
 * every filter comes from a whitelist in this class so request input can never
 * choose a column name (AGENTS.md: use the query builder, bind parameters).
 */
class CatalogService
{
    /**
     * Sort keys the catalog accepts.
     *
     * @var list<string>
     */
    public const SORTS = ['sold_count', 'newest', 'price_asc', 'price_desc', 'rating', 'name'];

    private ProductModel $products;
    private ProductVariantModel $variants;
    private ProductImageModel $images;
    private ProductCategoryModel $categories;
    private ReviewModel $reviews;
    private SellerProfileModel $sellers;
    private SellerDailyStatModel $stats;
    private SellerFacilityModel $facilities;
    private SellerStorySectionModel $stories;
    private SellerMediaModel $sellerMedia;
    private SellerBusinessHourModel $hours;

    public function __construct()
    {
        $this->products   = new ProductModel();
        $this->variants   = new ProductVariantModel();
        $this->images     = new ProductImageModel();
        $this->categories = new ProductCategoryModel();
        $this->reviews    = new ReviewModel();
        $this->sellers    = new SellerProfileModel();
        $this->stats      = new SellerDailyStatModel();
        $this->facilities = new SellerFacilityModel();
        $this->stories    = new SellerStorySectionModel();
        $this->sellerMedia = new SellerMediaModel();
        $this->hours      = new SellerBusinessHourModel();
    }

    /**
     * A filtered, sorted page of the catalog.
     *
     * @param array<string, mixed> $filters `q`, `category`, `seller`, `min_price`,
     *                                       `max_price`, `sort`, `page`
     *
     * @return array<string, mixed>
     */
    public function search(array $filters, int $perPage = 12): array
    {
        $builder = $this->products->newQuery()
            ->select('products.*, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.logo_path AS shop_logo, seller_profiles.rating_average AS shop_rating, seller_profiles.rating_count AS shop_rating_count')
            ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
            ->where('products.status', ProductModel::STATUS_PUBLISHED)
            ->where('products.is_active', 1)
            ->where('products.deleted_at', null);

        $query = trim((string) ($filters['q'] ?? ''));

        if ($query !== '') {
            $builder->groupStart()
                ->like('products.name', $query)
                ->orLike('products.subtitle', $query)
                ->orLike('products.summary', $query)
                ->orLike('products.material', $query)
                ->groupEnd();
        }

        $categorySlug = trim((string) ($filters['category'] ?? ''));

        if ($categorySlug !== '') {
            $builder->join('product_categories', 'product_categories.id = products.category_id', 'left')
                ->where('product_categories.slug', $categorySlug);
        }

        $sellerSlug = trim((string) ($filters['seller'] ?? ''));

        if ($sellerSlug !== '') {
            $builder->where('seller_profiles.slug', $sellerSlug);
        }

        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;

        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $builder->where('products.price >=', (int) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $builder->where('products.price <=', (int) $maxPrice);
        }

        $sort = (string) ($filters['sort'] ?? 'sold_count');

        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'sold_count';
        }

        match ($sort) {
            'price_asc'  => $builder->orderBy('products.price', 'ASC'),
            'price_desc' => $builder->orderBy('products.price', 'DESC'),
            'rating'     => $builder->orderBy('products.rating_average', 'DESC'),
            'newest'     => $builder->orderBy('products.published_at', 'DESC'),
            'name'       => $builder->orderBy('products.name', 'ASC'),
            default      => $builder->orderBy('products.sold_count', 'DESC'),
        };

        $page   = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // The rows are fetched before the count, so the count query can never
        // still be pending against the same model builder.
        $rows = $builder
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        // The total is counted by its own builder so the limit/offset applied
        // here cannot distort it.
        $total = $this->countFor($filters);

        return [
            'products'  => $this->withCovers(array_map([$this, 'hydrateProduct'], $rows)),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'pages'     => (int) max(1, ceil($total / $perPage)),
            'sort'      => $sort,
            'filters'   => $filters,
            'has_prev'  => $page > 1,
            'has_next'  => $page < (int) max(1, ceil($total / $perPage)),
        ];
    }

    /**
     * Matching row count for the same filters, ignoring pagination.
     *
     * This runs its own `ProductModel`. Reusing `$this->products` would call
     * `newQuery()` a second time, which resets that model's *shared* builder —
     * including the one `search()` is holding — so the row query would silently
     * lose its SELECT list and its filters.
     */
    private function countFor(array $filters): int
    {
        $model = new ProductModel();

        $builder = $model->newQuery()
            ->select('products.id')
            ->where('products.status', ProductModel::STATUS_PUBLISHED)
            ->where('products.is_active', 1)
            ->where('products.deleted_at', null);

        $query = trim((string) ($filters['q'] ?? ''));

        if ($query !== '') {
            $builder->groupStart()
                ->like('products.name', $query)
                ->orLike('products.subtitle', $query)
                ->orLike('products.summary', $query)
                ->orLike('products.material', $query)
                ->groupEnd();
        }

        $categorySlug = trim((string) ($filters['category'] ?? ''));

        if ($categorySlug !== '') {
            $builder->join('product_categories', 'product_categories.id = products.category_id', 'left')
                ->where('product_categories.slug', $categorySlug);
        }

        $sellerSlug = trim((string) ($filters['seller'] ?? ''));

        if ($sellerSlug !== '') {
            $builder->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
                ->where('seller_profiles.slug', $sellerSlug);
        }

        $minPrice = $filters['min_price'] ?? null;
        $maxPrice = $filters['max_price'] ?? null;

        if ($minPrice !== null && $minPrice !== '' && is_numeric($minPrice)) {
            $builder->where('products.price >=', (int) $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '' && is_numeric($maxPrice)) {
            $builder->where('products.price <=', (int) $maxPrice);
        }

        return $builder->countAllResults();
    }

    /**
     * A published product by slug, with everything the detail page renders.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedBySlug(string $slug): ?array
    {
        $row = $this->products->newRow(
            $this->products->newQuery()
                ->select('products.*, seller_profiles.display_name AS shop_name, seller_profiles.slug AS shop_slug, seller_profiles.logo_path AS shop_logo, seller_profiles.owner_name AS shop_owner, seller_profiles.tagline AS shop_tagline, seller_profiles.rating_average AS shop_rating, seller_profiles.rating_count AS shop_rating_count, seller_profiles.sold_count AS shop_sold, seller_profiles.village AS shop_village, seller_profiles.regency AS shop_regency')
                ->join('seller_profiles', 'seller_profiles.user_id = products.seller_id', 'left')
                ->where('products.slug', $slug)
                ->where('products.status', ProductModel::STATUS_PUBLISHED)
                ->where('products.is_active', 1)
                ->where('products.deleted_at', null)
        );

        if ($row === null) {
            return null;
        }

        $row = $this->hydrateProduct($row);

        $row['images']   = $this->images->galleryFor((int) $row['id']);
        $row['variants'] = $this->variants->activeFor((int) $row['id']);
        $row['reviews']  = $this->reviews->publishedFor((int) $row['id'], 10);
        $row['breakdown'] = $this->reviews->ratingBreakdown((int) $row['id']);
        $row['review_total'] = (int) $row['rating_count'];
        $row['cover_path'] = $row['images'][0]['file_path'] ?? null;

        return $row;
    }

    /**
     * A seller's public storefront.
     *
     * @return array<string, mixed>|null
     */
    public function storefrontBySlug(string $slug): ?array
    {
        $seller = $this->sellers->newRow(
            $this->sellers->newQuery()->where('slug', $slug)
        );

        if ($seller === null || ! $seller['is_active']) {
            return null;
        }

        $sellerId = (int) $seller['user_id'];

        return [
            'profile'   => $seller,
            'products'  => $this->productsBySeller($sellerId, 24),
            'facilities' => $this->facilities->ownedBy($sellerId)->findAll(),
            'stories'   => $this->stories->ownedBy($sellerId)->findAll(),
            'hours'     => $this->hours->ownedBy($sellerId)->findAll(),
            'media'     => $this->sellerMedia->publishedFor($sellerId),
            'reviews'   => $this->reviews->latestForSeller($sellerId, 6),
            'is_open'   => $this->isOpenNow($sellerId),
        ];
    }

    /**
     * A seller's published products.
     *
     * @return list<array<string, mixed>>
     */
    public function productsBySeller(int $sellerId, int $limit = 24, int $offset = 0): array
    {
        $rows = $this->products->newRows(
            $this->products->newQuery()
                ->where('seller_id', $sellerId)
                ->where('status', ProductModel::STATUS_PUBLISHED)
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('sold_count', 'DESC'),
            $limit,
            $offset
        );

        return $this->withCovers(array_map([$this, 'hydrateProduct'], $rows));
    }

    /**
     * Attach each product's cover image path in one query.
     *
     * The `products` table has no cover column, so a listing resolves it from
     * `product_images` — once for the whole page rather than once per card.
     *
     * @param list<array<string, mixed>> $products
     *
     * @return list<array<string, mixed>>
     */
    private function withCovers(array $products): array
    {
        $ids = [];

        foreach ($products as $product) {
            $ids[] = (int) $product['id'];
        }

        $covers = $this->images->coversFor($ids);

        foreach ($products as $index => $product) {
            $products[$index]['cover_path'] = $covers[(int) $product['id']] ?? null;
        }

        return $products;
    }

    /**
     * Whether the sanggar is open at the current WIB time.
     *
     * A sanggar with no business hours saved is treated as open, so a new seller
     * is not shown as permanently closed on their own storefront.
     */
    public function isOpenNow(int $sellerId): bool
    {
        $hours = $this->hours->ownedBy($sellerId)->findAll();

        if ($hours === []) {
            return true;
        }

        $dayOfWeek = (int) date('N');
        $current   = date('H:i:s');

        foreach ($hours as $hour) {
            if ((int) $hour['day_of_week'] !== $dayOfWeek) {
                continue;
            }

            if ((bool) $hour['is_closed']) {
                return false;
            }

            if ($hour['opens_at'] !== null && $hour['closes_at'] !== null) {
                return $current >= $hour['opens_at'] && $current <= $hour['closes_at'];
            }

            return true;
        }

        // No row for today means the sanggar does not open that day.
        return false;
    }

    /**
     * Seller pins for the store locator map.
     *
     * Only sanggar with coordinates can appear on a map, so the query requires
     * both latitude and longitude.
     *
     * @return list<array<string, mixed>>
     */
    public function locatorPins(string $query = ''): array
    {
        $builder = $this->sellers->newQuery()
            ->select('id, user_id, slug, display_name, owner_name, tagline, craft_focus, logo_path, cover_path, village, district, regency, latitude, longitude, rating_average, rating_count, artisan_count, is_verified')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->where('latitude IS NOT NULL', null, false)
            ->where('longitude IS NOT NULL', null, false);

        $query = trim($query);

        if ($query !== '') {
            $builder->groupStart()
                ->like('display_name', $query)
                ->orLike('owner_name', $query)
                ->orLike('craft_focus', $query)
                ->orLike('village', $query)
                ->orLike('regency', $query)
                ->groupEnd();
        }

        return $this->sellers->newRows($builder->orderBy('rating_average', 'DESC'));
    }

    /**
     * Dashboard tiles for a seller.
     *
     * @return array<string, mixed>
     */
    public function sellerDashboard(int $sellerId): array
    {
        $orders     = new OrderModel();
        $conversations = new ConversationModel();

        $since = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $until = date('Y-m-d 23:59:59');

        $seller = $this->sellers->newRow(
            $this->sellers->newQuery()->where('user_id', $sellerId)
        );

        $stats = $this->stats->totalsBetween($sellerId, date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

        return [
            'profile'        => $seller,
            'orders'         => [
                'all'         => $orders->forSellerDashboard($sellerId, 'all', 200),
                'to_ship'     => $orders->forSellerDashboard($sellerId, 'to_ship', 200),
                'in_transit'  => $orders->forSellerDashboard($sellerId, 'in_transit', 200),
                'done'        => $orders->forSellerDashboard($sellerId, 'done', 200),
                'cancelled'   => $orders->forSellerDashboard($sellerId, 'cancelled', 200),
            ],
            'revenue_30d'    => $orders->earningsBetween($sellerId, $since, $until),
            'visit_count'    => $stats['visit_count'],
            'product_view_count' => $stats['product_view_count'],
            'chat_started_count' => $stats['chat_started_count'],
            'geography'      => $orders->buyerGeography($sellerId, $since, $until),
            'unread_chat'    => $conversations->totalUnreadFor($sellerId),
            'low_stock'      => $this->lowStockFor($sellerId),
        ];
    }

    /**
     * The seller's products at or below their low-stock threshold.
     *
     * @return list<array<string, mixed>>
     */
    private function lowStockFor(int $sellerId): array
    {
        return $this->products->newRows(
            $this->products->newQuery()
                ->where('seller_id', $sellerId)
                ->where('status', ProductModel::STATUS_PUBLISHED)
                ->where('deleted_at', null)
                ->where('stock <=', 'low_stock_threshold', false)
                // A made-to-order piece is made after it is bought, so its
                // stock column is not a count of anything and an empty row is
                // not a warning.
                ->where('made_to_order', 0)
                ->orderBy('stock', 'ASC'),
            10
        );
    }

    /**
     * Apply the model casts and normalise the numeric columns a card renders.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function hydrateProduct(array $row): array
    {
        return [
            'id'             => (int) ($row['id'] ?? 0),
            'seller_id'      => (int) ($row['seller_id'] ?? 0),
            'category_id'    => isset($row['category_id']) ? (int) $row['category_id'] : null,
            'name'           => $row['name'] ?? '',
            'slug'           => $row['slug'] ?? '',
            'subtitle'       => $row['subtitle'] ?? null,
            'summary'        => $row['summary'] ?? null,
            // Long-form copy is only needed on the detail page, but carrying it
            // here avoids a second lookup there.
            'description'    => $row['description'] ?? null,
            'story'          => $row['story'] ?? null,
            'material'       => $row['material'] ?? null,
            'finishing'      => $row['finishing'] ?? null,
            'weight_gram'    => isset($row['weight_gram']) ? (int) $row['weight_gram'] : null,
            'production_days' => isset($row['production_days']) ? (int) $row['production_days'] : null,
            'low_stock_threshold' => isset($row['low_stock_threshold']) ? (int) $row['low_stock_threshold'] : 0,
            'price'          => (int) ($row['price'] ?? 0),
            'stock'          => (int) ($row['stock'] ?? 0),
            'sold_count'     => (int) ($row['sold_count'] ?? 0),
            'view_count'     => (int) ($row['view_count'] ?? 0),
            'rating_average' => $row['rating_average'] ?? 0,
            'rating_count'   => (int) ($row['rating_count'] ?? 0),
            'made_to_order'  => (bool) ($row['made_to_order'] ?? false),
            'cover_path'     => $row['cover_path'] ?? null,
            'published_at'   => $row['published_at'] ?? null,
            'shop_name'      => $row['shop_name'] ?? null,
            'shop_slug'      => $row['shop_slug'] ?? null,
            'shop_logo'      => $row['shop_logo'] ?? null,
            'shop_owner'     => $row['shop_owner'] ?? null,
            'shop_tagline'   => $row['shop_tagline'] ?? null,
            'shop_sold'      => isset($row['shop_sold']) ? (int) $row['shop_sold'] : null,
            'shop_village'   => $row['shop_village'] ?? null,
            'shop_regency'   => $row['shop_regency'] ?? null,
            'shop_rating'    => $row['shop_rating'] ?? null,
            'shop_rating_count' => (int) ($row['shop_rating_count'] ?? 0),
        ];
    }
}
