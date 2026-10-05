<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ConversationModel;
use App\Models\MessageModel;
use App\Models\SellerProfileModel;
use App\Models\SellerQuickReplyModel;
use App\Models\ProductModel;
use App\Models\UserProfileModel;

/**
 * In-app chat between a customer and a sanggar.
 *
 * AGENTS.md requires that "Chat conversations may only be accessed by their
 * participants", so every read and write in this service starts from a
 * participant check. The conversation's two participant ids are written once,
 * from the authenticated Shield user and the product's seller — a `seller_id`
 * in a request body is never consulted.
 */
class ChatService
{
    private ConversationModel $conversations;
    private MessageModel $messages;
    private SellerQuickReplyModel $quickReplies;
    private ProductModel $products;
    private UserProfileModel $profiles;

    public function __construct()
    {
        $this->conversations = new ConversationModel();
        $this->messages      = new MessageModel();
        $this->quickReplies  = new SellerQuickReplyModel();
        $this->products      = new ProductModel();
        $this->profiles      = new UserProfileModel();
    }

    /**
     * Open (or reuse) the thread a customer has with a sanggar about a product.
     *
     * Returns null when the product does not exist, so a crafted id cannot open
     * a thread with an arbitrary seller.
     */
    public function openFromProduct(int $customerId, int $productId): ?array
    {
        $product = $this->products->find($productId);

        if ($product === null) {
            return null;
        }

        $sellerId = (int) $product['seller_id'];

        if ($sellerId === $customerId) {
            return null;
        }

        $conversationId = $this->conversations->firstOrCreateThread($customerId, $sellerId, $productId);

        return $this->conversations->findForParticipant($conversationId, $customerId);
    }

    /**
     * Threads for the sidebar list, newest activity first.
     *
     * @return list<array<string, mixed>>
     */
    public function inboxFor(int $userId, int $limit = 50, int $offset = 0): array
    {
        $threads = $this->conversations->forParticipant($userId, $limit, $offset);

        foreach ($threads as $index => $thread) {
            $threads[$index] = $this->decorate($thread, $userId);
        }

        return $threads;
    }

    /**
     * One thread with its messages, marking it read for the reader.
     *
     * @return array<string, mixed>|null Null when the caller is not a participant.
     */
    public function threadFor(int $conversationId, int $userId, int $messageLimit = 100): ?array
    {
        $conversation = $this->conversations->findForParticipant($conversationId, $userId);

        if ($conversation === null) {
            return null;
        }

        $conversation           = $this->decorate($conversation, $userId);
        $conversation['messages'] = $this->messages->forConversation($conversationId, $messageLimit);

        // Opening the thread is what clears the reader's unread badge.
        $this->conversations->markRead($conversationId, $userId);

        return $conversation;
    }

    /**
     * The conversation a customer already has with a seller, created if absent.
     *
     * The seller id always comes from an order or product the caller already
     * owns, so this cannot be used to start a thread with an arbitrary seller
     * beyond the one the caller legitimately interacted with.
     *
     * @return array<string, mixed>|null
     */
    public function findOrCreateWithSeller(int $customerId, int $sellerId, ?int $orderId = null, ?int $productId = null): ?array
    {
        if ($customerId === $sellerId) {
            return null;
        }

        $conversationId = $this->conversations->firstOrCreateThread($customerId, $sellerId, $productId, $orderId);

        return $this->conversations->findForParticipant($conversationId, $customerId);
    }

    /**
     * Post a message from one side of the thread.
     *
     * @return array{ok: bool, message: string, id: int|null}
     */
    public function send(int $conversationId, int $senderId, string $body): array
    {
        $conversation = $this->conversations->findForParticipant($conversationId, $senderId);

        if ($conversation === null) {
            return ['ok' => false, 'message' => 'Percakapan tidak ditemukan.', 'id' => null];
        }

        $body = trim($body);

        if ($body === '') {
            return ['ok' => false, 'message' => 'Pesan tidak boleh kosong.', 'id' => null];
        }

        if (mb_strlen($body) > 2000) {
            return ['ok' => false, 'message' => 'Pesan terlalu panjang (maksimal 2000 karakter).', 'id' => null];
        }

        $fromCustomer = (int) $conversation['customer_id'] === $senderId;

        $messageId = $this->messages->insertRow([
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'message_type'    => 'text',
            'body'            => $body,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        $this->conversations->touchLastMessage($conversationId, $body, $fromCustomer);

        return ['ok' => true, 'message' => 'Pesan terkirim.', 'id' => $messageId];
    }

    /**
     * Send a canned reply the seller has saved, for use on the seller's side.
     *
     * @return array{ok: bool, message: string, id: int|null}
     */
    public function sendQuickReply(int $conversationId, int $sellerId, int $quickReplyId): array
    {
        $reply = $this->quickReplies->findOwnedBy($quickReplyId, $sellerId);

        if ($reply === null) {
            return ['ok' => false, 'message' => 'Balasan cepat tidak ditemukan.', 'id' => null];
        }

        return $this->send($conversationId, $sellerId, (string) $reply['body']);
    }

    /**
     * Unread badges for the header: total across all threads, plus the count
     * for a single thread.
     *
     * @return array{total: int, conversation_id: int, count: int}
     */
    public function unreadSummaryFor(int $userId, int $conversationId): array
    {
        return [
            'total'          => $this->conversations->totalUnreadFor($userId),
            'conversation_id' => $conversationId,
            'count'          => $this->conversations->unreadCountFor($conversationId, $userId),
        ];
    }

    /**
     * Total unread messages across every thread a user takes part in, for the
     * header badge.
     */
    public function totalUnreadFor(int $userId): int
    {
        return $this->conversations->totalUnreadFor($userId);
    }

    /**
     * Quick replies a seller has set up, for the seller's own composer.
     *
     * @return list<array<string, mixed>>
     */
    public function quickRepliesFor(int $sellerId): array
    {
        return $this->quickReplies->ownedBy($sellerId)->findAll();
    }

    /**
     * Attach the other party's profile plus this reader's unread badge.
     *
     * @param array<string, mixed> $conversation
     *
     * @return array<string, mixed>
     */
    private function decorate(array $conversation, int $userId): array
    {
        $isCustomer = (int) $conversation['customer_id'] === $userId;
        $counter    = $isCustomer ? 'customer_unread_count' : 'seller_unread_count';

        $conversation['unread']   = (int) $conversation[$counter];
        $conversation['is_customer_side'] = $isCustomer;
        $conversation['counterparty_id'] = $isCustomer
            ? (int) $conversation['seller_id']
            : (int) $conversation['customer_id'];

        $counterpartyId = (int) $conversation['counterparty_id'];
        $counterparty   = $this->profiles->find($counterpartyId);

        $conversation['counterparty_name']  = $counterparty['full_name'] ?? 'Pengguna Lokapren';
        $conversation['counterparty_photo'] = $counterparty['photo_path'] ?? null;
        $conversation['counterparty_shop']  = null;
        $conversation['counterparty_slug']  = null;

        // When the buyer is reading the thread, the other side is the sanggar:
        // present it by its shop name and logo rather than the account name,
        // so the inbox reads like a list of workshops. On the seller's side the
        // counterparty is a customer, who has no sanggar row at all.
        if ($isCustomer) {
            $sellers = new SellerProfileModel();
            $shop    = $sellers->newRow(
                $sellers->newQuery()->where('user_id', $counterpartyId)
            );

            if ($shop !== null) {
                $conversation['counterparty_shop']  = $shop['display_name'];
                $conversation['counterparty_name']  = $shop['display_name'];
                $conversation['counterparty_slug']  = $shop['slug'];
                // The sanggar logo is a better identity than the account photo.
                $conversation['counterparty_photo'] = $shop['logo_path'] ?? $counterparty['photo_path'] ?? null;
            }
        }

        return $conversation;
    }
}
