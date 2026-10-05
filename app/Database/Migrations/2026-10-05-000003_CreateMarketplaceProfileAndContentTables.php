<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceProfileAndContentTables extends Migration
{
    private array $attributes;

    public function __construct(?Forge $forge = null)
    {
        parent::__construct($forge);

        $this->attributes = ($this->db->getPlatform() === 'MySQLi') ? ['ENGINE' => 'InnoDB'] : [];
    }

    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'full_name'           => ['type' => 'VARCHAR', 'constraint' => 150],
            'nickname'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'photo_path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bio'                 => ['type' => 'TEXT', 'null' => true],
            'birth_date'          => ['type' => 'DATE', 'null' => true],
            'gender'              => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'identity_status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'partner_level'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'artisan_since'       => ['type' => 'DATE', 'null' => true],
            'contribution_total'  => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'orders_total'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'reviews_written'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'prefers_antar_terima' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notify_chat'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addKey('partner_level');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->create('user_profiles');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'label'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'recipient_name'   => ['type' => 'VARCHAR', 'constraint' => 150],
            'recipient_phone'  => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'address_line'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'village'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'district_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'regency_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'province_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'postal_code'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'landmark'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'delivery_notes'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_hotel'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_default'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'is_default']);
        $this->forge->addKey('district_id');
        $this->forge->addKey('regency_id');
        $this->forge->addKey('province_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('district_id', 'regions', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('regency_id', 'regions', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('province_id', 'regions', 'id', '', 'SET NULL');
        $this->create('addresses');

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug'      => ['type' => 'VARCHAR', 'constraint' => 120],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('parent_id');
        $this->forge->addForeignKey('parent_id', 'blog_categories', 'id', '', 'CASCADE');
        $this->create('blog_categories');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'category_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'excerpt'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'body'         => ['type' => 'TEXT', 'null' => true],
            'cover_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'is_published' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'view_count'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['seller_id', 'status']);
        $this->forge->addKey('category_id');
        $this->forge->addKey('published_at');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'blog_categories', 'id', '', 'SET NULL');
        $this->create('blog_posts');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach ([
            'blog_posts',
            'blog_categories',
            'addresses',
            'user_profiles',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->db->enableForeignKeyChecks();
    }

    private function create(string $table): void
    {
        $this->forge->createTable($table, false, $this->attributes);
    }
}