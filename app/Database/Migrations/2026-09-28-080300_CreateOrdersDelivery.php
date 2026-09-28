<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrdersDelivery extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'order_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
                'comment'    => 'Public order number, e.g. LKP-2025-0001.',
            ],
            'customer_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user who placed the order (users.id).',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'pending | confirmed | processing | ready | shipped | completed | cancelled',
            ],
            'subtotal' => [
                'type'     => 'BIGINT',
                'null'     => false,
                'default'  => 0,
                'comment'  => 'Sum of order item line totals, in rupiah.',
            ],
            'delivery_fee' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
            ],
            'discount_amount' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
            ],
            'tax_amount' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
            ],
            'total' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Server-calculated grand total in rupiah.',
            ],
            'recipient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'recipient_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 25,
                'null'       => false,
            ],
            'address_line' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'subdistrict' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'postal_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'courier_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Special delivery instructions, e.g. hand over to hotel reception.',
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
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
        $this->forge->addUniqueKey('order_code');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('customer_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('orders', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'order_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'store_id' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'comment' => 'Seller fulfilling this line; one order may span multiple stores.',
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'product_variant_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'product_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
                'comment'    => 'Snapshot at purchase time so history survives catalog edits.',
            ],
            'variant_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'unit_price' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'qty' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
            'line_total' => [
                'type' => 'BIGINT',
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
        $this->forge->addKey('order_id');
        $this->forge->addKey('store_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('product_variant_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('store_id', 'stores', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('product_variant_id', 'product_variants', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('order_items', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'order_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'service_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'comment'    => 'pesan_antar_terima | ekspedisi_reguler | cargo',
            ],
            'courier_label' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
                'comment'    => 'Courier name shown in the UI, e.g. Kurir Kriya Lokal, JNE, J&T.',
            ],
            'cost' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'eta_minutes' => [
                'type'       => 'BIGINT',
                'null'       => true,
                'comment'    => 'Estimated minutes until arrival, e.g. 180 for same-day 1-3 hour delivery.',
            ],
            'tracking_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'comment'    => 'Courier receipt number (resi).',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'pending | picked_up | in_transit | out_for_delivery | delivered | failed',
            ],
            'progress' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Completion percentage derived from status, 0-100.',
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'picked_up_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'delivered_at' => [
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
        $this->forge->addUniqueKey('order_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('order_deliveries', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'delivery_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'comment'    => 'Mirrors the parent delivery status at the time of the scan.',
            ],
            'note' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('delivery_id');
        $this->forge->addForeignKey('delivery_id', 'order_deliveries', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('delivery_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('delivery_events', true);
        $this->forge->dropTable('order_deliveries', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
    }
}
