<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class DeliveryEventModel extends Model
{
    protected $table = 'delivery_events';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = false;

    protected $allowedFields = [
        'delivery_id',
        'status',
        'note',
    ];

    protected $createdField = 'created_at';
}
