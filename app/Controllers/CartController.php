<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Cart;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Cart endpoints. Every one of them requires a signed-in customer, and every
 * cart operation is scoped to that customer's own cart row.
 */
class CartController extends BaseController
{
    public function index(): string
    {
        $cart = new Cart($this->db);

        return view('cart', [
            'cart'    => $cart->forUser($this->userId()),
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function add(): RedirectResponse
    {
        $cart   = new Cart($this->db);
        $userId = $this->userId();

        try {
            $cart->add(
                $userId,
                (int) $this->request->getPost('variant_id'),
                max(1, (int) $this->request->getPost('qty')),
            );

            return redirect()->to('checkout')->with('message', 'Karya ditambahkan ke keranjang.');
        } catch (RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(int $lineId): RedirectResponse
    {
        $cart = new Cart($this->db);

        try {
            $cart->setQty($this->userId(), $lineId, (int) $this->request->getPost('qty'));

            return redirect()->to('cart')->with('message', 'Jumlah keranjang diperbarui.');
        } catch (RuntimeException $e) {
            return redirect()->to('cart')->with('error', $e->getMessage());
        }
    }

    public function remove(int $lineId): RedirectResponse
    {
        $cart = new Cart($this->db);

        try {
            $cart->remove($this->userId(), $lineId);

            return redirect()->to('cart')->with('message', 'Karya dihapus dari keranjang.');
        } catch (RuntimeException $e) {
            return redirect()->to('cart')->with('error', $e->getMessage());
        }
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}