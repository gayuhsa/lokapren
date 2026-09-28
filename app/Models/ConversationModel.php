<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ConversationModel extends Model
{
    protected $table = 'conversations';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'linked_order_id',
        'last_message_at',
        'last_message_preview',
        'customer_unread_count',
        'store_unread_count',
    ];
}
