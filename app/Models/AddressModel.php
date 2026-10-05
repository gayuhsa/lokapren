<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Address book, including hotel/villa/homestay delivery.
 *
 * `recipient_phone` is delivery data for the courier, not a login identifier —
 * the auth path is username/email only.
 *
 * `user_id` is server-owned, so an address can never be saved against another
 * account by passing a different id in the payload.
 */
class AddressModel extends BaseModel
{
    protected $table = 'addresses';

    protected $returnType = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'label',
        'recipient_name',
        'recipient_phone',
        'address_line',
        'village',
        'district_id',
        'regency_id',
        'province_id',
        'postal_code',
        'landmark',
        'delivery_notes',
        'is_hotel',
        'is_default',
    ];

    protected array $casts = [
        'is_hotel'   => 'bool',
        'is_default' => 'bool',
    ];

    protected $validationRules = [
        'recipient_name'  => 'required|max_length[150]',
        'recipient_phone' => 'permit_empty|max_length[25]',
        'address_line'    => 'required|max_length[255]',
        'village'         => 'permit_empty|max_length[100]',
        'postal_code'     => 'permit_empty|max_length[10]',
        'landmark'        => 'permit_empty|max_length[150]',
        'delivery_notes'  => 'permit_empty|max_length[255]',
        'district_id'     => 'permit_empty|is_natural_no_zero',
        'regency_id'      => 'permit_empty|is_natural_no_zero',
        'province_id'     => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * A user's live addresses, default first.
     */
    public function forUser(int $userId)
    {
        return $this->newRows(
            $this->newQuery()
                ->where('user_id', $userId)
                ->orderBy('is_default', 'DESC')
                ->orderBy('id', 'ASC')
        );
    }

    /**
     * Load an address only when it belongs to `$userId`.
     *
     * Returns null for someone else's address so a controller can 404 without
     * revealing that the id exists.
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedBy(int $id, int $userId): ?array
    {
        return $this->newRow(
            $this->newQuery()
                ->where('id', $id)
                ->where('user_id', $userId)
        );
    }

    /**
     * Mark one address as the default, clearing any previous default.
     */
    public function makeDefault(int $id, int $userId): bool
    {
        $owned = $this->findOwnedBy($id, $userId);

        if ($owned === null) {
            return false;
        }

        $this->db->transStart();

        // Only live addresses participate: a deleted address cannot be the
        // default one.
        $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_default', 1)
            ->where('id !=', $id)
            ->set('is_default', 0)
            ->update();

        $this->updateWhere(['is_default' => 1], ['id' => $id]);

        return $this->db->transComplete();
    }
}