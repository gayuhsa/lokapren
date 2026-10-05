<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CartItemModel;
use App\Models\ProductVariantModel;

/**
 * The customer's cart.
 *
 * Every action reads the owner from Shield and passes only an item id or a
 * product id from the request. Quantities are clamped and prices are re-read
 * from the product rows by `CartService`, so a tampered `price` in the form has
 * no effect anywhere.
 */
class CartController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();

        return view('cart/index', [
            'title'      => 'Keranjang Belanja',
            'cart'       => $this->cartService->viewFor($userId),
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Add a product (optionally a specific variant) to the cart.
     *
     * The route carries the product id; the variant id is optional and is
     * validated against that product by the service.
     */
    public function add(int $productId)
    {
        $userId = $this->requireUserId();

        $quantity = (int) ($this->request->getPost('quantity') ?: 1);
        $note     = trim((string) $this->request->getPost('note'));

        $result = $this->cartService->add(
            $userId,
            $productId,
            $this->nullableVariantId(),
            $quantity,
            $note === '' ? null : $note,
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()
            ->route('cart')
            ->with('success', $result['message']);
    }

    /**
     * Update quantities for several lines at once from the cart form.
     *
     * Only `cart_item_id => quantity` pairs are read; any other posted key is
     * ignored, so the form cannot be used to set a price or a seller id.
     */
    public function update()
    {
        $userId = $this->requireUserId();

        $quantities = [];

        foreach ((array) $this->request->getPost('quantities') as $itemId => $quantity) {
            if (! is_numeric($itemId)) {
                continue;
            }

            $quantities[(int) $itemId] = (int) $quantity;
        }

        $updated = $this->cartService->applyQuantities($userId, $quantities);

        return redirect()
            ->route('cart')
            ->with('success', $updated === 0
                ? 'Jumlah keranjang tidak berubah.'
                : 'Jumlah keranjang diperbarui.');
    }

    public function remove(int $itemId)
    {
        $userId = $this->requireUserId();

        if (! $this->cartService->remove($userId, $itemId)) {
            return redirect()->back()->with('error', 'Item keranjang tidak ditemukan.');
        }

        return redirect()->route('cart')->with('success', 'Item dihapus dari keranjang.');
    }

    public function clear()
    {
        $this->cartService->clear($this->requireUserId());

        return redirect()->route('cart')->with('success', 'Keranjang dikosongkan.');
    }

    /**
     * A variant id from the form, or null when the product has no variants.
     */
    private function nullableVariantId(): ?int
    {
        $variantId = $this->request->getPost('variant_id');

        if ($variantId === null || $variantId === '' || ! is_numeric($variantId)) {
            return null;
        }

        return (int) $variantId;
    }
}
