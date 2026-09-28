<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OrderDeliveryModel extends Model
{
    protected $table = 'order_deliveries';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'service_type',
        'courier_label',
        'cost',
        'eta_minutes',
        'tracking_code',
        'status',
        'progress',
        'requested_at',
        'picked_up_at',
        'delivered_at',
    ];

    public const SERVICE_PESAN_ANTAR_TERIMA  = 'pesan_antar_terima';
    public const SERVICE_EKSPEDISI_REGULER   = 'ekspedisi_reguler';
    public const SERVICE_CARGO              = 'cargo';

    public const STATUS_PENDING             = 'pending';
    public const STATUS_PICKED_UP           = 'picked_up';
    public const STATUS_IN_TRANSIT          = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY    = 'out_for_delivery';
    public const STATUS_DELIVERED           = 'delivered';
    public const STATUS_FAILED              = 'failed';

    public function allowedServices(): array
    {
        return [
            self::SERVICE_PESAN_ANTAR_TERIMA,
            self::SERVICE_EKSPEDISI_REGULER,
            self::SERVICE_CARGO,
        ];
    }

    public function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
        ];
    }

    public function allowedTransitions(): array
    {
        return [
            self::STATUS_PENDING          => [self::STATUS_PICKED_UP, self::STATUS_FAILED],
            self::STATUS_PICKED_UP        => [self::STATUS_IN_TRANSIT, self::STATUS_FAILED],
            self::STATUS_IN_TRANSIT       => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_FAILED],
            self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_FAILED],
            self::STATUS_DELIVERED        => [],
            self::STATUS_FAILED           => [],
        ];
    }

    public function canTransitionTo(string $from, string $to): bool
    {
        $allowed = $this->allowedTransitions();

        return in_array($to, $allowed[$from] ?? [], true);
    }

    public function progressFor(string $status): int
    {
        return match ($status) {
            self::STATUS_PENDING          => 0,
            self::STATUS_PICKED_UP        => 25,
            self::STATUS_IN_TRANSIT       => 50,
            self::STATUS_OUT_FOR_DELIVERY => 75,
            self::STATUS_DELIVERED        => 100,
            default                       => 0,
        };
    }
}
