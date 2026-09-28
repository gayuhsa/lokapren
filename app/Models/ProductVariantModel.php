<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table = 'product_variants';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'name',
        'sku',
        'price',
        'stock',
        'attributes',
        'is_active',
    ];

    protected $validationRules = [
        'name'  => 'required|max_length[200]',
        'price' => 'required|is_natural_no_zero',
        'stock' => 'required|is_natural',
    ];
}
