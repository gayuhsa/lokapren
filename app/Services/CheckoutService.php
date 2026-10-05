<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AddressModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderShipmentModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\ProductModel;
use App\Models\ReviewModel;
use App\Models\SellerDailyStatModel;
use App\Models\SellerProfileModel;

/**
 * Turns a cart into orders.
 *
 * A cart may hold lines from several sanggar, but `orders` carries a single
 * `seller_id`, because each sanggar prepares, packs and ships its own work.
 * One checkout therefore creates **one order per seller**, all under one
 * combined total the customer sees once. The customer gets N order numbers
 * from a single "Selesaikan Pembelian" click, and each order then moves through
 * the status machine independently.
 *
 * Every figure written here is computed in this class from live product rows.
 * The request supplies only the address id, the shipping option and a free-text
 * note — never a price, a total or a seller id (AGENTS.md: "Never trust
 * client-submitted prices, totals, order status").
 */
class CheckoutService
{
    /**
     * Shipping options offered at checkout.
     *
     * `fee` is in whole rupiah and is added once per order, not once per line,
     * so a customer ordering three works from one sanggar pays one shipping fee.
     * The catalogue matches the "Pilihan Layanan Ekspedisi & Antar" step.
     *
     * @var array<string, array{label: string, description: string, eta: string, courier_code: string, courier_name: string, service_level: string, fee: int}>
     */
    private const SHIPPING_OPTIONS = [
        'antar_terima' => [
            'label'         => 'Pesan Antar Terima',
            'description'   => 'Kurir khas Magelang, ramah lingkungan. Pengantaran langsung dari bilik sanggar perajin.',
            'eta'           => '1-3 hari kerja',
            'courier_code'  => 'LOKAPREN',
            'courier_name'  => 'Kurir Khas Magelang',
            'service_level' => 'Kurir/local',
            'fee'           => 0,
        ],
        'reguler' => [
            'label'         => 'Ekspedisi Reguler',
            'description'   => 'JNE/TIKI Reguler. Estimasi 2-3 hari kerja, bubble wrap standar.',
            'eta'           => '2-3 hari kerja',
            'courier_code'  => 'JNE',
            'courier_name'  => 'JNE Reguler',
            'service_level' => 'Reguler',
            'fee'           => 38000,
        ],
        'kargo' => [
            'label'         => 'Ekspedisi Kargo Kayu & Keramik',
            'description'   => 'Peti kayu khusus untuk pahatan relief batu jati dan gerabah besar.',
            'eta'           => '3-5 hari kerja',
            'courier_code'  => 'JNT',
            'courier_name'  => 'J&T Cargo',
            'service_level' => 'Kargo',
            'fee'           => 65000,
        ],
    ];

    private CartService $cart;
    private OrderModel $orders;
    private OrderItemModel $orderItems;
    private OrderShipmentModel $shipments;
    private OrderStatusHistoryModel $history;
    private ProductModel $products;
    private AddressModel $addresses;
    private SellerProfileModel $sellers;
    private SellerDailyStatModel $stats;

    public function __construct()
    {
        $this->cart        = new CartService();
        $this->orders      = new OrderModel();
        $this->orderItems  = new OrderItemModel();
        $this->shipments   = new OrderShipmentModel();
        $this->history     = new OrderStatusHistoryModel();
        $this->products    = new ProductModel();
        $this->addresses   = new AddressModel();
        $this->sellers     = new SellerProfileModel();
        $this->stats       = new SellerDailyStatModel();
    }

/**
     * @return list<string>
     */
    public static function shippingOptionCodes(): array
    {
        return array_keys(self::SHIPPING_OPTIONS);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function shippingOptions(): array
    {
        return self::SHIPPING_OPTIONS;
    }

    /**
     * Everything the checkout view needs: the repriced lines grouped per
     * sanggar, the shipping options and a combined total.
     *
     * @return array<string, mixed>
     */
    public function summaryFor(int $customerId, string $shippingCode = 'antar_terima'): array
    {
        $repriced = $this->cart->repriceForCheckout($customerId);
        $option   = $this->resolveShippingOption($shippingCode);

        $groups   = [];
        $subtotal = $repriced['subtotal'];
        $shipping = 0;
        $fee      = (int) $option['fee'];

        foreach ($repriced['per_seller'] as $sellerId => $lines) {
            $groups[] = [
                'seller_id'    => (int) $sellerId,
                'lines'        => $lines,
                'subtotal'     => array_sum(array_column($lines, 'subtotal')),
                // One fee per order; free "Antar Terima" means Rp 0 here.
                'shipping_fee' => $fee,
                'order_total'  => array_sum(array_column($lines, 'subtotal')) + $fee,
            ];
        }

        // The customer pays one shipping fee per sanggar, not one per checkout,
        // because each sanggar dispatches from its own workshop.
        $shipping = $fee * count($groups);

        return [
            'groups'          => $groups,
            'addresses'       => $this->addresses->forUser($customerId),
            'shipping_option' => $option,
            'subtotal'        => $subtotal,
            'shipping_total'  => $shipping,
            // No gateway is integrated in the MVP: no tax, service fee or
            // donation column is computed. grand_total is subtotal + shipping.
            'service_total'   => 0,
            'tax_total'       => 0,
            'grand_total'     => $subtotal + $shipping,
            'seller_count'    => count($groups),
            'item_count'      => array_sum(array_column($repriced['lines'], 'quantity')),
            'dropped'         => $repriced['dropped'],
            'is_empty'        => $repriced['lines'] === [],
        ];
    }

    /**
     * Place the order(s).
     *
     * The whole checkout runs in one transaction: if the second sanggar's order
     * cannot be written, the first one is rolled back too, so a customer never
     * ends up with a half-placed multi-seller purchase.
     *
     * @param array<string, mixed> $input `address_id`, `shipping_option`,
     *                                     optional `customer_note`
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     order_ids: list<int>,
     *     order_numbers: list<string>,
     *     grand_total: int,
     * }
     */
    public function place(int $customerId, int $actorId, array $input): array
    {
        $addressId     = (int) ($input['address_id'] ?? 0);
        $shippingCode  = (string) ($input['shipping_option'] ?? 'antar_terima');
        $customerNote  = isset($input['customer_note']) ? (string) $input['customer_note'] : null;

        $address = $this->addresses->findOwnedBy($addressId, $customerId);

        if ($address === null) {
            return $this->fail('Alamat pengiriman tidak ditemukan.');
        }

        $option = $this->resolveShippingOption($shippingCode);

        $repriced = $this->cart->repriceForCheckout($customerId);

        if ($repriced['lines'] === []) {
            return $this->fail('Keranjang kosong.');
        }

        $now  = date('Y-m-d H:i:s');
        $fee  = (int) $option['fee'];

        $this->db()->transStart();

        $orderIds     = [];
        $orderNumbers = [];
        $grandTotal   = 0;

        foreach ($repriced['per_seller'] as $sellerId => $lines) {
            $sellerId  = (int) $sellerId;
            $subtotal  = array_sum(array_column($lines, 'subtotal'));
            $total     = $subtotal + $fee;
            $orderNo   = $this->orders->nextOrderNumber();
            $orderId   = $this->orders->insertRow([
                'order_number'          => $orderNo,
                'customer_id'           => $customerId,
                'seller_id'             => $sellerId,
                'status'                => OrderModel::STATUS_PENDING_PAYMENT,
                'fulfillment_type'      => 'ship',
                // No payment gateway is integrated, so the method is recorded as
                // `manual` — the buyer confirms transfer with the seller out of
                // band. Nothing in the app settles or verifies a payment.
                'payment_method'        => 'manual',
                'payment_status'        => 'unpaid',
                'currency'              => 'IDR',
                'subtotal'              => $subtotal,
                'shipping_total'        => $fee,
                'service_total'         => 0,
                'tax_total'             => 0,
                'donation_total'        => 0,
                'grand_total'           => $total,
                // No payment gateway is integrated, so there is no
                // intermediary fee to deduct yet; the seller earns the subtotal.
                'platform_fee'          => 0,
                'seller_earning'        => $subtotal,
                'ship_recipient_name'   => $address['recipient_name'],
                'ship_recipient_phone'  => $address['recipient_phone'],
                'ship_address_line'     => $address['address_line'],
                'ship_village'          => $address['village'],
                'ship_district'         => $address['district'],
                'ship_regency'          => $address['regency'],
                'ship_province'         => $address['province'],
                'ship_postal_code'      => $address['postal_code'],
                'ship_latitude'         => $address['latitude'],
                'ship_longitude'        => $address['longitude'],
                'ship_landmark'         => $address['landmark'],
                'ship_notes'            => $address['delivery_notes'],
                'courier_code'          => $option['courier_code'],
                'courier_name'          => $option['courier_name'],
                'estimated_delivery_at' => date('Y-m-d', strtotime('+5 days')),
                'customer_note'         => $customerNote,
                'placed_at'             => $now,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            foreach ($lines as $line) {
                $this->orderItems->insertRow([
                    'order_id'      => $orderId,
                    'product_id'    => $line['product_id'],
                    'variant_id'    => $line['variant_id'],
                    // Snapshot: a later product rename or price change must not
                    // rewrite a historical invoice.
                    'product_name'  => $line['product_name'],
                    'variant_label' => $line['variant_label'],
                    'sku'           => $line['sku'],
                    // The photo comes from the repriced cart line, so the order
                    // keeps a picture of what was actually bought even if the
                    // seller changes the gallery afterwards.
                    'image_path'    => $line['cover_path'] ?? null,
                    'unit_price'    => $line['unit_price'],
                    'quantity'      => $line['quantity'],
                    'subtotal'      => $line['subtotal'],
                    'note'          => $line['note'],
                    'created_at'    => $now,
                ]);

                $this->decrementStock($line, $now);
                $this->bumpSoldCount($line);
            }

            $this->shipments->insertRow([
                'order_id'       => $orderId,
                'courier_code'   => $option['courier_code'],
                'courier_name'   => $option['courier_name'],
                'service_level'  => $option['service_level'],
                'created_at'     => $now,
            ]);

            // Audit trail starts at the order's initial state.
            $this->history->insertRow([
                'order_id'   => $orderId,
                'from_status' => null,
                'to_status'  => OrderModel::STATUS_PENDING_PAYMENT,
                'note'       => 'Pesanan dibuat oleh pembeli.',
                'actor_id'   => $actorId,
                'created_at' => $now,
            ]);

            $orderIds[]     = $orderId;
            $orderNumbers[] = $orderNo;
            $grandTotal     += $total;

            // Only the order count is recorded here. Revenue is deliberately
            // left until the seller actually ships, so a cancelled or
            // unpaid order never inflates a sanggar's daily omzet.
            $this->stats->increment($sellerId, $this->stats->today(), ['order_count' => 1]);
        }

        if ($this->db()->transStatus() === false) {
            $this->db()->transRollback();

            return $this->fail('Pesanan gagal disimpan. Silakan coba lagi.');
        }

        $this->db()->transCommit();

        $this->cart->markConverted($customerId);

        return [
            'ok'            => true,
            'message'       => count($orderNumbers) > 1
                ? count($orderNumbers) . ' pesanan berhasil dibuat untuk ' . count($orderNumbers) . ' sanggar.'
                : 'Pesanan berhasil dibuat.',
            'order_ids'     => $orderIds,
            'order_numbers' => $orderNumbers,
            'grand_total'   => $grandTotal,
        ];
    }

    /**
     * Reduce stock for a line, honouring made-to-order products.
     */
    private function decrementStock(array $line, string $now): void
    {
        $product = $this->products->find($line['product_id']);

        if ($product === null || $product['made_to_order']) {
            return;
        }

        if ($line['variant_id'] !== null) {
            $this->db()->table('product_variants')
                ->set('stock', 'stock - ' . (int) $line['quantity'], false)
                ->set('updated_at', $now)
                ->where('id', $line['variant_id'])
                ->update();

            // Variant stock is authoritative when the product tracks variants.
            return;
        }

        $this->db()->table('products')
            ->set('stock', 'stock - ' . (int) $line['quantity'], false)
            ->set('updated_at', $now)
            ->where('id', $line['product_id'])
            ->update();
    }

    /**
     * Keep the denormalised sold counter moving in step with the order rows.
     */
    private function bumpSoldCount(array $line): void
    {
        $this->db()->table('products')
            ->set('sold_count', 'sold_count + ' . (int) $line['quantity'], false)
            ->where('id', $line['product_id'])
            ->update();
    }

/**
 * Resolve a submitted option code against the service's own table.
 *
 * The matched code travels back with the option so the checkout form can mark
 * the selected radio without echoing whatever the query string claimed. An
 * unknown code falls back to the default option rather than being rejected, so
 * a tampered form still produces a valid, priced order.
 *
 * @return array<string, mixed>
 */
private function resolveShippingOption(string $code): array
{
    $code = array_key_exists($code, self::SHIPPING_OPTIONS) ? $code : 'antar_terima';

    return self::SHIPPING_OPTIONS[$code] + ['code' => $code];
}

    /**
     * @return array{ok: bool, message: string, order_ids: list<int>, order_numbers: list<string>, grand_total: int}
     */
    private function fail(string $message): array
    {
        return [
            'ok'            => false,
            'message'       => $message,
            'order_ids'     => [],
            'order_numbers' => [],
            'grand_total'   => 0,
        ];
    }

    private function db(): \CodeIgniter\Database\ConnectionInterface
    {
        return db_connect();
    }
}
