<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrderModel;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Exceptions\RuntimeException;

/**
 * Reads orders back and moves them through the documented status flow.
 *
 * An order is only ever read through the customer who placed it, or through the
 * seller whose store holds one of its lines, so neither side can reach the
 * other's orders by guessing an id.
 */
final class OrderFlow
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    public function forCustomer(int $orderId, int $customerId): array
    {
        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->get()
            ->getRowArray();

        if ($order === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->decorate($order);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forCustomerList(int $customerId): array
    {
        $rows = $this->db->table('orders')
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->decorate($row, false), $rows);
    }

    /**
     * Only lines belonging to the caller's store are attached, so a seller
     * never sees another seller's products or payout share.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function forStore(array $store, int $orderId): array
    {
        $order = $this->db->table('orders')->where('id', $orderId)->get()->getRowArray();

        if ($order === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ownsLine = $this->db->table('order_items')
            ->where('order_id', $orderId)
            ->where('store_id', (int) $store['id'])
            ->countAllResults() > 0;

        if (! $ownsLine) {
            throw PageNotFoundException::forPageNotFound();
        }

        $decorated = $this->decorate($order);

        $decorated['items'] = array_values(array_filter(
            $decorated['items'],
            static fn (array $item): bool => (int) $item['store_id'] === (int) $store['id'],
        ));

        return $decorated;
    }

    /**
     * @throws RuntimeException
     */
    public function advance(array $order, string $next): array
    {
        $model = new OrderModel();
        $from  = (string) $order['status'];

        // The transition map lives on OrderModel; this service only applies it.
        if (! $model->canTransitionTo($from, $next)) {
            throw new RuntimeException('Status pesanan tidak bisa berubah dari ' . $from . ' ke ' . $next . '.');
        }

        $now = date('Y-m-d H:i:s');

        $data = ['status' => $next, 'updated_at' => $now];

        if ($next === 'completed') {
            $data['completed_at'] = $now;
        }

        if ($next === 'cancelled') {
            $data['cancelled_at'] = $now;
        }

        $this->db->table('orders')->where('id', (int) $order['id'])->update($data);

        return array_merge($order, $data);
    }

    /**
     * Restores stock for every cancelled line, so a cancelled order does not
     * quietly delete a work from the catalogue.
     */
    public function restock(array $order): void
    {
        foreach ($this->lines((int) $order['id']) as $line) {
            $this->db->table('product_variants')
                ->set('stock', 'stock + ' . (int) $line['qty'], false)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->where('id', (int) $line['product_variant_id'])
                ->update();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lines(int $orderId): array
    {
        return $this->db->table('order_items')
            ->where('order_id', $orderId)
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, mixed> $order
     *
     * @return array<string, mixed>
     */
    private function decorate(array $order, bool $withDetail = true): array
    {
        $orderId = (int) $order['id'];

        $order['items'] = $this->lines($orderId);

        if (! $withDetail) {
            return $order;
        }

        $order['delivery'] = $this->db->table('order_deliveries')
            ->where('order_id', $orderId)
            ->get()
            ->getRowArray();

        $order['payment'] = $this->db->table('order_payments p')
            ->select('p.*, m.name AS method_name, m.instructions AS method_instructions')
            ->join('payment_methods m', 'm.id = p.payment_method_id')
            ->where('p.order_id', $orderId)
            ->orderBy('p.id', 'DESC')
            ->get(1)
            ->getRowArray();

        $order['events'] = $this->db->table('delivery_events de')
            ->select('de.status, de.note, de.created_at')
            ->join('order_deliveries od', 'od.id = de.delivery_id')
            ->where('od.order_id', $orderId)
            ->orderBy('de.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $order;
    }
}