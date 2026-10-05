<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Seller blog taxonomy. Reference data.
 */
class BlogCategoryModel extends BaseModel
{
    protected $table = 'blog_categories';

    protected $returnType = 'array';

    protected $allowedFields = [
        'parent_id',
        'name',
        'slug',
    ];

    protected $validationRules = [
        'name'      => 'required|max_length[120]',
        'slug'      => 'required|max_length[120]',
        'parent_id' => 'permit_empty|is_natural_no_zero',
    ];

    public function getChildren(?int $parentId): array
    {
        return $this->newRows(
            $this->newQuery()->where('parent_id', $parentId)
        );
    }
}