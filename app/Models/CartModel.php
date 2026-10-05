<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * One cart per customer.
 *
 * `customer_id` is server-owned, so a cart can never be created for another
 * account. Cart totals are always recomputed from `cart_items.unit_price`,
 * which is itself copied from the product at add-to-cart time and re-verified
 * at checkout.
 */
class CartModel extends BaseModel
{
    protected $table = 'carts';

    protected $returnType = 'array';

    protected $allowedFields = [
        'status',
    ];

    /**
     * This table carries only `updated_at`, so timestamps are disabled and the
     * column is maintained explicitly by `touch()`.
     */
    protected $useTimestamps = false;

    protected array $casts = [];

    protected $validationRules = [
        'status'   => 'permit_empty|in_list[active,converted,abandoned]',
        'currency' => 'permit_empty|max_length[3]',
    ];

    /**
     * Fetch (or lazily create) the cart for a customer.
     */
    public function forCustomer(int $customerId): array
    {
        $cart = $this->newRow($this->newQuery()->where('customer_id', $customerId));

        if ($cart !== null) {
            return $cart;
        }

        // `carts.customer_id` is UNIQUE, so a losing race surfaces as a duplicate
        // insert; re-reading is what makes this safe.
        try {
            $this->insertRow([
                'customer_id' => $customerId,
                'status'      => 'active',
                'currency'    => 'IDR',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (DatabaseException) {
            // Another request created it first.
        }

        return $this->newRow($this->newQuery()->where('customer_id', $customerId));
    }

    /**
     * A cart is emptied by deleting its items, not by deleting the cart row:
     * `carts.customer_id` is UNIQUE, so the row must survive checkout.
     */
    public function markConverted(int $customerId): bool
    {
        return $this->updateWhere(['status' => 'converted'], ['customer_id' => $customerId]);
    }

    public function touch(int $customerId): void
    {
        $this->updateWhere(['updated_at' => date('Y-m-d H:i:s')], ['customer_id' => $customerId]);
    }
}