<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceChatTables extends Migration
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
            'id'                  => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'customer_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'order_id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'subject'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'open'],
            'last_message_at'     => ['type' => 'DATETIME', 'null' => true],
            'last_message_preview' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'customer_unread_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'seller_unread_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'customer_archived_at' => ['type' => 'DATETIME', 'null' => true],
            'seller_archived_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['customer_id', 'seller_id', 'product_id']);
        $this->forge->addKey(['customer_id', 'last_message_at']);
        $this->forge->addKey(['seller_id', 'last_message_at']);
        $this->forge->addKey('product_id');
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('customer_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'SET NULL');
        $this->create('conversations');

        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'sender_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'message_type'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'text'],
            'body'            => ['type' => 'TEXT', 'null' => true],
            'attachment_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'attachment_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'attachment_mime' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'attachment_size' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'read_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['conversation_id', 'created_at']);
        $this->forge->addKey('sender_id');
        $this->forge->addForeignKey('conversation_id', 'conversations', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('sender_id', 'users', 'id', '', 'CASCADE');
        $this->create('messages');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'seller_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'body'        => ['type' => 'TEXT', 'null' => true],
            'position'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'use_count'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['seller_id', 'is_active', 'position']);
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('seller_quick_replies');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach (['seller_quick_replies', 'messages', 'conversations'] as $table) {
            $this->forge->dropTable($table, true);
        }

        $this->db->enableForeignKeyChecks();
    }

    private function create(string $table): void
    {
        $this->forge->createTable($table, false, $this->attributes);
    }
}