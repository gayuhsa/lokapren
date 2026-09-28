<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class StoreVisitModel extends Model
{
    protected $table = 'store_visits';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'store_id',
        'visitor_user_id',
        'source',
        'visited_at',
    ];
}
