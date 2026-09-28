<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStores extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'user_id' => [
                'type'       => 'INT',
                'null'       => false,
                'comment'    => 'Shield user owning this store (users.id). One store per seller.',
            ],
            'store_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'comment'    => 'Public partner code shown in the UI, e.g. BDR-88219.',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'tagline' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'logo_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'cover_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'address_line' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'subdistrict' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'comment'    => 'Kecamatan, e.g. Borobudur.',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'comment'    => 'Kabupaten/Kota, e.g. Magelang.',
            ],
            'lat' => [
                'type'       => 'DECIMAL',
                'constraint' => [10, 7],
                'null'       => true,
            ],
            'lng' => [
                'type'       => 'DECIMAL',
                'constraint' => [10, 7],
                'null'       => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'null'       => true,
                'comment'    => 'Indonesian phone number, e.g. 081234567890.',
            ],
            'opening_hours' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON object of weekday => [open, close] pairs, e.g. {"Mon":["08:00","17:00"]}.',
            ],
            'verification_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'pending | verified',
            ],
            'verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'partner_level' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Mitra level label shown on the dashboard, e.g. Mitra Utama.',
            ],
            'is_active' => [
                'type'       => 'BOOLEAN',
                'null'       => false,
                'default'    => true,
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
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('store_code');
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('stores', true);
    }

    public function down()
    {
        $this->forge->dropTable('stores', true);
    }
}
