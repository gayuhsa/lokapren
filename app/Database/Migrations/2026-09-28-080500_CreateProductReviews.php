<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductReviews extends Migration
{
    public function up()
    {
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
            'product_variant_id' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'comment' => 'Purchased variant shown as "Varian" on the review.',
            ],
            'order_id' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'comment' => 'Order proving the review is a genuine purchase.',
            ],
            'customer_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user who wrote the review (users.id).',
            ],
            'rating' => [
                'type'       => 'INT',
                'null'       => false,
                'comment'    => 'Star rating 1-5.',
            ],
            'comment' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('customer_id');
        $this->forge->addKey('order_id');
        $this->forge->addUniqueKey(['order_id', 'product_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('product_variant_id', 'product_variants', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('product_reviews', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'review_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'sort_order' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
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
        $this->forge->addKey('review_id');
        $this->forge->addForeignKey('review_id', 'product_reviews', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('review_photos', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'review_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'voter_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user who marked the review as helpful (users.id).',
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
        $this->forge->addKey('review_id');
        $this->forge->addKey('voter_id');
        $this->forge->addUniqueKey(['review_id', 'voter_id']);
        $this->forge->addForeignKey('review_id', 'product_reviews', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('voter_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('review_helpful_votes', true);
    }

    public function down()
    {
        $this->forge->dropTable('review_helpful_votes', true);
        $this->forge->dropTable('review_photos', true);
        $this->forge->dropTable('product_reviews', true);
    }
}
