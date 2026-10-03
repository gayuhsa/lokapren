<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Runs every Lokapren seeder in dependency order.
 *
 * Order matters: stores resolve seller accounts, and products resolve both
 * stores and categories, so this cannot be run with the individual classes in
 * an arbitrary order.
 *
 * Run with: php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends LokaprenSeeder
{
    public function run(): void
    {
        $this->call('CategorySeeder');
        $this->call('StoreSeeder');
        $this->call('ProductSeeder');
        $this->call('StorePostSeeder');
        $this->call('UserSeeder');
    }
}