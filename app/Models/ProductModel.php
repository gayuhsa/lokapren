<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'name',
        'slug',
        'description',
        'attributes',
        'status',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'slug' => 'required|max_length[200]|is_unique[products.slug,id,{id}]',
        'status' => 'in_list[draft,active,archived]',
    ];
}
