<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * One row per seller per weekday. `seller_id` is server-owned.
 */
class SellerBusinessHourModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_business_hours';

    protected $returnType = 'array';

    protected $allowedFields = [
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_closed',
    ];

    protected array $casts = [
        'day_of_week' => 'int',
        'is_closed'   => 'bool',
    ];

    protected $validationRules = [
        // ISO-8601: 1 = Monday … 7 = Sunday.
        'day_of_week' => 'required|is_natural_no_zero|less_than_equal_to[7]',
        'opens_at'    => 'permit_empty',
        'closes_at'   => 'permit_empty',
    ];

    /**
     * Replace a seller's whole week in one call.
     *
     * @param array<int, array<string, mixed>> $hours
     */
    public function replaceWeek(int $sellerId, array $hours): bool
    {
        $this->db->transStart();

        $this->newQuery()->where('seller_id', $sellerId)->delete();

        foreach ($hours as $hour) {
            // `seller_id` is applied last so a caller-supplied value in `$hour`
            // can never redirect the row to another seller.
            $row = array_merge($hour, ['seller_id' => $sellerId]);
            $this->db->table($this->table)->insert($row);
        }

        return $this->db->transComplete();
    }
}