<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'conversation_id',
        'sender_id',
        'sender_role',
        'message_type',
        'body',
        'media_url',
        'product_id',
        'order_id',
        'is_read',
        'read_at',
    ];

    protected $validationRules = [
        'sender_role'  => 'in_list[customer,seller]',
        'message_type' => 'in_list[text,image,video,product,order]',
    ];
}
