<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceReviewAndAnalyticsTables extends Migration
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
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'order_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'order_item_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'customer_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'rating'        => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'body'          => ['type' => 'TEXT', 'null' => true],
            'variant_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'published'],
            'seller_reply'  => ['type' => 'TEXT', 'null' => true],
            'replied_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['order_id', 'product_id']);
        $this->forge->addUniqueKey('order_item_id');
        $this->forge->addKey(['product_id', 'status', 'created_at']);
        $this->forge->addKey(['seller_id', 'status']);
        $this->forge->addKey(['customer_id', 'created_at']);
        $this->forge->addKey('rating');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('order_item_id', 'order_items', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('reviews');

        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'review_id'  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'file_path'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'thumb_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'position'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['review_id', 'position']);
        $this->forge->addForeignKey('review_id', 'reviews', 'id', '', 'CASCADE');
        $this->create('review_images');

        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'stat_date'        => ['type' => 'DATE'],
            'visit_count'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'visitor_count'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'product_view_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'chat_started_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'order_count'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            // Counted once per order when it first ships, and separately when it
            // is confirmed complete, so a cancelled order never reaches revenue.
            'revenue_total'    => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'completed_count'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'top_product_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['seller_id', 'stat_date']);
        $this->forge->addKey('stat_date');
        $this->forge->addKey('top_product_id');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('top_product_id', 'products', 'id', '', 'SET NULL');
        $this->create('seller_daily_stats');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach ([
            'seller_daily_stats',
            'review_images',
            'reviews',
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