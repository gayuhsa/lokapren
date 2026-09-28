<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChat extends Migration
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
            'customer_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user chatting with the seller (users.id).',
            ],
            'linked_order_id' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'comment' => 'Order surfaced in the thread as a related order card.',
            ],
            'last_message_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_message_preview' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'customer_unread_count' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Unread messages for the customer.',
            ],
            'store_unread_count' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Unread messages for the seller.',
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
        $this->forge->addUniqueKey(['store_id', 'customer_id']);
        $this->forge->addKey('customer_id');
        $this->forge->addKey('last_message_at');
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('customer_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('linked_order_id', 'orders', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('conversations', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'conversation_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'sender_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user who sent the message (users.id).',
            ],
            'sender_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'comment'    => 'customer | seller',
            ],
            'message_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'text',
                'comment'    => 'text | image | video | product | order',
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'media_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'product_id' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'comment' => 'Product attached as a craft card in the message.',
            ],
            'order_id' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'comment' => 'Order attached as a related order card in the message.',
            ],
            'is_read' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => false,
            ],
            'read_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('sender_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('order_id');
        $this->forge->addKey(['conversation_id', 'created_at']);
        $this->forge->addForeignKey('conversation_id', 'conversations', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('sender_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('messages', true);
    }

    public function down()
    {
        $this->forge->dropTable('messages', true);
        $this->forge->dropTable('conversations', true);
    }
}
