<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ProductReviewModel extends Model
{
    protected $table = 'product_reviews';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'product_id',
        'product_variant_id',
        'order_id',
        'customer_id',
        'rating',
        'comment',
    ];

    protected $validationRules = [
        'rating' => 'required|is_natural|less_than_equal_to[5]|greater_than[0]',
    ];
}
