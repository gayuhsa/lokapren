<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateMarketplaceCommerceTables extends Migration
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
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'customer_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'currency'    => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'IDR'],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('customer_id');
        $this->forge->addForeignKey('customer_id', 'users', 'id', '', 'CASCADE');
        $this->create('carts');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'cart_id'     => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            // Denormalized from the product so the cart view can group lines per
            // sanggar without joining through `products`. Always server-owned.
            'seller_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'variant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'quantity'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'unit_price'  => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true],
            'note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['cart_id', 'product_id', 'variant_id']);
        $this->forge->addKey('seller_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('cart_id', 'carts', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->create('cart_items');

        $this->forge->addField([
            'id'                      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_number'            => ['type' => 'VARCHAR', 'constraint' => 32],
            'customer_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'seller_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending_payment'],
            'fulfillment_type'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ship'],
            'production_progress'     => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
            // No payment gateway is integrated. `payment_method` records how the
            // buyer intends to pay (always `manual` today) and `payment_status`
            // tracks the confirmation the seller records by hand; neither is ever
            // settled by the application.
            'payment_method'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'payment_status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'unpaid'],
            'currency'                => ['type' => 'CHAR', 'constraint' => 3, 'default' => 'IDR'],
            'subtotal'                => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'shipping_total'          => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'service_total'           => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'tax_total'               => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'donation_total'          => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'grand_total'             => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'platform_fee'            => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'seller_earning'          => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true, 'default' => 0],
            'ship_recipient_name'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'ship_recipient_phone'    => ['type' => 'VARCHAR', 'constraint' => 25, 'null' => true],
            'ship_address_line'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            // Snapshotted from the customer's address at checkout. Geography is the
            // free text the customer typed; lat/lon is kept so the seller can
            // place the parcel on a map.
            'ship_village'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ship_district'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ship_regency'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ship_province'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ship_postal_code'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'ship_latitude'           => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'ship_longitude'          => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'ship_landmark'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'ship_notes'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'courier_code'            => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'courier_name'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tracking_number'         => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'estimated_delivery_at'   => ['type' => 'DATE', 'null' => true],
            'customer_note'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'cancellation_reason'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'placed_at'               => ['type' => 'DATETIME', 'null' => true],
            'paid_at'                 => ['type' => 'DATETIME', 'null' => true],
            'shipped_at'              => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'            => ['type' => 'DATETIME', 'null' => true],
            'completed_at'            => ['type' => 'DATETIME', 'null' => true],
            'cancelled_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'              => ['type' => 'DATETIME', 'null' => true],
            'updated_at'              => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('order_number');
        $this->forge->addKey(['customer_id', 'status']);
        $this->forge->addKey(['seller_id', 'status']);
        $this->forge->addKey('status');
        $this->forge->addKey('placed_at');
        $this->forge->addKey('ship_district');
        $this->forge->addKey('ship_regency');
        $this->forge->addKey('ship_province');
        $this->forge->addForeignKey('customer_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('seller_id', 'users', 'id', '', 'CASCADE');
        $this->create('orders');

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'variant_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_name'  => ['type' => 'VARCHAR', 'constraint' => 180],
            'variant_label' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sku'           => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'image_path'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'unit_price'    => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true],
            'quantity'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'subtotal'      => ['type' => 'BIGINT', 'constraint' => 15, 'unsigned' => true],
            'note'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'product_id']);
        $this->forge->addKey('product_id');
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'RESTRICT');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'SET NULL');
        $this->create('order_items');

        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'      => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'courier_code'  => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'courier_name'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'service_level' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'tracking_number' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'shipped_at'    => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('order_id');
        $this->forge->addKey('tracking_number');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->create('order_shipments');

        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'order_id'    => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true],
            'from_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'to_status'   => ['type' => 'VARCHAR', 'constraint' => 20],
            'note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'actor_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['order_id', 'created_at']);
        $this->forge->addKey('actor_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('actor_id', 'users', 'id', '', 'SET NULL');
        $this->create('order_status_history');
    }

    public function down(): void
    {
        $this->db->disableForeignKeyChecks();

        foreach ([
            'order_status_history',
            'order_shipments',
            'order_items',
            'orders',
            'cart_items',
            'carts',
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