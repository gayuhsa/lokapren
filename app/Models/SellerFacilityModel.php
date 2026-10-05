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
     * The facility vocabulary, code => Indonesian label.
     *
     * There is no lookup table for this, so the codes live here as the single
     * source of truth: the seller editor only offers these, and
     * `ShopService::saveFacilities()` rejects anything outside the list. That
     * keeps the locator filter from accumulating arbitrary text.
     *
     * @var array<string, string>
     */
    public const FACILITIES = [
        'kelas_memahat'   => 'Kelas Memahat',
        'kelas_mengamik'  => 'Kelas Mengamik',
        'kelas_melukis'   => 'Kelas Melukis',
        'galeri'          => 'Galeri Karya',
        'parkir_bus'      => 'Parkir Bus Wisata',
        'parkir_mobil'    => 'Parkir Mobil',
        'wc'              => 'Toilet Umum',
        'wifi'            => 'WiFi',
        'tempat_ibadah'   => 'Tempat Ibadah Terdekat',
        'opsi_takeaway'  => 'Bisa Dibeli Offline',
        'fasilitas_simpan' => 'Fasilitas Simpan Barang',
    ];

    /**
     * The facility vocabulary as `code => label`.
     *
     * @return array<string, string>
     */
    public function availableFacilities(): array
    {
        return self::FACILITIES;
    }

    /**
     * Distinct facility codes currently advertised by at least one seller,
     * which is what the public locator filter shows.
     *
     * @return list<string>
     */
    public function advertisedFacilities(): array
    {
        return array_column($this->newRows(
            $this->newQuery()
                ->select('facility')
                ->distinct()
                ->orderBy('facility', 'ASC')
        ), 'facility');
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