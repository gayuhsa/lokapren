<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CartItemModel;
use App\Models\CartModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use CodeIgniter\Database\BaseBuilder;

/**
 * Cart reads and writes, including the authoritative price of every line.
 *
 * The cart stores `unit_price` as a snapshot taken when the line was added, so
 * the cart view can render a total without a join. Checkout does **not** trust
 * that snapshot: `repriceForCheckout()` re-reads every product and variant and
 * rebuilds the lines from live data, so a client that edited a hidden field, or
 * a seller who changed a price after the customer loaded the page, cannot make
 * the order total wrong (AGENTS.md: "Product prices and order totals must come
 * from trusted application/database data").
 */
class CartService
{
    private CartModel $carts;
    private CartItemModel $items;
    private ProductModel $products;
    private ProductVariantModel $variants;
    private ProductImageModel $images;

    public function __construct()
    {
        $this->carts    = new CartModel();
        $this->items    = new CartItemModel();
        $this->products = new ProductModel();
        $this->variants = new ProductVariantModel();
        $this->images   = new ProductImageModel();
    }

    /**
     * A cart with its lines grouped per sanggar.
     *
     * @return array{
     *     cart: array<string, mixed>,
     *     groups: list<array<string, mixed>>,
     *     item_count: int,
     *     subtotal: int,
     * }
     */
    public function viewFor(int $customerId): array
    {
        $cart     = $this->carts->forCustomer($customerId);
        $cartId   = (int) $cart['id'];
        $lines    = $this->linesFor($cartId);
        $groups   = $this->groupBySeller($lines);
        $subtotal = 0;
        $count    = 0;

        foreach ($lines as $line) {
            $subtotal += (int) $line['quantity'] * (int) $line['unit_price'];
            $count    += (int) $line['quantity'];
        }

        return [
            'cart'        => $cart,
            'groups'      => $groups,
            'item_count'  => $count,
            'subtotal'    => $subtotal,
        ];
    }

    /**
     * How many units are in the cart, for the header badge.
     *
     * Read-only: a signed-in seller (or a customer who never shopped) has no
     * cart, so the badge shows zero without creating a row.
     */
    public function badgeCount(int $customerId): int
    {
        $cart = $this->carts->findForCustomer($customerId);

        if ($cart === null) {
            return 0;
        }

        $row = $this->items->newQuery()
            ->selectSum('quantity', 'total')
            ->where('cart_id', (int) $cart['id'])
            ->get()
            ->getRowArray();

        return (int) ($row['total'] ?? 0);
    }

    /**
     * Add a product to the cart, or increase the existing line.
     *
     * The unit price is copied from the variant when there is one, otherwise
     * from the product. Nothing about the price, seller or stock comes from the
     * request.
     *
     * @param int|null $variantId
     *
     * @return array{ok: bool, message: string}
     */
    public function add(int $customerId, int $productId, ?int $variantId, int $quantity = 1, ?string $note = null): array
    {
        $quantity = max(1, $quantity);

        $product = $this->products->find($productId);

        if ($product === null || ! $product['is_active'] || $product['status'] !== ProductModel::STATUS_PUBLISHED) {
            return ['ok' => false, 'message' => 'Produk tidak tersedia.'];
        }

        $price = (int) $product['price'];
        $stock = (int) $product['stock'];

        if ($variantId !== null) {
            $variant = $this->variants->find($variantId);

            // The variant must belong to the product being added, otherwise a
            // client could pair a cheap variant with an expensive product.
            if ($variant === null || (int) $variant['product_id'] !== $productId || ! $variant['is_active']) {
                return ['ok' => false, 'message' => 'Pilihan ukuran tidak valid.'];
            }

            $price = $this->variants->effectivePrice($variant, $product);
            $stock = (int) $variant['stock'];
        }

        if ($product['made_to_order']) {
            $stock = PHP_INT_MAX;
        }

        $cart    = $this->carts->forCustomer($customerId);
        $cartId  = (int) $cart['id'];
        $existing = $this->items->findLine($cartId, $productId, $variantId);
        $wanted  = $quantity + (int) ($existing['quantity'] ?? 0);

        if ($wanted > $stock) {
            return ['ok' => false, 'message' => 'Stok tidak mencukupi.'];
        }

        $this->carts->touch($customerId);

        if ($existing !== null) {
            // `cart_id` and `seller_id` stay as they are; only the quantity moves.
            $this->items->updateWhere(
                ['quantity' => $wanted, 'note' => $note, 'unit_price' => $price],
                ['id' => (int) $existing['id']]
            );
        } else {
            $this->items->insertRow([
                'cart_id'    => $cartId,
                // Denormalized for the per-sanggar grouping in the cart view.
                'seller_id'  => (int) $product['seller_id'],
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $quantity,
                'unit_price' => $price,
                'note'       => $note,
            ]);
        }

        return ['ok' => true, 'message' => 'Kriya ditambahkan ke keranjang.'];
    }

    /**
     * Set an absolute quantity on a line the customer owns.
     *
     * A quantity of zero removes the line, which is what the cart form's
     * "remove" control posts.
     */
    public function setQuantity(int $customerId, int $itemId, int $quantity): bool
    {
        $line = $this->findLineOwned($customerId, $itemId);

        if ($line === null) {
            return false;
        }

        if ($quantity < 1) {
            return $this->remove($customerId, $itemId);
        }

        $product = $this->products->find((int) $line['product_id']);

        if ($product === null) {
            return false;
        }

        $stock = $product['made_to_order'] ? PHP_INT_MAX : (int) $product['stock'];

        if ($line['variant_id'] !== null) {
            $variant = $this->variants->find((int) $line['variant_id']);

            if ($variant !== null && ! $product['made_to_order']) {
                $stock = (int) $variant['stock'];
            }
        }

        if ($quantity > $stock) {
            return false;
        }

        $this->carts->touch($customerId);

        return $this->items->updateWhere(['quantity' => $quantity], ['id' => $itemId]);
    }

    /**
     * Apply a whole batch of quantity changes from the cart form.
     *
     * @param array<int|string, int> $quantities cart_item_id => quantity
     *
     * @return array{updated: int, rejected: int}
     */
    public function applyQuantities(int $customerId, array $quantities): array
    {
        $updated  = 0;
        $rejected = 0;

        foreach ($quantities as $itemId => $quantity) {
            if ($this->setQuantity($customerId, (int) $itemId, (int) $quantity)) {
                $updated++;
            } else {
                $rejected++;
            }
        }

        return ['updated' => $updated, 'rejected' => $rejected];
    }

    public function remove(int $customerId, int $itemId): bool
    {
        if ($this->findLineOwned($customerId, $itemId) === null) {
            return false;
        }

        $this->carts->touch($customerId);

        return (bool) $this->items->delete($itemId);
    }

    public function clear(int $customerId): void
    {
        $cart = $this->carts->forCustomer($customerId);

        $this->items->newQuery()->where('cart_id', (int) $cart['id'])->delete();
        $this->carts->touch($customerId);
    }

    /**
     * A cart line, but only when it belongs to this customer's cart.
     *
     * @return array<string, mixed>|null
     */
    public function findLineOwned(int $customerId, int $itemId): ?array
    {
        $cart = $this->carts->forCustomer($customerId);

        return $this->items->newRow(
            $this->items->newQuery()
                ->where('id', $itemId)
                ->where('cart_id', (int) $cart['id'])
        );
    }

    /**
     * Rebuild the cart's lines from live product data.
     *
     * Called by checkout before any order row is written. Each line is
     * re-priced from the database and dropped when the product has since been
     * deactivated or deleted, so the resulting order can only ever contain
     * sellable items at their real prices.
     *
     * @return array{
     *     lines: list<array<string, mixed>>,
     *     per_seller: array<int, list<array<string, mixed>>>,
     *     subtotal: int,
     *     dropped: int,
     * }
     */
    public function repriceForCheckout(int $customerId): array
    {
        $cart  = $this->carts->forCustomer($customerId);
        $cartId = (int) $cart['id'];

        $lines   = [];
        $dropped = [];

        foreach ($this->linesFor($cartId) as $line) {
            $product = $this->products->find((int) $line['product_id']);

            if ($product === null || ! $product['is_active'] || $product['status'] !== ProductModel::STATUS_PUBLISHED) {
                $this->items->delete((int) $line['id']);
                $dropped[] = $product['name'] ?? 'Produk yang tidak tersedia';

                continue;
            }

            $price = (int) $product['price'];
            $stock = (int) $product['stock'];
            $label = null;
            $sku   = $product['product_code'];

            if ($line['variant_id'] !== null) {
                $variant = $this->variants->find((int) $line['variant_id']);

                if ($variant === null || ! $variant['is_active']) {
                    $this->items->delete((int) $line['id']);
                    $dropped[] = $product['name'];

                    continue;
                }

                $price = $this->variants->effectivePrice($variant, $product);
                $label = $variant['label'];
                $sku   = $variant['sku'] ?? $sku;
                $stock = $product['made_to_order'] ? PHP_INT_MAX : (int) $variant['stock'];
            } elseif ($product['made_to_order']) {
                $stock = PHP_INT_MAX;
            }

            // A seller's stock may have fallen below a quantity already in the
            // cart; clamp rather than reject the whole checkout.
            $quantity = (int) $line['quantity'];

            if ($quantity > $stock) {
                $quantity = $stock;
                $this->items->updateWhere(['quantity' => $quantity], ['id' => (int) $line['id']]);
            }

            if ($quantity < 1) {
                $this->items->delete((int) $line['id']);
                $dropped[] = $product['name'];

                continue;
            }

            $lines[] = [
                'seller_id'     => (int) $product['seller_id'],
                'product_id'    => (int) $product['id'],
                'variant_id'    => $line['variant_id'] !== null ? (int) $line['variant_id'] : null,
                'product_name'  => $product['name'],
                'variant_label' => $label,
                'sku'           => $sku,
                'cover_path'    => $this->images->coverFor((int) $product['id']),
                'unit_price'    => $price,
                'quantity'      => $quantity,
                'subtotal'      => $price * $quantity,
                'note'          => $line['note'],
            ];
        }

        $perSeller = [];

        foreach ($lines as $line) {
            $perSeller[$line['seller_id']][] = $line;
        }

        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $line['subtotal'];
        }

        return [
            'lines'      => $lines,
            'per_seller' => $perSeller,
            'subtotal'   => $subtotal,
            'dropped'    => $dropped,
        ];
    }

    /**
     * Empty the cart once its lines have become orders.
     */
    public function markConverted(int $customerId): void
    {
        $this->clear($customerId);
        $this->carts->markConverted($customerId);
    }

    /**
     * Cart lines joined to product and variant detail.
     *
     * @return list<array<string, mixed>>
     */
    public function linesFor(int $cartId): array
    {
        $builder = $this->items->detailedFor($cartId);

        return $this->hydrateLines($builder);
    }

    /**
     * Split cart lines into one group per sanggar, for the cart and checkout
     * views. Each group carries its own subtotal so the multi-seller checkout
     * can show one summary per order it is about to create.
     *
     * @param list<array<string, mixed>> $lines
     *
     * @return list<array<string, mixed>>
     */
    public function groupBySeller(array $lines): array
    {
        $groups = [];

        foreach ($lines as $line) {
            $sellerId = (int) $line['seller_id'];

            if (! isset($groups[$sellerId])) {
                // The sanggar's free-text place, so the cart can show where
                // each order is going without a second lookup per group.
                $place = lokasi_teks([
                    'village'  => $line['shop_village'] ?? null,
                    'district' => $line['shop_district'] ?? null,
                    'regency'  => $line['shop_regency'] ?? null,
                ]);

                $groups[$sellerId] = [
                    'seller_id'  => $sellerId,
                    'shop_name'  => $line['shop_name'] ?? null,
                    'shop_slug'  => $line['shop_slug'] ?? null,
                    'shop_logo'  => $line['shop_logo'] ?? null,
                    'shop_place' => $place,
                    'lines'      => [],
                    'subtotal'   => 0,
                ];
            }

            $groups[$sellerId]['lines'][] = $line;
            $groups[$sellerId]['subtotal'] += (int) $line['quantity'] * (int) $line['unit_price'];
        }

        return array_values($groups);
    }

    /**
     * Run a builder's rows through the cart item casts, since `detailedFor()`
     * composes the query directly rather than going through `find()`.
     *
     * @return list<array<string, mixed>>
     */
    private function hydrateLines(BaseBuilder $builder): array
    {
        $lines = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $lines[] = [
                'id'            => (int) $row['id'],
                'cart_id'       => (int) $row['cart_id'],
                'seller_id'     => (int) $row['seller_id'],
                'product_id'    => (int) $row['product_id'],
                'variant_id'    => $row['variant_id'] !== null ? (int) $row['variant_id'] : null,
                'quantity'      => (int) $row['quantity'],
                'unit_price'    => (int) $row['unit_price'],
                // Recomputed rather than read: the stored unit price is a
                // snapshot, and the subtotal the buyer sees must match the
                // price that will be charged at checkout.
                'subtotal'      => (int) $row['quantity'] * (int) $row['unit_price'],
                'line_total'    => (int) $row['quantity'] * (int) $row['unit_price'],
                'note'          => $row['note'] ?? null,
                'product_name'  => $row['product_name'] ?? null,
                'slug'          => $row['product_slug'] ?? null,
                'product_slug'  => $row['product_slug'] ?? null,
                'variant_label' => $row['variant_label'] ?? null,
                // Filled in after the loop: `products` has no cover column.
                'cover_path'    => null,
                'product_stock' => isset($row['available_stock']) ? (int) $row['available_stock'] : null,
                // The quantity ceiling: a made-to-order piece has no stock cap.
                'stock'         => (bool) ($row['made_to_order'] ?? false)
                    ? 99
                    : (isset($row['variant_stock']) && $row['variant_stock'] !== null
                        ? (int) $row['variant_stock']
                        : (int) ($row['available_stock'] ?? 0)),
                'weight_gram'   => $row['weight_gram'] !== null ? (int) $row['weight_gram'] : null,
                'made_to_order' => (bool) ($row['made_to_order'] ?? false),
                'shop_name'     => $row['shop_name'] ?? null,
                'shop_slug'     => $row['shop_slug'] ?? null,
                'shop_logo'     => $row['shop_logo'] ?? null,
                'shop_village'  => $row['shop_village'] ?? null,
                'shop_district' => $row['shop_district'] ?? null,
                'shop_regency'  => $row['shop_regency'] ?? null,
            ];
        }

        // One query for the whole cart rather than one per line.
        $covers = $this->images->coversFor(array_column($lines, 'product_id'));

        foreach ($lines as $index => $line) {
            $lines[$index]['cover_path'] = $covers[(int) $line['product_id']] ?? null;
        }

        return $lines;
    }
}
