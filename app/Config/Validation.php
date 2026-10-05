<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Shared validation sets for the marketplace forms.
     *
     * Grouping them here keeps the same field (a phone number, a coordinate, a
     * rupiah amount) validated identically wherever it appears, and keeps the
     * error copy in Indonesian next to the rules it belongs to.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public array $marketplaceSets = [
        // Indonesian mobile number in national or +62 form.
        'phone' => [
            'recipient_phone' => [
                'label'  => 'Nomor Telepon',
                'rules'  => 'permit_empty|regex_match[/^(\+62|62|0)8[1-9][0-9]{7,12}$/]',
                'errors' => [
                    'regex_match' => 'Nomor telepon harus diawali 08 atau +62 (contoh: 081234567890).',
                ],
            ],
        ],

        // A rupiah amount typed into a form. Stored as integer rupiah, so the
        // rule strips thousand separators rather than accepting a float.
        'money' => [
            'price' => [
                'label'  => 'Harga',
                'rules'  => 'required|numeric|greater_than_equal_to[0]|max_length[12]',
                'errors' => [
                    'numeric'  => 'Harga harus berupa angka rupiah.',
                    'max_length' => 'Harga terlalu besar.',
                ],
            ],
        ],

        // Coordinates typed by a seller picking a workshop on the map. Bounds
        // cover Indonesia, so a bad geocode cannot be stored (AGENTS.md: never
        // trust client-submitted coordinates).
        'coordinate' => [
            'latitude' => [
                'label'  => 'Lintang',
                'rules'  => 'permit_empty|decimal|numeric|greater_than_equal_to[-11]|less_than_equal_to[6]',
                'errors' => [
                    'numeric'              => 'Lintang harus berupa angka.',
                    'greater_than_equal_to' => 'Lintang harus antara -11 dan 6 (wilayah Indonesia).',
                    'less_than_equal_to'   => 'Lintang harus antara -11 dan 6 (wilayah Indonesia).',
                ],
            ],
            'longitude' => [
                'label'  => 'Bujur',
                'rules'  => 'permit_empty|decimal|numeric|greater_than_equal_to[95]|less_than_equal_to[141]',
                'errors' => [
                    'numeric'              => 'Bujur harus berupa angka.',
                    'greater_than_equal_to' => 'Bujur harus antara 95 dan 141 (wilayah Indonesia).',
                    'less_than_equal_to'   => 'Bujur harus antara 95 dan 141 (wilayah Indonesia).',
                ],
            ],
        ],
    ];
}
