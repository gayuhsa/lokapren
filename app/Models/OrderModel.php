<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'recipient_name',
        'recipient_phone',
        'address_line',
        'subdistrict',
        'city',
        'postal_code',
        'courier_note',
    ];

    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY      = 'ready';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';

    public function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING,
            self::STATUS_READY,
            self::STATUS_SHIPPED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    public function allowedTransitions(): array
    {
        return [
            self::STATUS_PENDING    => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED  => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_READY, self::STATUS_CANCELLED],
            self::STATUS_READY      => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED    => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED  => [],
            self::STATUS_CANCELLED  => [],
        ];
    }

    public function canTransitionTo(string $from, string $to): bool
    {
        $allowed = $this->allowedTransitions();

        return in_array($to, $allowed[$from] ?? [], true);
    }
}
