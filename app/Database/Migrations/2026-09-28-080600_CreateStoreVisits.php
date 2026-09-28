<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStoreVisits extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'store_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'visitor_user_id' => [
                'type'    => 'INT',
                'null'    => true,
                'comment' => 'Shield user when signed in, null for anonymous visitors.',
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Optional referrer grouping, e.g. marketplace, direct.',
            ],
            'visited_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('visitor_user_id');
        $this->forge->addKey(['store_id', 'visited_at']);
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('visitor_user_id', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('store_visits', true);
    }

    public function down()
    {
        $this->forge->dropTable('store_visits', true);
    }
}
