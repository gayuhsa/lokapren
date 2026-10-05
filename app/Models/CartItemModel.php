<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Cart lines. `cart_id` links to the owning cart, `seller_id` is denormalized
 * so the cart can be grouped per sanggar without a join through `products`.
 *
 * Both `cart_id` and `seller_id` are server-owned. `unit_price` is copied from
 * the product by application code — it is never accepted from the request
 * (AGENTS.md: "Never trust client-submitted prices").
 */
class CartItemModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'cart_items';

    protected $returnType = 'array';

    protected $allowedFields = [
        'product_id',
        'variant_id',
        'quantity',
        'note',
    ];

    protected array $casts = [
        'cart_id'    => 'int',
        'product_id' => 'int',
        'variant_id' => '?int',
        'quantity'   => 'int',
        'unit_price' => 'int',
    ];

    protected $validationRules = [
        'cart_id'    => 'required|is_natural_no_zero',
        'product_id' => 'required|is_natural_no_zero',
        'variant_id' => 'permit_empty|is_natural_no_zero',
        'quantity'   => 'required|is_natural|greater_than[0]',
        'note'       => 'permit_empty|max_length[255]',
    ];

    /**
     * Existing line for the same product/variant, if any.
     *
     * `UNIQUE (cart_id, product_id, variant_id)` does not collapse rows where
     * `variant_id IS NULL` on MariaDB or SQLite, so the lookup is explicit and
     * a `variant_id` null comparison uses `IS NULL` rather than `= NULL`.
     *
     * @param int|null $variantId
     */
    public function findLine(int $cartId, int $productId, ?int $variantId): ?array
    {
        $builder = $this->newQuery()
            ->where('cart_id', $cartId)
            ->where('product_id', $productId);

        if ($variantId === null) {
            $builder->where('variant_id', null);
        } else {
            $builder->where('variant_id', $variantId);
        }

        return $this->newRow($builder);
    }

    /**
     * A cart's lines joined to product detail, ready for the cart view.
     */
    public function detailedFor(int $cartId)
    {
        return $this->builder()
            ->select('cart_items.*, products.name AS product_name, products.slug AS product_slug, products.price AS product_price, products.stock AS product_stock, products.stock AS available_stock, products.weight_gram, products.made_to_order, product_variants.label AS variant_label, product_variants.stock AS variant_stock')
            ->join('products', 'products.id = cart_items.product_id', 'left')
            ->join('product_variants', 'product_variants.id = cart_items.variant_id', 'left')
            ->where('cart_items.cart_id', $cartId)
            ->where('products.deleted_at', null)
            ->orderBy('cart_items.seller_id', 'ASC')
            ->orderBy('cart_items.id', 'ASC');
    }

    /**
     * Server-side subtotal for a cart: sum(quantity * unit_price).
     *
     * Integer arithmetic only, per AGENTS.md.
     */
    public function subtotalFor(int $cartId): int
    {
        $row = $this->newQuery()
            ->select('SUM(quantity * unit_price) AS subtotal')
            ->where('cart_id', $cartId)
            ->get()
            ->getRowArray();

        return (int) ($row['subtotal'] ?? 0);
    }
}