<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class StoreModel extends Model
{
    protected $table = 'stores';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'name',
        'slug',
        'tagline',
        'description',
        'logo_url',
        'cover_url',
        'address_line',
        'subdistrict',
        'city',
        'lat',
        'lng',
        'phone',
        'opening_hours',
        'partner_level',
    ];

    protected $validationRules = [
        'name'     => 'required|max_length[150]',
        'slug'     => 'required|max_length[150]|is_unique[stores.slug,id,{id}]',
        'tagline'  => 'permit_empty|max_length[255]',
        'subdistrict' => 'permit_empty|max_length[120]',
        'city'     => 'permit_empty|max_length[120]',
        'phone'    => 'permit_empty|max_length[25]',
    ];
}
