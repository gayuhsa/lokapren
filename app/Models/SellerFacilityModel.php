<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Store locator filter chips: "Kelas Memahat", "Parkir Bus Wisata",
 * "Galeri Virtual 360°". `seller_id` is server-owned.
 */
class SellerFacilityModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_facilities';

    protected $returnType = 'array';

    protected $allowedFields = [
        'facility',
        'label',
        'position',
    ];

    protected array $casts = [
        'position' => 'int',
    ];

    protected $validationRules = [
        'facility' => 'required|max_length[40]',
        'label'    => 'permit_empty|max_length[100]',
        'position' => 'permit_empty|is_natural',
    ];

    /**
     * Distinct facility codes available across the locator, for the filter UI.
     *
     * @return list<string>
     */
    public function availableFacilities(): array
    {
        return $this->newRows(
            $this->newQuery()
                ->select('facility')
                ->distinct()
                ->orderBy('facility', 'ASC')
        );
    }

    /**
     * Locate sellers advertising every facility in `$facilities`.
     *
     * Implemented as a grouped HAVING on the join count rather than N separate
     * queries, so the result set stays a single indexed lookup.
     *
     * @param list<string> $facilities
     */
    public function sellersWithAllFacilities(array $facilities)
    {
        if ($facilities === []) {
            return $this->newQuery();
        }

        return $this->newQuery()
            ->select('seller_id')
            ->whereIn('facility', $facilities)
            ->groupBy('seller_id')
            ->having('COUNT(DISTINCT facility)', count($facilities));
    }
}