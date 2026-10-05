<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Product gallery images, optionally scoped to a variant.
 *
 * The `products` table has no cover column: the cover is whichever image is
 * flagged primary, or the first one in display order. `coversFor()` resolves
 * that for a whole page of products in a single query, so listings do not run
 * one lookup per card.
 */
class ProductImageModel extends BaseModel
{
    protected $table = 'product_images';

    protected $returnType = 'array';

    protected $allowedFields = [
        'product_id',
        'variant_id',
        'file_path',
        'thumb_path',
        'alt_text',
        'caption',
        'is_primary',
        'position',
    ];

    protected array $casts = [
        'is_primary' => 'bool',
        'position'   => 'int',
    ];

    protected $validationRules = [
        'product_id' => 'required|is_natural_no_zero',
        'variant_id' => 'permit_empty|is_natural_no_zero',
        'file_path'  => 'required|max_length[255]',
        'thumb_path' => 'permit_empty|max_length[255]',
        'alt_text'   => 'permit_empty|max_length[255]',
        'caption'    => 'permit_empty|max_length[255]',
        'position'   => 'permit_empty|is_natural',
    ];

    /**
     * Gallery for a product in display order, primary image first.
     */
    public function galleryFor(int $productId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('product_id', $productId)
                ->orderBy('is_primary', 'DESC')
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
        );
    }

    /**
     * Clear the primary flag from every image of a product.
     */
    public function clearPrimary(int $productId): void
    {
        $this->newQuery()
            ->where('product_id', $productId)
            ->set('is_primary', 0)
            ->update();
    }

    /**
     * The cover image path for one product, or null when it has no photo.
     */
    public function coverFor(int $productId): ?string
    {
        $builder = $this->newQuery()
            ->select('file_path')
            ->where('product_id', $productId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC');

        $row = $this->newRow($builder->limit(1));

        return $row === null ? null : $row['file_path'];
    }

    /**
     * Cover image paths for many products at once.
     *
     * @param list<int> $productIds
     *
     * @return array<int, string> keyed by product id
     */
    public function coversFor(array $productIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));

        if ($ids === []) {
            return [];
        }

        $covers = [];

        foreach ($this->newRows($this->newQuery()
            ->select('product_id, file_path')
            ->whereIn('product_id', $ids)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('position', 'ASC')
            ->orderBy('id', 'ASC')) as $row) {
            $productId = (int) $row['product_id'];

            // The first row seen for a product is its cover, because the sort
            // puts the primary image and then the earliest position first.
            if (! isset($covers[$productId])) {
                $covers[$productId] = $row['file_path'];
            }
        }

        return $covers;
    }
}