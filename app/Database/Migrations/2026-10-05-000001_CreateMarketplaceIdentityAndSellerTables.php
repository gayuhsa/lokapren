<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceIdentityAndSellerTables extends Migration
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
            'id'                   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'slug'                 => ['type' => 'VARCHAR', 'constraint' => 120],
            'partner_code'         => ['type' => 'VARCHAR', 'constraint' => 32],
            'display_name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'owner_name'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tagline'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'          => ['type' => 'TEXT', 'null' => true],
            'craft_focus'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'logo_path'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cover_path'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'banner_path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'address_line'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // Free-text Indonesian geography. The map works from lat/lon only, so
            // these columns exist purely for display and printing on documents.
            'village'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'district'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'regency'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'province'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'postal_code'          => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'landmark_name'        => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'landmark_distance_km' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'latitude'             => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitude'            => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'location_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'artisan_count'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_verified'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'verification_level'   => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'verification_note'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'verified_at'          => ['type' => 'DATETIME', 'null' => true],
            'member_since'         => ['type' => 'DATE', 'null' => true],
            'rating_average'       => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'rating_count'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'sold_count'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'avg_response_minutes' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addUniqueKey('partner_code');
        $this->forge->addKey(['is_active', 'is_verified']);
        $this->forge->addKey(['district', 'is_active']);
        $this->forge->addKey('regency');
        $this->forge->addKey('province');
        $this->forge->addKey(['latitude', 'longitude']);
        $this->forge->addKey('rating_average');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_profiles');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'day_of_week' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'opens_at'    => ['type' => 'TIME', 'null' => true],
            'closes_at'   => ['type' => 'TIME', 'null' => true],
            'is_closed'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['seller_id', 'day_of_week']);
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_business_hours');

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'facility'  => ['type' => 'VARCHAR', 'constraint' => 40],
            'label'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'position'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['seller_id', 'facility']);
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_facilities');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'subtitle'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'body'       => ['type' => 'TEXT', 'null' => true],
            'image_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'position'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['seller_id', 'position']);
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_story_sections');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'media_type'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'caption'          => ['type' => 'TEXT', 'null' => true],
            'file_path'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'thumbnail_path'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'duration_seconds' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'position'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_published'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'published_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['seller_id', 'media_type', 'position']);
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_media');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach ([
            'seller_media',
            'seller_story_sections',
            'seller_facilities',
            'seller_business_hours',
            'seller_profiles',
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