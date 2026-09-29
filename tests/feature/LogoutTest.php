<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Logging out is a state change, so it must not be reachable by following a
 * plain GET link. The form posts and the route requires a CSRF token.
 *
 * @internal
 */
final class LogoutTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    private function makeUser(): User
    {
        $provider = auth()->getProvider();

        $user = new User([
            'username' => 'logoutuser',
            'email'    => 'logout@example.com',
            'password' => 'Password!2345',
        ]);

        $provider->save($user);
        $user->id = (int) $provider->getInsertID();
        $user->addGroup('customer');

        return $user;
    }

    private function csrfToken(string $body): string
    {
        $this->assertSame(
            1,
            preg_match('/name="csrf_test_name" value="([a-f0-9]+)"/', $body, $m),
            'a CSRF token should be present on the marketplace page',
        );

        return $m[1];
    }

    public function testMarketplaceLogoutFormPostsWithACsrfToken(): void
    {
        $user   = $this->makeUser();
        $result = $this->actingAs($user)->get('/marketplace');

        $result->assertOK();
        $result->assertSee('action="' . site_url('logout') . '"');
        $result->assertSee('method="post"');
        $result->assertSee('name="csrf_test_name"');
    }

    public function testLogoutIsNotReachableByFollowingALink(): void
    {
        $user       = $this->makeUser();
        $notRouted  = false;

        try {
            // A stray link, prefetch or <img> tag must not end the session.
            $this->actingAs($user)->get('/logout');
        } catch (PageNotFoundException) {
            $notRouted = true;
        }

        $this->assertTrue($notRouted, 'GET /logout must not be a route');
        $this->assertNotNull(auth()->user(), 'the session must still be active');
    }

    public function testPostingToLogoutWithACsrfTokenLogsTheUserOut(): void
    {
        $user = $this->makeUser();

        $body    = $this->actingAs($user)->get('/marketplace')->getBody();
        $csrfKey = config('Security')->tokenName;
        $token   = $this->csrfToken($body);

        $result = $this->withBodyFormat('urlencoded')
            ->post('/logout', [$csrfKey => $token]);

        $result->assertRedirect();
        $this->assertNull(auth()->user(), 'the user should no longer be authenticated');
    }

    public function testPostingToLogoutWithoutACsrfTokenIsRejected(): void
    {
        $user = $this->makeUser();

        $rejected = false;

        try {
            $this->actingAs($user)->post('/logout');
        } catch (SecurityException) {
            $rejected = true;
        }

        $this->assertTrue($rejected, 'a logout without a CSRF token must be rejected');
    }
}
