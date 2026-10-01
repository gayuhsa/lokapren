<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Removes the orphaned seller_profiles and customer_profiles tables.
 */
class DropOrphanedProfileTables extends Migration
{
    public function up()
    {
        $this->forge->dropTable('seller_profiles', true, true);
        $this->forge->dropTable('customer_profiles', true, true);
    }

    public function down()
    {
        // No-op: dropping these orphaned tables is a one-way cleanup.
    }
}
