<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Province → regency → district → village, with the Kemendagri `code` used by
 * Indonesian address forms.
 *
 * Reference data: seeded and read-only in normal operation, so it carries no
 * ownership scoping and allows every column for administrative use.
 */
class RegionModel extends BaseModel
{
    protected $table = 'regions';

    protected $returnType = 'array';

    protected $allowedFields = [
        'parent_id',
        'type',
        'name',
        'code',
        'latitude',
        'longitude',
    ];

    /**
     * No casts: `latitude`/`longitude` are DECIMAL and are consumed as strings
     * or raw SQL bounds by the map code. CI4's float cast also rejects the
     * integer 0 SQLite hands back for a numeric column.
     */
    protected array $casts = [
        'parent_id' => '?int',
    ];

    protected $validationRules = [
        'type'      => 'required|in_list[province,regency,district,village]',
        'name'      => 'required|max_length[120]',
        'code'      => 'permit_empty|max_length[20]',
        'latitude'  => 'permit_empty|decimal',
        'longitude' => 'permit_empty|decimal',
        'parent_id' => 'permit_empty|is_natural_no_zero',
    ];

    public function getChildren(?int $parentId): array
    {
        return $this->newRows(
            $this->newQuery()->where('parent_id', $parentId)
        );
    }

    /**
     * Resolve a full region path, e.g. `Jawa Tengah › Magelang › Borobudur › Candirejo`.
     */
    public function pathOf(int $regionId): string
    {
        $names    = [];
        $currentId = $regionId;

        // Region trees are shallow (4 levels); the guard prevents a cycle in
        // corrupt data from spinning forever.
        for ($depth = 0; $depth < 8 && $currentId !== null; $depth++) {
            $region = $this->find($currentId);

            if ($region === null) {
                break;
            }

            array_unshift($names, $region['name']);
            $currentId = $region['parent_id'];
        }

        return implode(' › ', $names);
    }
}