<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables the checkout needs: the cart a customer assembles an order from, the
 * shipping and payment options the design offers, and the promo codes that
 * discount a subtotal.
 *
 * Money is stored as BIGINT rupiah throughout. No column holds a formatted
 * string or a float, so totals can always be recalculated from rows rather
 * than trusted back from a form.
 */
class CreateCheckout extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'user_id' => [
                'type'    => 'INT',
                'null'    => false,
                'comment' => 'Shield user the cart belongs to (users.id).',
            ],
            'promo_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'comment'    => 'Applied promo code, validated server-side against promo_codes.',
            ],
            'courier_note' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'Special delivery instructions, e.g. hand over to hotel reception.',
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
        $this->forge->addUniqueKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('carts', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'cart_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'product_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'product_variant_id' => [
                'type' => 'BIGINT',
                'null' => false,
                'comment' => 'The variant whose price and stock apply to this line.',
            ],
            'store_id' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'comment' => 'Denormalised from the product so a cart can be split per seller.',
            ],
            'qty' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
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
        $this->forge->addKey('cart_id');
        $this->forge->addKey('product_id');
        $this->forge->addKey('product_variant_id');
        $this->forge->addForeignKey('cart_id', 'carts', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('product_variant_id', 'product_variants', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('cart_items', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'comment'    => 'Stable identifier persisted on order_deliveries.service_type.',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'eta_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'comment'    => 'Human estimate shown on the option card, e.g. 1-3 Hari Kerja.',
            ],
            'eta_minutes' => [
                'type'    => 'BIGINT',
                'null'    => true,
                'comment' => 'Estimated minutes until arrival; null when open-ended.',
            ],
            'packing_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'cost' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Flat fee in rupiah, never taken from the client.',
            ],
            'sort_order' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('shipping_methods', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'instructions' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'comment'    => 'What the payer must do, e.g. scan the QRIS code.',
            ],
            'fee' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Platform fee in rupiah, added to the order total.',
            ],
            'expires_minutes' => [
                'type'    => 'INT',
                'null'    => true,
                'comment' => 'How long an unpaid order stays payable, e.g. 15 for QRIS.',
            ],
            'sort_order' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('payment_methods', true);

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'auto_increment' => true,
                'null'           => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => false,
            ],
            'discount_amount' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Fixed rupiah discount applied to the subtotal.',
            ],
            'discount_percent' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Percentage discount, 0-100, applied after the fixed amount.',
            ],
            'min_subtotal' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'default' => 0,
                'comment' => 'Minimum subtotal in rupiah before the code may be used.',
            ],
            'max_uses' => [
                'type'    => 'INT',
                'null'    => true,
                'comment' => 'Null means unlimited.',
            ],
            'used_count' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
            'starts_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('promo_codes', true);

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
            'payment_method_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'method_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'comment'    => 'Snapshot of the chosen method code.',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'pending',
                'comment'    => 'pending | paid | failed | expired',
            ],
            'amount' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'comment' => 'Amount the payer was asked for, including fees.',
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'comment'    => 'Gateway reference, e.g. a QRIS or virtual-account number.',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
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
        $this->forge->addKey('order_id');
        $this->forge->addKey('payment_method_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('payment_method_id', 'payment_methods', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('order_payments', true);

        $this->forge->addField([
            'order_id' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'promo_code_id' => [
                'type'    => 'BIGINT',
                'null'    => false,
                'comment' => 'Promo consumed by this order, kept for the used_count audit.',
            ],
        ]);

        $this->forge->addKey(['order_id', 'promo_code_id'], true);
        $this->forge->addForeignKey('order_id', 'orders', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('promo_code_id', 'promo_codes', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('order_promos', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_promos', true);
        $this->forge->dropTable('order_payments', true);
        $this->forge->dropTable('promo_codes', true);
        $this->forge->dropTable('payment_methods', true);
        $this->forge->dropTable('shipping_methods', true);
        $this->forge->dropTable('cart_items', true);
        $this->forge->dropTable('carts', true);
    }
}