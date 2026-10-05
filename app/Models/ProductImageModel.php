<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Product gallery images, optionally scoped to a variant.
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
}