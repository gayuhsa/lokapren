<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table = 'product_images';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'url',
        'alt',
        'sort_order',
    ];

    protected $validationRules = [
        'url'        => 'required|max_length[500]',
        'sort_order' => 'is_natural',
    ];
}
