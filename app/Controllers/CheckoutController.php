<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CheckoutService;

/**
 * Checkout: one review step, then a single submit.
 *
 * The cart may span several sanggar. `CheckoutService::place()` splits it into
 * one order per seller inside one transaction, so the customer sees one
 * confirmation while each seller manages only their own order.
 *
 * Nothing financial is read from the request. The shipping option is a code that
 * the service resolves against its own table; the address is validated for
 * ownership; and every total comes from a product row.
 */
class CheckoutController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();

        $shippingCode = (string) ($this->request->getGet('opsi') ?: 'antar_terima');
        $summary      = $this->checkoutService->summaryFor($userId, $shippingCode);

        // Products can sell out between the cart page and checkout, so the
        // emptiness check belongs here rather than in the cart view.
        if ($summary['is_empty']) {
            return redirect()->route('cart')->with('error', 'Keranjang masih kosong.');
        }

        return view('checkout/index', [
            'title'            => 'Checkout',
            'summary'          => $summary,
            'addresses'        => $summary['addresses'],
            'shipping_options' => CheckoutService::shippingOptions(),
            'selected'         => (int) ($this->request->getGet('alamat') ?: 0),
            'cart_count'       => $this->cartCount(),
        ]);
    }

    public function store()
    {
        $userId = $this->requireUserId();

        $result = $this->checkoutService->place($userId, $userId, [
            'address_id'      => (int) $this->request->getPost('address_id'),
            'shipping_option' => (string) $this->request->getPost('shipping_option'),
            'customer_note'   => trim((string) $this->request->getPost('customer_note')),
        ]);

        if (! $result['ok']) {
            return redirect()
                ->route('checkout_index')
                ->withInput()
                ->with('error', $result['message']);
        }

        // The ids travel in the session rather than the query string so a
        // customer cannot walk the success page by editing `?orders=1,2`.
        session()->set('checkout_result', [
            'order_ids'     => $result['order_ids'],
            'order_numbers' => $result['order_numbers'],
            'grand_total'   => $result['grand_total'],
        ]);

        return redirect()->route('checkout_success');
    }

    public function success()
    {
        $userId = $this->requireUserId();
        $result = session()->get('checkout_result');

        // Re-read every order through the ownership check: a stale session must
        // not become a way to render somebody else's confirmation.
        if (! is_array($result) || ! isset($result['order_ids']) || ! is_array($result['order_ids'])) {
            return redirect()->route('orders')->with('error', 'Tidak ada pesanan yang baru saja dibuat.');
        }

        $orders = [];

        foreach ($result['order_ids'] as $orderId) {
            $order = $this->orderService->findForUser((int) $orderId, $userId);

            if ($order !== null) {
                $orders[] = $order;
            }
        }

        if ($orders === []) {
            return redirect()->route('orders');
        }

        session()->remove('checkout_result');

        return view('checkout/success', [
            'title'      => 'Pesanan Diterima',
            'orders'     => $orders,
            'grand_total' => array_sum(array_column($orders, 'grand_total')),
            'cart_count' => $this->cartCount(),
        ]);
    }

    }
