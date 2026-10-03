<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\Shield\Entities\User;

/**
 * In-app messaging between a customer and one sanggar.
 *
 * Every method takes the authenticated user and derives the caller's side from
 * the data: a user is the seller in a conversation only when they own the
 * store it belongs to. Nothing here accepts a store_id, customer_id or
 * sender_role from a request, so a customer cannot post as a seller and one
 * customer cannot read another's thread.
 *
 * `sender_role` is a denormalised column that keeps a thread readable without
 * a join back to stores, so it is written from the server-derived side and is
 * never accepted as input.
 */
final class Chat
{
    private const ROLE_CUSTOMER = 'customer';
    private const ROLE_SELLER   = 'seller';

    private const TYPE_TEXT    = 'text';
    private const TYPE_PRODUCT = 'product';
    private const TYPE_ORDER   = 'order';

    private const BODY_MAX       = 2000;
    private const PREVIEW_MAX    = 255;
    private const PAGE_SIZE      = 40;
    private const THREAD_PAGE    = 60;

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly StoreAccess $access = new StoreAccess(),
    ) {
    }

    /**
     * Threads the caller is a participant in, newest activity first.
     *
     * A seller sees their own store's threads and a customer sees their own.
     * There is no flag to widen this, because there is no legitimate case for
     * a third party reading a private customer/seller thread.
     *
     * @return list<array<string, mixed>>
     */
    public function inbox(User $user): array
    {
        return $this->scope($user)->get()->getResultArray();
    }

    /**
     * Total unread across every thread the caller is in.
     */
    public function unreadTotal(User $user): int
    {
        $storeId = $this->access->idForUser($user);

        $column = $storeId === null ? 'customer_unread_count' : 'store_unread_count';

        // The per-conversation counters are the badge's single source of truth;
        // is_read on individual messages is kept in step by markRead().
        $query = $this->db->table('conversations')->selectSum($column, 'unread');

        if ($storeId === null) {
            $query->where('customer_id', (int) $user->id);
        } else {
            $query->where('store_id', $storeId);
        }

        return (int) ($query->get()->getRowArray()[$column] ?? 0);
    }

    /**
     * One thread plus its messages, or a 404 when the caller is not in it.
     *
     * Opening a thread marks the caller's own unread messages read, which is
     * what drives the badge in the inbox list.
     *
     * @return array<string, mixed>
     */
    public function thread(User $user, int $conversationId): array
    {
        $conversation = $this->scope($user)
            ->where('conversations.id', $conversationId)
            ->get()
            ->getRowArray();

        if ($conversation === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->markRead($user, $conversation);

        $messages = $this->db->table('messages')
            ->select('messages.*, users.username AS sender_username')
            ->join('users', 'users.id = messages.sender_id', 'left')
            ->where('messages.conversation_id', $conversationId)
            ->orderBy('messages.created_at', 'ASC')
            ->orderBy('messages.id', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'conversation' => $conversation,
            'messages'     => $this->decorate($messages),
            'orders'       => $this->relatedOrders($conversation),
            'quickReplies' => self::quickReplies(),
        ];
    }

    /**
     * Starts the thread between the caller and a sanggar, or returns the
     * existing one. The unique (store_id, customer_id) key makes this safe to
     * call from the product page on every visit.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException When the store cannot be chatted with.
     */
    public function openWithStore(User $user, int $storeId): array
    {
        $store = $this->assertChatableStore($user, $storeId);

        $existing = $this->db->table('conversations')
            ->where('store_id', $storeId)
            ->where('customer_id', (int) $user->id)
            ->get()
            ->getRowArray();

        if ($existing !== null) {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('conversations')->insert([
            'store_id'     => (int) $store['id'],
            'customer_id'  => (int) $user->id,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return $this->db->table('conversations')
            ->where('id', $this->db->insertID())
            ->get()
            ->getRowArray();
    }

    /**
     * Posts a message.
     *
     * The conversation is re-resolved through the caller, so guessing an id in
     * the form cannot post into somebody else's thread. The store on an
     * attached product and the store on an attached order are also checked
     * against the thread, so a card from a third sanggar cannot be injected.
     *
     * @throws RuntimeException
     */
    public function send(User $user, int $conversationId, string $body, ?int $productId = null, ?int $orderId = null): int
    {
        $conversation = $this->scope($user)
            ->where('conversations.id', $conversationId)
            ->get()
            ->getRowArray();

        if ($conversation === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $role    = $this->roleFor($user);
        $body    = trim($body);
        $type    = $this->messageType($body, $productId, $orderId);

        $this->assertAttachment($conversation, $productId, $orderId);

        $now = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            $this->db->table('messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id'       => (int) $user->id,
                'sender_role'     => $role,
                'message_type'    => $type,
                'body'            => $body !== '' ? mb_substr($body, 0, self::BODY_MAX) : null,
                'product_id'      => $productId,
                'order_id'        => $orderId,
                // is_read describes the *recipient*, so a fresh message starts
                // unread and the other side flips it in markRead().
                'is_read'         => false,
                'read_at'         => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $messageId = (int) $this->db->insertID();

            // The sender has by definition read their own message, so only the
            // other side's counter moves.
            $builder = $this->db->table('conversations')->where('id', $conversationId);
            if ($role === self::ROLE_CUSTOMER) {
                $builder->set('store_unread_count', 'store_unread_count + 1', false)
                    ->set('last_message_at', $now)
                    ->set('last_message_preview', $this->preview($type, $productId, $orderId, $body))
                    ->set('updated_at', $now)
                    ->update();
            } else {
                $builder->set('customer_unread_count', 'customer_unread_count + 1', false)
                    ->set('last_message_at', $now)
                    ->set('last_message_preview', $this->preview($type, $productId, $orderId, $body))
                    ->set('updated_at', $now)
                    ->update();
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();

            throw new RuntimeException('Pesan gagal terkirim, silakan coba lagi.', 0, $e);
        }

        return $messageId;
    }

    /**
     * The caller's side of a conversation, derived rather than submitted.
     */
    public function roleFor(User $user): string
    {
        return $this->access->idForUser($user) === null
            ? self::ROLE_CUSTOMER
            : self::ROLE_SELLER;
    }

    /**
     * The unread badge count for the caller's own side.
     */
    public function unreadFor(User $user, int $conversationId): int
    {
        $column = $this->roleFor($user) === self::ROLE_SELLER
            ? 'store_unread_count'
            : 'customer_unread_count';

        $row = $this->scope($user)
            ->select("c.{$column} AS unread")
            ->where('conversations.id', $conversationId)
            ->get()
            ->getRowArray();

        return (int) ($row['unread'] ?? 0);
    }

    /**
     * Suggested openers the customer can send as-is.
     *
     * These are static copy rather than stored rows: they are the same for
     * every sanggar, so a table would only be a second place to keep text in
     * sync with the design.
     *
     * @return list<string>
     */
    public static function quickReplies(): array
    {
        return [
            'Halo, apakah karya ini masih tersedia?',
            'Boleh minta foto detail dari proses pahatnya?',
            'Berapa lama perkiraan pengerjaannya?',
            'Apakah bisa dikirim antar terima?',
        ];
    }

    /**
     * The conversation list restricted to the caller's own threads.
     *
     * @return \CodeIgniter\Database\BaseBuilder
     */
    private function scope(User $user)
    {
        $query = $this->db->table('conversations')
            ->select('conversations.id, conversations.store_id, conversations.customer_id, conversations.linked_order_id, conversations.last_message_at, conversations.last_message_preview, conversations.customer_unread_count, conversations.store_unread_count, conversations.created_at, conversations.updated_at, stores.name AS store_name, stores.slug AS store_slug, stores.logo_url AS store_logo, stores.verification_status, users.username AS customer_username')
            ->join('stores', 'stores.id = conversations.store_id')
            ->join('users', 'users.id = conversations.customer_id');

        $storeId = $this->access->idForUser($user);

        if ($storeId !== null) {
            return $query->where('conversations.store_id', $storeId);
        }

        return $query->where('conversations.customer_id', (int) $user->id);
    }

    /**
     * @param array<string, mixed> $conversation
     *
     * @throws RuntimeException
     */
    private function markRead(User $user, array $conversation): void
    {
        $role = $this->roleFor($user);
        $now  = date('Y-m-d H:i:s');

        // Only messages the other side sent are marked read here.
        $this->db->table('messages')
            ->where('conversation_id', (int) $conversation['id'])
            ->where('is_read', false)
            ->where('sender_role', $role === self::ROLE_SELLER ? self::ROLE_CUSTOMER : self::ROLE_SELLER)
            ->update(['is_read' => true, 'read_at' => $now]);

        $column = $role === self::ROLE_SELLER ? 'store_unread_count' : 'customer_unread_count';

        $this->db->table('conversations')
            ->where('id', (int) $conversation['id'])
            ->update([$column => 0]);
    }

    /**
     * An attachment has to belong to the sanggar the thread is with, otherwise
     * a customer could show another seller's product or order inside a thread.
     *
     * @param array<string, mixed> $conversation
     *
     * @throws RuntimeException
     */
    private function assertAttachment(array $conversation, ?int $productId, ?int $orderId): void
    {
        $storeId = (int) $conversation['store_id'];

        if ($productId !== null) {
            $owner = $this->db->table('products')
                ->select('store_id')
                ->where('id', $productId)
                ->get()
                ->getRowArray();

            if ($owner === null || (int) $owner['store_id'] !== $storeId) {
                throw new RuntimeException('Karya itu bukan dari sanggar ini.');
            }
        }

        if ($orderId !== null) {
            $involvesStore = $this->db->table('order_items oi')
                ->join('orders o', 'o.id = oi.order_id')
                ->where('oi.order_id', $orderId)
                ->where('oi.store_id', $storeId)
                ->where('o.customer_id', (int) $conversation['customer_id'])
                ->countAllResults() > 0;

            if (! $involvesStore) {
                throw new RuntimeException('Pesanan itu bukan dari sanggar ini.');
            }
        }
    }

    /**
     * Orders this customer placed with this store, newest first. Derived from
     * the order lines rather than from the single `linked_order_id` column,
     * because one cart can span several sanggar and therefore one order can
     * hold lines from several of them.
     *
     * @param array<string, mixed> $conversation
     *
     * @return list<array<string, mixed>>
     */
    private function relatedOrders(array $conversation): array
    {
        return $this->db->table('orders o')
            ->select('o.id, o.order_code, o.status, o.total, o.created_at, COUNT(oi.id) AS line_count')
            ->join('order_items oi', 'oi.order_id = o.id')
            ->where('oi.store_id', (int) $conversation['store_id'])
            ->where('o.customer_id', (int) $conversation['customer_id'])
            ->groupBy('o.id, o.order_code, o.status, o.total, o.created_at')
            ->orderBy('o.created_at', 'DESC')
            ->limit(self::PAGE_SIZE)
            ->get()
            ->getResultArray();
    }

    /**
     * Resolves an attached craft card so the thread can render it without a
     * query per message.
     *
     * @param list<array<string, mixed>> $messages
     *
     * @return list<array<string, mixed>>
     */
    private function decorate(array $messages): array
    {
        $productIds = array_values(array_filter(array_map(
            static fn (array $m): ?int => $m['product_id'] === null ? null : (int) $m['product_id'],
            $messages,
        )));

        if ($productIds === []) {
            return $messages;
        }

        $products = $this->db->table('products')
            ->select('id, name, slug, store_id')
            ->whereIn('id', $productIds)
            ->get()
            ->getResultArray();

        $byId = [];

        foreach ($products as $product) {
            $byId[(int) $product['id']] = $product;
        }

        foreach ($messages as $i => $message) {
            $messages[$i]['product'] = null;
            if ($message['product_id'] !== null) {
                $messages[$i]['product'] = $byId[(int) $message['product_id']] ?? null;
            }
        }

        return $messages;
    }

    private function messageType(string $body, ?int $productId, ?int $orderId): string
    {
        if ($productId !== null) {
            return self::TYPE_PRODUCT;
        }

        if ($orderId !== null) {
            return self::TYPE_ORDER;
        }

        if ($body === '') {
            throw new RuntimeException('Pesan tidak boleh kosong.');
        }

        return self::TYPE_TEXT;
    }

    /**
     * @throws RuntimeException
     */
    private function assertChatableStore(User $user, int $storeId): array
    {
        $store = $this->db->table('stores')->where('id', $storeId)->get()->getRowArray();

        if ($store === null) {
            throw new RuntimeException('Sanggar tidak ditemukan.');
        }

        // A sanggar does not appear in the marketplace until it is verified and
        // open, so an unverified one cannot be opened from the catalogue.
        if (! (bool) $store['is_active'] || $store['verification_status'] !== 'verified') {
            throw new RuntimeException('Sanggar ini belum tersedia di Lokapren.');
        }

        return $store;
    }

    private function preview(string $type, ?int $productId, ?int $orderId, string $body): string
    {
        return match ($type) {
            self::TYPE_PRODUCT => 'Mengirim kartu karya',
            self::TYPE_ORDER   => 'Mengirim kartu pesanan',
            default            => mb_substr($body, 0, self::PREVIEW_MAX),
        };
    }
}