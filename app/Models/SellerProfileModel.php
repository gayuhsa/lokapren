<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * The storefront. One row per seller, enforced by `user_id` being UNIQUE.
 *
 * There is no `stores` table by design (AGENTS.md invariants 5 and 6).
 *
 * `user_id`, the trust counters (`rating_average`, `rating_count`, `sold_count`)
 * and the verification columns are server-owned: they are intentionally absent
 * from `$allowedFields` so no request payload can write them.
 */
class SellerProfileModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_profiles';

    protected $returnType = 'array';

    /**
     * A seller owns their own storefront, and this table names the column
     * `user_id` rather than `seller_id`.
     */
    protected function sellerIdField(): string
    {
        return 'user_id';
    }

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'slug',
        'partner_code',
        'display_name',
        'owner_name',
        'tagline',
        'description',
        'craft_focus',
        'logo_path',
        'cover_path',
        'banner_path',
        'address_line',
        'village',
        'district_id',
        'regency_id',
        'province_id',
        'postal_code',
        'landmark_name',
        'landmark_distance_km',
        'latitude',
        'longitude',
        'member_since',
        'is_active',
    ];

    protected array $casts = [
        // DECIMAL columns stay driver-native: CI4's float cast rejects the
        // integer 0 SQLite returns, and coordinates/averages are only ever
        // compared as numbers or formatted for display.
        'artisan_count'        => 'int',
        'rating_count'         => 'int',
        'sold_count'           => 'int',
        'district_id'          => '?int',
        'regency_id'           => '?int',
        'province_id'          => '?int',
        'is_active'            => 'bool',
        'is_verified'          => 'bool',
    ];

    protected $validationRules = [
        'display_name'      => 'required|max_length[150]',
        'slug'              => 'required|max_length[120]|is_unique[seller_profiles,id,{id}]',
        'partner_code'      => 'required|max_length[32]|is_unique[seller_profiles,id,{id}]',
        'tagline'           => 'permit_empty|max_length[255]',
        'owner_name'        => 'permit_empty|max_length[150]',
        'craft_focus'       => 'permit_empty|max_length[150]',
        'address_line'      => 'permit_empty|max_length[255]',
        'village'           => 'permit_empty|max_length[100]',
        'postal_code'       => 'permit_empty|max_length[10]',
        'landmark_name'     => 'permit_empty|max_length[150]',
        'latitude'          => 'permit_empty|decimal|validate_latitude',
        'longitude'         => 'permit_empty|decimal|validate_longitude',
        'landmark_distance_km' => 'permit_empty|decimal',
        'district_id'       => 'permit_empty|is_natural_no_zero',
        'regency_id'        => 'permit_empty|is_natural_no_zero',
        'province_id'       => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * Coordinates are validated for plausible Indonesian bounds so a bad
     * geocode cannot be persisted (AGENTS.md: never trust client coordinates).
     */
    public function validateLatitude($value): bool|string
    {
        if ($value === null || $value === '') {
            return true;
        }

        return ((float) $value >= -11) && ((float) $value <= 6)
            ? true
            : 'Latitude must be between -11 and 6 (Indonesia).';
    }

    public function validateLongitude($value): bool|string
    {
        if ($value === null || $value === '') {
            return true;
        }

        return ((float) $value >= 94) && ((float) $value <= 142)
            ? true
            : 'Longitude must be between 94 and 142 (Indonesia).';
    }
}