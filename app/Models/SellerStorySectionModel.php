<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * "Filosofi & Sejarah Kriya" storefront story blocks. `seller_id` is server-owned.
 */
class SellerStorySectionModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_story_sections';

    protected $returnType = 'array';

    protected $allowedFields = [
        'title',
        'subtitle',
        'body',
        'image_path',
        'position',
        'is_active',
    ];

    protected array $casts = [
        'position'  => 'int',
        'is_active' => 'bool',
    ];

    protected $validationRules = [
        'title'      => 'required|max_length[150]',
        'subtitle'   => 'permit_empty|max_length[255]',
        'image_path' => 'permit_empty|max_length[255]',
        'position'   => 'permit_empty|is_natural',
    ];

    /**
     * Visible story blocks for a storefront, in display order.
     */
    public function activeFor(int $sellerId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('seller_id', $sellerId)
                ->where('is_active', 1)
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
        );
    }
}