<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ReviewPhotoModel extends Model
{
    protected $table = 'review_photos';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'review_id',
        'url',
        'sort_order',
    ];

    protected $validationRules = [
        'url'        => 'required|max_length[500]',
        'sort_order' => 'is_natural',
    ];
}
