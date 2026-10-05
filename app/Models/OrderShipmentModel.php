<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Courier tracking for an order. One row per order (UNIQUE on `order_id`).
 *
 * `tracking_number` is server-owned: it comes from the courier integration, not
 * from the request.
 */
class OrderShipmentModel extends BaseModel
{
    protected $table = 'order_shipments';

    protected $returnType = 'array';

    /**
     * Only `created_at` exists, so automatic timestamps are disabled.
     */
    protected $useTimestamps = false;

    protected $allowedFields = [
        'courier_code',
        'courier_name',
        'service_level',
    ];

    protected $validationRules = [
        'courier_code'  => 'permit_empty|max_length[20]',
        'courier_name'  => 'permit_empty|max_length[100]',
        'service_level' => 'permit_empty|max_length[30]',
    ];

    /**
     * The shipment row for an order, or null when none is recorded yet.
     *
     * @return array<string, mixed>|null
     */
    public function forOrder(int $orderId): ?array
    {
        return $this->newRow($this->newQuery()->where('order_id', $orderId));
    }

    /**
     * Store a tracking number for an order, updating in place when a shipment
     * row already exists.
     */
    public function recordTracking(int $orderId, string $trackingNumber, ?string $courierCode = null, ?string $courierName = null): void
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->forOrder($orderId);

        if ($existing === null) {
            $this->insertRow([
                'order_id'        => $orderId,
                'tracking_number' => $trackingNumber,
                'courier_code'    => $courierCode,
                'courier_name'    => $courierName,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            return;
        }

        $this->updateWhere([
            'tracking_number' => $trackingNumber,
            'courier_code'    => $courierCode,
            'courier_name'    => $courierName,
            'updated_at'      => $now,
        ], ['id' => $existing['id']]);
    }

    /**
     * Find an order by its tracking number, for public tracking lookups.
     */
    public function findOrderIdByTracking(string $trackingNumber): ?int
    {
        $row = $this->newQuery()
            ->select('order_id')
            ->where('tracking_number', $trackingNumber)
            ->get()
            ->getRowArray();

        return $row === null ? null : (int) $row['order_id'];
    }
}