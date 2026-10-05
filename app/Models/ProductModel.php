<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

class ProductModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'products';

    protected $returnType = 'array';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    /**
     * `seller_id`, `price`, `stock`, the `*_count` aggregates, `view_count`,
     * `rating_average` and `status` are server-owned and deliberately omitted.
     *
     * Price and totals must never come from the request (AGENTS.md marketplace
     * rules); the seller may *request* a price but the value is confirmed
     * server-side before it is written here.
     */
    protected $allowedFields = [
        'category_id',
        'product_code',
        'slug',
        'name',
        'subtitle',
        'summary',
        'description',
        'story',
        'material',
        'finishing',
        'weight_gram',
        'made_to_order',
        'production_days',
        'low_stock_threshold',
        'is_active',
        'is_featured',
    ];

    protected array $casts = [
        'price'              => 'int',
        'stock'              => 'int',
        // DECIMAL: left as the driver returns it. CI4's float cast rejects the
        // integer 0 that SQLite returns for an otherwise numeric column, and
        // the average is formatted for display at the presentation layer.
        'rating_count'       => 'int',
        'sold_count'         => 'int',
        'orders_count'       => 'int',
        'view_count'         => 'int',
        'low_stock_threshold' => 'int',
        'weight_gram'        => '?int',
        'production_days'    => '?int',
        'made_to_order'      => 'bool',
        'is_active'          => 'bool',
        'is_featured'        => 'bool',
    ];

    protected $validationRules = [
        'product_code'  => 'required|max_length[32]',
        'slug'          => 'required|max_length[150]',
        'name'          => 'required|max_length[180]',
        'subtitle'      => 'permit_empty|max_length[255]',
        'summary'       => 'permit_empty|max_length[500]',
        'material'      => 'permit_empty|max_length[150]',
        'finishing'     => 'permit_empty|max_length[150]',
        'weight_gram'   => 'permit_empty|is_natural',
        'production_days' => 'permit_empty|is_natural',
        'category_id'   => 'permit_empty|is_natural_no_zero',
        'low_stock_threshold' => 'permit_empty|is_natural',
    ];

    /**
     * Restrict the next query to products that are visible in the catalog.
     */
    public function published()
    {
        return $this->builder()
            ->where('status', self::STATUS_PUBLISHED)
            ->where('is_active', 1)
            ->where('deleted_at', null);
    }

    /**
     * Products at or below their low-stock threshold, for dashboard alerts.
     *
     * Made-to-order products are left out: they are produced after a purchase,
     * so an empty stock column says nothing about supply.
     */
    public function lowStock()
    {
        return $this->builder()
            ->where('stock', '<=', 'low_stock_threshold', false)
            ->where('status', self::STATUS_PUBLISHED)
            ->where('made_to_order', 0)
            ->where('deleted_at', null);
    }

    /**
     * Catalog ordering helper. `sort` is whitelisted here rather than passed
     * straight to the query builder, so request input cannot choose a column.
     */
    public function sorted(string $sort = 'sold_count'): self
    {
        match ($sort) {
            'price_asc'  => $this->builder()->orderBy('price', 'ASC'),
            'price_desc' => $this->builder()->orderBy('price', 'DESC'),
            'rating'     => $this->builder()->orderBy('rating_average', 'DESC'),
            'newest'     => $this->builder()->orderBy('published_at', 'DESC'),
            'name'       => $this->builder()->orderBy('name', 'ASC'),
            default      => $this->builder()->orderBy('sold_count', 'DESC'),
        };

        return $this;
    }
}