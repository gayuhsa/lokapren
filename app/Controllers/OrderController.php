<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\OrderFlow;

/**
 * Order history and the order detail a customer lands on after checkout.
 *
 * Reads go through OrderFlow, which only ever returns an order that belongs to
 * the signed-in customer, so a guessed id yields a 404 rather than someone
 * else's receipt.
 */
class OrderController extends BaseController
{
    public function index(): string
    {
        $orders = new OrderFlow($this->db);

        return view('orders', [
            'orders'  => $orders->forCustomerList($this->userId()),
            'message' => session()->getFlashdata('message'),
        ]);
    }

    public function show(int $orderId): string
    {
        $order = (new OrderFlow($this->db))->forCustomer($orderId, $this->userId());

        return view('order', [
            'order'   => $order,
            'message' => session()->getFlashdata('message'),
        ]);
    }

    private function userId(): int
    {
        return (int) auth()->id();
    }
}