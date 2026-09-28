<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table = 'order_items';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'order_id',
        'store_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_name',
        'unit_price',
        'qty',
        'line_total',
    ];

    protected $validationRules = [
        'qty'        => 'required|is_natural|greater_than[0]',
        'unit_price' => 'required|is_natural_no_zero',
    ];
}
