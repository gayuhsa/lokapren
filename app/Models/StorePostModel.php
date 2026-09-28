<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class StorePostModel extends Model
{
    protected $table = 'store_posts';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'title',
        'slug',
        'excerpt',
        'body',
        'cover_image_url',
        'media_url',
        'post_type',
        'status',
        'published_at',
    ];

    // Per-store slug uniqueness is enforced by the composite unique key
    // ['store_id', 'slug'] in the store_posts table.
    protected $validationRules = [
        'title'     => 'required|max_length[200]',
        'slug'      => 'required|max_length[200]',
        'excerpt'   => 'permit_empty|max_length[500]',
        'post_type' => 'in_list[article,story,documentary]',
        'status'    => 'in_list[draft,published]',
    ];
}
