<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Crafts categories for the catalogue.
 *
 * Idempotent: matched on `slug`, so re-running never duplicates a category and
 * never changes its id, which products depend on.
 *
 * Run with: php spark db:seed CategorySeeder
 */
class CategorySeeder extends LokaprenSeeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kriya Ukiran Kayu', 'slug' => 'ukiran-kayu'],
            ['name' => 'Batik Tulis', 'slug' => 'batik-tulis'],
            ['name' => 'Anyaman Bambu', 'slug' => 'anyaman-bambu'],
            ['name' => 'Kriya Tembaga', 'slug' => 'kriya-tembaga'],
            ['name' => 'Keramik Seni', 'slug' => 'keramik-seni'],
        ];

        foreach ($categories as $category) {
            $id = $this->upsertRow('categories', 'slug', $category['slug'], [
                'name'      => $category['name'],
                'is_active' => true,
            ]);

            $this->report(sprintf('  %-22s id=%d', $category['name'], $id));
        }
    }
}