<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * It also holds the two helpers every marketplace controller needs: the id of
 * the signed-in user, resolved from Shield rather than the request, and the
 * group check that separates `customer` from `seller`.
 */
abstract class BaseController extends Controller
{
    /**
     * Shared services, initialised per request.
     *
     * Declared explicitly because dynamic properties are deprecated in PHP 8.2.
     */
    protected \App\Services\CartService $cartService;
    protected \App\Services\CheckoutService $checkoutService;
    protected \App\Services\OrderService $orderService;
    protected \App\Services\ChatService $chatService;
    protected \App\Services\CatalogService $catalogService;
    protected \App\Services\ProfileService $profileService;
    protected \App\Services\UploadService $uploadService;
    protected \App\Services\ProductService $productService;
    protected \App\Services\ShopService $shopService;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['form', 'url', 'text'];

        parent::initController($request, $response, $logger);

        $this->cartService     = service('cartService');
        $this->checkoutService = service('checkoutService');
        $this->orderService    = service('orderService');
        $this->chatService     = service('chatService');
        $this->catalogService  = service('catalogService');
        $this->profileService  = service('profileService');
        $this->uploadService   = service('uploadService');
        $this->productService = service('productService');
        $this->shopService    = service('shopService');
    }

    /**
     * The authenticated user's id, or null when nobody is signed in.
     *
     * Every controller that touches user-scoped data takes its owner id from
     * here. AGENTS.md is explicit that a client-supplied `user_id` or
     * `seller_id` must never be trusted, so no controller reads one from the
     * request.
     */
    protected function userId(): ?int
    {
        $user = auth()->user();

        return $user === null ? null : (int) $user->id;
    }

    /**
     * The signed-in user's id, or a 403 when they are not authenticated.
     *
     * Used by controllers that sit behind an auth filter but should still fail
     * closed rather than assume the filter ran.
     */
    protected function requireUserId(): int
    {
        $userId = $this->userId();

        if ($userId === null) {
            throw \CodeIgniter\Exceptions\ForbiddenException::forPageNotFound();
        }

        return $userId;
    }

    /**
     * Whether the signed-in user belongs to `$group`.
     */
    protected function inGroup(string $group): bool
    {
        $user = auth()->user();

        return $user !== null && in_array($group, $user->getGroups(), true);
    }

    /**
     * Number of cart units for the header badge, or 0 when signed out.
     */
    protected function cartCount(): int
    {
        $userId = $this->userId();

        return $userId === null ? 0 : $this->cartService->badgeCount($userId);
    }

    /**
     * Unread chat count for the header badge.
     */
    protected function unreadChatCount(): int
    {
        $userId = $this->userId();

        return $userId === null ? 0 : $this->chatService->totalUnreadFor($userId);
    }

    /**
     * Redirect back to the previous page with a flash message.
     */
    protected function withMessage(string $route, string $message, string $level = 'success'): RedirectResponse
    {
        return redirect()->to($route)->with($level, $message);
    }
}
