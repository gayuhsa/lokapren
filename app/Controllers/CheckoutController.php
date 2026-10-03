<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Cart;
use App\Services\Checkout;
use App\Services\Shipping;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * The checkout page and the place-order endpoint.
 *
 * This controller reads no amount from the request. It sends a variant id, a
 * shipping code, a payment code and an address; prices, fees, discounts and the
 * final total are all recomputed by Checkout from the database.
 */
class CheckoutController extends BaseController
{
    public function index()
    {
        $quote = $this->quote();

        if ($quote['cart']['items'] === []) {
            return redirect()->to('cart');
        }

        return view('checkout', [
            'quote'       => $quote,
            'payments'    => $this->paymentMethods(),
            'message'     => session()->getFlashdata('message'),
            'error'       => session()->getFlashdata('error'),
            'old'         => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function place(): RedirectResponse
    {
        $checkout = new Checkout($this->db, new Cart($this->db), new Shipping($this->db));
        $request  = $this->request;

        $address = [
            'recipient_name'  => (string) $request->getPost('recipient_name'),
            'recipient_phone' => (string) $request->getPost('recipient_phone'),
            'address_line'    => (string) $request->getPost('address_line'),
            'subdistrict'     => (string) $request->getPost('subdistrict'),
            'city'            => (string) $request->getPost('city'),
            'postal_code'     => (string) $request->getPost('postal_code'),
        ];

        try {
            $order = $checkout->place(
                $this->userId(),
                $address,
                (string) $request->getPost('shipping_code'),
                (string) $request->getPost('payment_code'),
                (string) $request->getPost('courier_note'),
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->to('checkout')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->to('order/' . (int) $order['id'])
            ->with('message', 'Pesanan ' . $order['order_code'] . ' dibuat. Selesaikan pembayaran untuk mengunci pesanan.');
    }

    /**
     * Applies or clears the promo code held on the customer's own cart.
     *
     * This is POST-only because a code changes money: the request has to carry
     * the CSRF token the checkout page rendered, so a promo cannot be attached
     * to a cart by a link someone else shares.
     */
    public function promo(): RedirectResponse
    {
        $cart   = new Cart($this->db);
        $userId = $this->userId();
        $input  = (string) $this->request->getPost('code');

        if ($input === '' || strtolower($input) === 'hapus') {
            $cart->applyPromo($userId, null);

            return redirect()->to('checkout')->with('message', 'Kode promo dilepas.');
        }

        try {
            $promo = (new Shipping($this->db))->promo($input, $cart->forUser($userId)['subtotal']);

            $cart->applyPromo($userId, $promo['code']);

            return redirect()->to('checkout')->with('message', $promo['label'] . ' diterapkan.');
        } catch (RuntimeException $e) {
            return redirect()->to('checkout')->with('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function quote(): array
    {
        return (new Checkout($this->db, new Cart($this->db), new Shipping($this->db)))->quote($this->userId());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentMethods(): array
    {
        return $this->db->table('payment_methods')
            ->where('is_active', true)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}