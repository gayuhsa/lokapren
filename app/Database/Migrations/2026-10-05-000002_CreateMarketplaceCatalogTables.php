<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceCatalogTables extends Migration
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
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'craft_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'position'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey(['parent_id', 'position']);
        $this->forge->addKey(['is_active', 'position']);
        $this->forge->addForeignKey('parent_id', 'product_categories', 'id', '', 'CASCADE');
        $this->create('product_categories');

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'category_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_code'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'slug'               => ['type' => 'VARCHAR', 'constraint' => 150],
            'name'               => ['type' => 'VARCHAR', 'constraint' => 180],
            'subtitle'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'summary'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'description'        => ['type' => 'TEXT', 'null' => true],
            'story'              => ['type' => 'TEXT', 'null' => true],
            'material'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'finishing'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'price'              => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true],
            'stock'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'low_stock_threshold' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'weight_gram'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'made_to_order'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'production_days'    => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'null' => true],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'is_active'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_featured'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'rating_average'     => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'rating_count'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'sold_count'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'orders_count'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'view_count'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'published_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addUniqueKey('product_code');
        $this->forge->addKey('seller_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey(['status', 'is_active']);
        $this->forge->addKey(['is_featured', 'sold_count']);
        $this->forge->addKey('price');
        $this->forge->addKey('rating_average');
        $this->forge->addKey('published_at');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'product_categories', 'id', '', 'SET NULL');
        $this->create('products');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'variant_code' => ['type' => 'VARCHAR', 'constraint' => 32],
            'sku'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'label'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'price'        => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'null' => true],
            'stock'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'weight_gram'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_default'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'position'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('variant_code');
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey(['product_id', 'is_active', 'position']);
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->create('product_variants');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'variant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'file_path'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'thumb_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'alt_text'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'caption'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_primary'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'position'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['product_id', 'position']);
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->create('product_images');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach ([
            'product_images',
            'product_variants',
            'products',
            'product_categories',
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