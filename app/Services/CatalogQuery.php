<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Reads the published catalogue from the database.
 *
 * Everything the catalogue page needs is filtered and sorted in SQL rather
 * than in PHP, so the page never loads every product to discard most of them.
 *
 * Only `status = 'active'` products on active, verified stores are visible.
 * A draft product, or one belonging to an unverified store, must never reach a
 * visitor, and that rule lives here so no caller can forget it.
 */
final class CatalogQuery
{
    /**
     * Sort keys the catalogue accepts, mapped to SQL.
     *
     * This is a whitelist: anything not in it falls back to the default, so a
     * crafted `?sort=` can never inject into the ORDER BY clause.
     */
    private const SORTS = [
        'terbaru'    => 'p.created_at DESC, p.id DESC',
        'terlama'    => 'p.created_at ASC, p.id ASC',
        'harga_asc'  => 'min_price ASC, p.id ASC',
        'harga_desc' => 'min_price DESC, p.id DESC',
        'nama_asc'   => 'p.name ASC, p.id ASC',
        'nama_desc'  => 'p.name DESC, p.id DESC',
    ];

    /**
     * Columns every catalogue row needs, including the aggregated price and
     * stock that would otherwise require an N+1 lookup per product.
     *
     * The aggregates are compiled through the query builder rather than written
     * inline, so the table prefix and quoting rules are the connection's own.
     * Raw SQL here would bypass the prefix and fail the moment a DBPrefix is
     * configured, which is exactly what the test database does.
     */
    private function listingColumns(): string
    {
        return 'p.id, p.name, p.slug, p.description, p.is_featured,'
            . ' p.store_id, p.category_id, p.created_at,'
            . ' s.name AS store_name, s.slug AS store_slug, s.city AS store_city,'
            . ' s.subdistrict AS store_subdistrict,'
            . ' c.name AS category_name, c.slug AS category_slug,'
            . ' ' . $this->cheapestVariantSql(false) . ' AS min_price,'
            . ' ' . $this->cheapestVariantSql(true) . ' AS min_price_in_stock,'
            . ' ' . $this->totalStockSql() . ' AS total_stock,'
            . ' ' . $this->firstImageSql('pi.url') . ' AS image_url,'
            . ' ' . $this->firstImageSql('pi.alt') . ' AS image_alt';
    }

    /**
     * Cheapest active variant price for the outer product, as a subquery.
     *
     * @param bool $inStockOnly Skip variants that cannot currently be bought.
     */
    private function cheapestVariantSql(bool $inStockOnly): string
    {
        $builder = $this->db->table('product_variants v')
            ->select('MIN(v.price)')
            ->where('v.product_id = p.id', null, false)
            ->where('v.is_active', true);

        if ($inStockOnly) {
            $builder->where('v.stock >', 0);
        }

        return '(' . $builder->getCompiledSelect() . ')';
    }

    /**
     * Combined stock across a product's active variants, as a subquery.
     */
    private function totalStockSql(): string
    {
        $sql = $this->db->table('product_variants v')
            ->select('COALESCE(SUM(v.stock), 0)')
            ->where('v.product_id = p.id', null, false)
            ->where('v.is_active', true)
            ->getCompiledSelect();

        return '(' . $sql . ')';
    }

    /**
     * The value of a column on a product's first image, as a subquery.
     */
    private function firstImageSql(string $column): string
    {
        $sql = $this->db->table('product_images pi')
            ->select($column)
            ->where('pi.product_id = p.id', null, false)
            ->orderBy('pi.sort_order', 'ASC')
            ->orderBy('pi.id', 'ASC')
            ->limit(1)
            ->getCompiledSelect();

        return '(' . $sql . ')';
    }

    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * Whether a sort key is one the catalogue supports.
     */
    public static function isSortable(string $sort): bool
    {
        return $sort === '' || isset(self::SORTS[$sort]);
    }

    /**
     * The sort keys offered to visitors, in display order.
     *
     * @return array<string, string>
     */
    public static function sortOptions(): array
    {
        return [
            'terbaru'    => 'Terbaru',
            'harga_asc'  => 'Harga Terendah',
            'harga_desc' => 'Harga Tertinggi',
            'nama_asc'   => 'Nama A-Z',
        ];
    }

    /**
     * Applies the browse filters and visibility rules, without selecting
     * columns or ordering, so both the row query and the count can reuse it.
     */
    private function filtered(array $filters): BaseBuilder
    {
        // Compiled subqueries must be built *before* the outer builder exists.
        // Compiling one resets the connection's alias registry, and a later
        // compile of the outer query would then treat "p" as a table name and
        // rewrite `p.status` into `db_p.status`, which no database accepts.
        $min = $filters['min'] ?? null;
        $max = $filters['max'] ?? null;

        $minIds = $min === null ? null : $this->variantIds((int) $min, '>=');
        $maxIds = $max === null ? null : $this->variantIds((int) $max, '<=');

        $builder = $this->db->table('products p')
            ->join('stores s', 's.id = p.store_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('p.status', 'active')
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified');

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $builder->groupStart()
                ->like('p.name', $search)
                ->orLike('p.description', $search)
                ->orLike('s.name', $search)
                ->groupEnd();
        }

        $category = (string) ($filters['category'] ?? '');

        if ($category !== '') {
            $builder->where('c.slug', $category);
        }

        $store = (string) ($filters['store'] ?? '');

        if ($store !== '') {
            $builder->where('s.slug', $store);
        }

        // A product matches the range if *any* active variant sits inside it,
        // so a 165.000-820.000 product still appears when max is 500.000.
        // Comparing against the aggregated min_price instead would hide it.
        // whereIn() only accepts an array, a closure or a builder, so the
        // already-compiled subquery goes in as a raw condition. It is built
        // from bound values only, and escape=false stops CI4 from prefixing
        // the already-correct table names a second time.
        if ($minIds !== null) {
            $builder->where('p.id IN (' . $minIds . ')', null, false);
        }

        if ($maxIds !== null) {
            $builder->where('p.id IN (' . $maxIds . ')', null, false);
        }

        return $builder;
    }

    /**
     * Runs the catalogue query for one page of results.
     *
     * @return array{products: list<array<string, mixed>>, total: int, perPage: int, pages: int, page: int}
     */
    public function paginate(array $filters, int $perPage = 12, int $page = 1): array
    {
        $page = max(1, $page);

        // See filtered(): the column list carries compiled subqueries and must
        // be finished before any builder claims the alias registry.
        $listing = $this->listingColumns();

        $total = (int) $this->filtered($filters)->countAllResults();

        $rows = $this->filtered($filters)
            ->select($listing, false)
            ->orderBy(self::SORTS[$filters['sort'] ?? ''] ?? self::SORTS['terbaru'], '', false)
            ->get($perPage, ($page - 1) * $perPage)
            ->getResultArray();

        return [
            'products' => $rows,
            'total'    => $total,
            'perPage'  => $perPage,
            'pages'    => max(1, (int) ceil($total / max(1, $perPage))),
            'page'     => $page,
        ];
    }

    /**
     * Featured products for the landing strip.
     *
     * @return list<array<string, mixed>>
     */
    public function featured(int $limit = 4): array
    {
        $listing = $this->listingColumns();

        return $this->db->table('products p')
            ->select($listing, false)
            ->join('stores s', 's.id = p.store_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('p.status', 'active')
            ->where('p.is_featured', true)
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified')
            ->orderBy('p.id', 'ASC')
            ->get($limit)
            ->getResultArray();
    }

    /**
     * Active categories that actually have a visible product.
     *
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return $this->db->table('categories c')
            ->select('c.id, c.name, c.slug, COUNT(p.id) AS product_count')
            ->join('products p', 'p.category_id = c.id')
            ->join('stores s', 's.id = p.store_id')
            ->where('c.is_active', true)
            ->where('p.status', 'active')
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified')
            ->groupBy('c.id, c.name, c.slug')
            ->orderBy('product_count', 'DESC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Lowest and highest active variant price across the whole catalogue.
     *
     * Drives the price range inputs, so it ignores any active filter.
     *
     * @return array{min: int, max: int}
     */
    public function priceBounds(): array
    {
        $row = $this->db->table('product_variants v')
            ->select('MIN(v.price) AS lo, MAX(v.price) AS hi')
            ->join('products p', 'p.id = v.product_id')
            ->join('stores s', 's.id = p.store_id')
            ->where('v.is_active', true)
            ->where('p.status', 'active')
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified')
            ->get()
            ->getRowArray();

        return [
            'min' => (int) ($row['lo'] ?? 0),
            'max' => (int) ($row['hi'] ?? 0),
        ];
    }

    /**
     * Loads one product for its detail page.
     *
     * @return array<string, mixed>|null
     */
    public function detail(string $slug): ?array
    {
        $product = $this->db->table('products p')
            ->select(
                'p.*, s.name AS store_name, s.slug AS store_slug, s.tagline AS store_tagline,'
                . ' s.description AS store_description, s.address_line AS store_address,'
                . ' s.city AS store_city, s.subdistrict AS store_subdistrict,'
                . ' s.partner_level AS store_partner_level, s.store_code AS store_code,'
                . ' c.name AS category_name, c.slug AS category_slug',
                false,
            )
            ->join('stores s', 's.id = p.store_id')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->where('p.slug', $slug)
            ->where('p.status', 'active')
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified')
            ->get()
            ->getRowArray();

        if ($product === null) {
            return null;
        }

        $product['images']   = $this->images((int) $product['id']);
        $product['variants'] = $this->variants((int) $product['id']);

        // `attributes` is stored as a JSON text column; decode it once here so
        // views never have to, and never have to guard against malformed JSON.
        $product['specs'] = $this->decodeAttributes($product['attributes'] ?? null);

        // Default the picker to the cheapest variant that is actually buyable,
        // falling back to the cheapest overall when everything is sold out.
        $buyable = array_values(array_filter(
            $product['variants'],
            static fn (array $variant): bool => (int) $variant['stock'] > 0,
        ));

        $pool                    = $buyable !== [] ? $buyable : $product['variants'];
        $product['in_stock']     = $buyable !== [];
        $product['default_variant'] = $pool[0] ?? null;
        $product['price']        = (int) ($product['default_variant']['price'] ?? 0);
        $product['compare_at']   = $this->compareAtPrice($product['price']);
        $product['rating']       = $this->rating((int) $product['id']);

        return $product;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function images(int $productId): array
    {
        return $this->db->table('product_images')
            ->where('product_id', $productId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function variants(int $productId): array
    {
        return $this->db->table('product_variants')
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('price', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Review totals for the detail page.
     *
     * @return array{count: int, average: float, breakdown: array<int, int>}
     */
    public function rating(int $productId): array
    {
        $row = $this->db->table('product_reviews')
            ->select('COUNT(*) AS total, AVG(rating) AS average')
            ->where('product_id', $productId)
            ->get()
            ->getRowArray();

        $breakdown = array_fill(1, 5, 0);

        foreach ($this->db->table('product_reviews')
            ->select('rating, COUNT(*) AS c')
            ->where('product_id', $productId)
            ->groupBy('rating')
            ->get()
            ->getResultArray() as $line) {
            $star = (int) $line['rating'];

            if ($star >= 1 && $star <= 5) {
                $breakdown[$star] = (int) $line['c'];
            }
        }

        return [
            'count'     => (int) ($row['total'] ?? 0),
            'average'   => round((float) ($row['average'] ?? 0), 1),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Most helpful recent reviews for the detail page.
     *
     * @return list<array<string, mixed>>
     */
    public function reviews(int $productId, int $limit = 4): array
    {
        $helpful = '(' . $this->db->table('review_helpful_votes v')
            ->select('COUNT(*)')
            ->where('v.review_id = r.id', null, false)
            ->getCompiledSelect() . ')';

        return $this->db->table('product_reviews r')
            ->select(
                'r.id, r.rating, r.comment, r.created_at, r.product_variant_id,'
                . ' u.username AS reviewer_name,'
                . ' ' . $helpful . ' AS helpful_count',
                false,
            )
            ->join('users u', 'u.id = r.customer_id')
            ->where('r.product_id', $productId)
            ->orderBy('helpful_count', 'DESC')
            ->orderBy('r.created_at', 'DESC')
            ->get($limit)
            ->getResultArray();
    }

    /**
     * Decodes the JSON `attributes` column into a flat label => value map.
     *
     * @return array<string, string>
     */
    private function decodeAttributes(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $specs = [];

        foreach ($decoded as $label => $value) {
            if (is_scalar($value)) {
                $specs[(string) $label] = (string) $value;
            }
        }

        return $specs;
    }

    /**
     * The struck-through "was" price shown beside the artisan price.
     * The mockup shows a direct-from-artisan saving, so the comparison is a
     * flat uplift over the artisan price rather than a stored value.
     */
    private function compareAtPrice(int $price): int
    {
        return $price > 0 ? (int) round($price * 1.1667, -3) : 0;
    }

    /**
     * Compiled subquery of product ids having an active variant on one side of
     * a price bound.
     *
     * @return string
     */
    private function variantIds(int $bound, string $operator): string
    {
        return $this->db->table('product_variants')
            ->select('product_id')
            ->where('is_active', true)
            ->where('price ' . $operator, $bound)
            ->getCompiledSelect();
    }
}