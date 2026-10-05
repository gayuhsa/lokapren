<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Seller-defined suggested chat prompts. `seller_id` and `use_count` are
 * server-owned.
 */
class SellerQuickReplyModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_quick_replies';

    protected $returnType = 'array';

    protected $allowedFields = [
        'title',
        'body',
        'position',
        'is_active',
    ];

    protected array $casts = [
        'position'  => 'int',
        'use_count' => 'int',
        'is_active' => 'bool',
    ];

    protected $validationRules = [
        'title'    => 'required|max_length[120]',
        'position' => 'permit_empty|is_natural',
    ];

    /**
     * Active prompts for the seller's chat composer.
     */
    public function activeFor(int $sellerId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('seller_id', $sellerId)
                ->where('is_active', 1)
                ->orderBy('position', 'ASC')
        );
    }

    /**
     * Increment the usage counter for a prompt the seller owns.
     */
    public function recordUse(int $id, int $sellerId): bool
    {
        if ($this->findOwnedBy($id, $sellerId) === null) {
            return false;
        }

        $this->newQuery()
            ->where('id', $id)
            ->set('use_count', 'use_count + 1', false)
            ->update();

        return true;
    }
}