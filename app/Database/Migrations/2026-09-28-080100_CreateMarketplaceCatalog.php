<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarketplaceCatalog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => true,
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
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('categories', true);

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
            'category_id' => [
                'type'       => 'BIGINT',
                'null'       => true,
                'comment'    => 'Craft category, e.g. Ukiran Kayu Jati.',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'attributes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON of craft attributes, e.g. material, motif, measurement, finishing.',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'draft',
                'comment'    => 'draft | active | archived',
            ],
            'is_featured' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => false,
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
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('store_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('products', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
                'comment'    => 'Option label, e.g. Ukuran L (35x28 cm).',
            ],
            'sku' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'price' => [
                'type'       => 'BIGINT',
                'null'       => false,
                'comment'    => 'Price in whole rupiah, e.g. 450000 = Rp450.000.',
            ],
            'stock' => [
                'type'       => 'INT',
                'null'       => false,
                'default'    => 0,
            ],
            'attributes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON of variant attributes, e.g. motif, size, finishing.',
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => true,
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
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('product_variants', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'alt' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'sort_order' => [
                'type'       => 'INT',
                'null'       => false,
                'default'    => 0,
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
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('product_images', true);
    }

    public function down()
    {
        $this->forge->dropTable('product_images', true);
        $this->forge->dropTable('product_variants', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('categories', true);
    }
}
