<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Catalog category tree ("Ukiran Kayu Jati", "Batik Tulis", …).
 *
 * Reference data, so every column is allowed; `parent_id` is validated by the
 * self-referencing foreign key.
 */
class ProductCategoryModel extends BaseModel
{
    protected $table = 'product_categories';

    protected $returnType = 'array';

    protected $allowedFields = [
        'parent_id',
        'name',
        'slug',
        'craft_type',
        'position',
        'is_active',
    ];

    protected array $casts = [
        'position'  => 'int',
        'is_active' => 'bool',
    ];

    protected $validationRules = [
        'name'       => 'required|max_length[120]',
        'slug'       => 'required|max_length[120]',
        'craft_type' => 'permit_empty|max_length[30]',
        'parent_id'  => 'permit_empty|is_natural_no_zero',
        'position'   => 'permit_empty|is_natural',
    ];

    /**
     * Active categories with their live product counts, for the catalog tabs.
     *
     * @return list<array<string, mixed>>
     */
    public function withProductCounts(): array
    {
        $builder = $this->newQuery()
            ->select('product_categories.*')
            ->select('COUNT(products.id) AS product_count', false)
            ->join('products', 'products.category_id = product_categories.id', 'left')
            ->where('product_categories.is_active', 1)
            ->where('products.deleted_at', null)
            ->groupBy('product_categories.id')
            ->orderBy('product_categories.position', 'ASC');

        return $this->newRows($builder);
    }

    /**
     * A category and all of its descendants, as a flat list.
     *
     * Breadth-first, with a visited set so a corrupted `parent_id` cycle cannot
     * loop forever.
     *
     * @return list<array<string, mixed>>
     */
    public function withDescendants(int $categoryId): array
    {
        $collected = [];
        $visited   = [$categoryId => true];
        $frontier  = [$categoryId];

        while ($frontier !== []) {
            $children = $this->newRows(
                $this->newQuery()->whereIn('parent_id', $frontier)
            );

            $next = [];

            foreach ($children as $child) {
                $id = (int) $child['id'];

                if (isset($visited[$id])) {
                    continue;
                }

                $visited[$id] = true;
                $collected[]   = $child;
                $next[]        = $id;
            }

            $frontier = $next;
        }

        return $collected;
    }
}