<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * A signed-out visitor must be sent to /login from every login-walled page.
 *
 * Shield's `session` filter does the work, but no other test proves it: they
 * all run signed in, and AGENTS.md explicitly requires unauthenticated-access
 * coverage. Both halves matter — a walled page must never render for a guest
 * and never 404, while the public storefront must stay reachable.
 *
 * @internal
 */
final class GuestWallTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    private const WALLED_GET_PAGES = [
        '/account',
        '/account/addresses',
        '/account/addresses/create',
        '/account/addresses/1/edit',
        '/cart',
        '/checkout',
        '/checkout/success',
        '/orders',
        '/orders/1',
        '/orders/1/tracking',
        '/chat',
        '/chat/1',
        '/chat/unread',
        '/seller',
        '/seller/products',
        '/seller/products/create',
        '/seller/products/1/edit',
        '/seller/variants/1',
        '/seller/shop',
        '/seller/articles',
        '/seller/articles/create',
        '/seller/articles/1/edit',
        '/seller/orders',
    ];

    public function testEveryWalledGetPageSendsGuestToLogin(): void
    {
        foreach (self::WALLED_GET_PAGES as $uri) {
            $response = $this->get($uri);

            try {
                $response->assertRedirectTo('login');
            } catch (Throwable $e) {
                $this->fail("A guest visiting {$uri} must be redirected to /login. {$e->getMessage()}");
            }
        }
    }

    public function testWalledFormPostsSendGuestToLoginToo(): void
    {
        $posts = [
            '/cart/add/1'     => ['quantity' => 1],
            '/checkout'       => ['address_id' => 1],
            '/chat/1'         => ['body' => 'halo'],
            '/orders/1/cancel' => [],
        ];

        foreach ($posts as $uri => $data) {
            $response = $this->post($uri, $data);

            try {
                $response->assertRedirectTo('login');
            } catch (Throwable $e) {
                $this->fail("A guest posting to {$uri} must be redirected to /login. {$e->getMessage()}");
            }
        }
    }

    public function testPublicPagesStayReachableWithoutLogin(): void
    {
        foreach (['/', '/catalog', '/location', '/articles', '/login'] as $uri) {
            $this->get($uri)->assertStatus(200);
        }
    }
}
