<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * In-app chat between a customer and a seller.
 *
 * `ChatService::threadFor()` resolves the conversation through Shield's
 * participant check, so a customer who guesses another thread id in the URL
 * gets a 404 rather than someone else's messages. Sending uses the same check
 * before writing.
 */
class ChatController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();
        $page   = max(1, (int) $this->request->getGet('page'));
        $limit  = 20;

        return view('chat/index', [
            'title'       => 'Pesan',
            'threads'     => $this->chatService->inboxFor($userId, $limit, ($page - 1) * $limit),
            'page'        => $page,
            'unread_chat' => $this->unreadChatCount(),
            'cart_count'  => $this->cartCount(),
        ]);
    }

    public function thread(int $id)
    {
        $userId = $this->requireUserId();
        $thread = $this->chatService->threadFor($id, $userId);

        if ($thread === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('chat/thread', [
            'title'       => $thread['counterparty_name'] ?? 'Percakapan',
            'thread'      => $thread,
            // Quick replies are the seller's saved phrases; a customer simply
            // does not get any.
            'quick_replies' => $this->inGroup('seller')
                ? $this->chatService->quickRepliesFor($userId)
                : [],
            'unread_chat' => $this->unreadChatCount(),
            'cart_count'  => $this->cartCount(),
        ]);
    }

    public function send(int $id)
    {
        $userId = $this->requireUserId();
        $body   = trim((string) $this->request->getPost('body'));

        if ($body === '' || mb_strlen($body) > 2000) {
            return redirect()
                ->route('chat_thread', [$id])
                ->with('error', 'Pesan tidak boleh kosong dan maksimal 2000 karakter.');
        }

        $result = $this->chatService->send($id, $userId, $body);

        if (! $result['ok']) {
            return redirect()
                ->route('chat_thread', [$id])
                ->with('error', $result['message']);
        }

        return redirect()->route('chat_thread', [$id]);
    }

    /**
     * Start (or reuse) a conversation about a product.
     *
     * The product must be published; the seller is read from the product row, so
     * the thread is always with that product's real owner.
     */
    public function startFromProduct(int $productId)
    {
        $userId = $this->requireUserId();

        $thread = $this->chatService->openFromProduct($userId, $productId);

        if ($thread === null) {
            return redirect()
                ->back()
                ->with('error', 'Produk tidak tersedia untuk ditanyakan.');
        }

        return redirect()->route('chat_thread', [(int) $thread['id']]);
    }

    /**
     * Badge counter for the header, polled by the layout.
     *
     * Returns JSON rather than HTML so the header can refresh it without a page
     * load; the number is scoped to the caller.
     */
    public function unread()
    {
        return $this->response->setJSON([
            'unread' => $this->unreadChatCount(),
        ]);
    }
}
