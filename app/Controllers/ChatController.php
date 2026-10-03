<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Chat;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Exceptions\RuntimeException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * The in-app message centre.
 *
 * There is no store_id, customer_id or sender_role parameter anywhere in this
 * controller: the caller's side of every conversation comes from Shield via
 * Chat, and a conversation is only reachable when the caller is a participant.
 */
class ChatController extends BaseController
{
    /**
     * Which tab the inbox list is showing.
     */
    private const TABS = ['all', 'unread', 'orders'];

    public function index(?string $tab = null): string
    {
        $chat  = new Chat($this->db);
        $user  = $this->user();
        $tab   = in_array($tab, self::TABS, true) ? $tab : 'all';
        $all   = $chat->inbox($user);

        return view('chat', [
            'tab'       => $tab,
            'threads'   => $this->filter($all, $tab),
            'allCount'  => count($all),
            'unread'    => $chat->unreadTotal($user),
            'thread'    => null,
            'message'   => session()->getFlashdata('message'),
            'error'     => session()->getFlashdata('error'),
        ]);
    }

    /**
     * Opening a thread clears that side's unread badge.
     */
    public function show(int $conversationId): string
    {
        $chat  = new Chat($this->db);
        $user  = $this->user();
        $data  = $chat->thread($user, $conversationId);
        $all   = $chat->inbox($user);

        return view('chat', [
            'tab'       => 'all',
            'threads'   => $all,
            'allCount'  => count($all),
            'unread'    => $chat->unreadTotal($user),
            'thread'    => $data,
            'message'   => session()->getFlashdata('message'),
            'error'     => session()->getFlashdata('error'),
        ]);
    }

    public function send(int $conversationId): RedirectResponse
    {
        $request = $this->request;
        $chat    = new Chat($this->db);

        $body      = mb_substr((string) $request->getPost('body'), 0, 2000);
        $productId = $this->optionalId($request->getPost('product_id'));
        $orderId   = $this->optionalId($request->getPost('order_id'));

        // A quick reply is real text: it is copied into the form on the client
        // and arrives here as an ordinary body, so there is no separate path.
        try {
            $chat->send($this->user(), $conversationId, $body, $productId, $orderId);
        } catch (RuntimeException $e) {
            return redirect()
                ->to('chat/' . $conversationId)
                ->with('error', $e->getMessage());
        }

        return redirect()->to('chat/' . $conversationId);
    }

    /**
     * Opens (or reuses) the thread with a sanggar, then redirects into it.
     *
     * This is the entry point from a product or storefront page, so it has to
     * survive a sanggar that is not chatable.
     */
    public function open(int $storeId): RedirectResponse
    {
        try {
            $conversation = (new Chat($this->db))->openWithStore($this->user(), $storeId);
        } catch (RuntimeException $e) {
            return redirect()->to('chat')->with('error', $e->getMessage());
        }

        return redirect()->to('chat/' . (int) $conversation['id']);
    }

    /**
     * The inbox tabs only narrow what is already the caller's own list.
     *
     * @param list<array<string, mixed>> $threads
     *
     * @return list<array<string, mixed>>
     */
    private function filter(array $threads, string $tab): array
    {
        $isSeller = (new Chat($this->db))->roleFor($this->user()) === 'seller';

        if ($tab === 'unread') {
            $column = $isSeller ? 'store_unread_count' : 'customer_unread_count';

            return array_values(array_filter(
                $threads,
                static fn (array $t): bool => (int) $t[$column] > 0,
            ));
        }

        if ($tab === 'orders') {
            $column = $isSeller ? 'store_unread_count' : 'customer_unread_count';

            return array_values(array_filter(
                $threads,
                static fn (array $t): bool => $t['linked_order_id'] !== null || (int) $t[$column] > 0,
            ));
        }

        return $threads;
    }

    private function optionalId(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function user(): \CodeIgniter\Shield\Entities\User
    {
        $user = auth()->user();

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $user;
    }
}