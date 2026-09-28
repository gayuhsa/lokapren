<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'categories';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'name',
        'slug',
        'is_active',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[120]',
        'slug' => 'required|max_length[120]|is_unique[categories.slug,id,{id}]',
    ];
}
