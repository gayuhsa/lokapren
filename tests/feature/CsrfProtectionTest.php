<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\PostsWithCsrf;

/**
 * CSRF is enabled globally via app/Config/Filters.php. Every state-changing
 * request must carry a token, not only logout.
 *
 * @internal
 */
final class CsrfProtectionTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PostsWithCsrf;

    protected $migrate = true;

    protected $namespace = null;

    private function makeUser(string $username, string $group): User
    {
        $provider = auth()->getProvider();

        $user = new User([
            'username' => $username,
            'email'    => "{$username}@example.com",
            'password' => 'Password!2345',
        ]);

        $provider->save($user);
        $user->id = (int) $provider->getInsertID();
        $user->addGroup($group);

        return $user;
    }

    public function testCsrfIsRegisteredAsAGlobalFilter(): void
    {
        $this->assertContains('csrf', config('Filters')->globals['before']);
    }

    public function testLogoutWithoutATokenIsRejected(): void
    {
        $user = $this->makeUser('csrf.logout', 'customer');

        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->actingAs($user)->post('/logout');
    }

    public function testSettingsUpdateWithoutATokenIsRejected(): void
    {
        $user = $this->makeUser('csrf.settings', 'customer');

        $this->expectException(\CodeIgniter\Security\Exceptions\SecurityException::class);

        $this->actingAs($user)->post('/settings', [
            'username' => 'csrf.settings',
            'email'    => 'attacker-controlled@example.com',
        ]);
    }

    public function testSettingsUpdateWithATokenSucceeds(): void
    {
        $user = $this->makeUser('csrf.ok', 'customer');

        $result = $this->actingAs($user)->postWithCsrf('/settings', [
            'username' => 'csrf.ok',
            'email'    => 'renamed-ok@example.com',
        ]);

        $result->assertRedirect();
        $this->assertSame('renamed-ok@example.com', $user->getEmail());
    }

    public function testEveryPostFormInTheProjectEmitsACsrfToken(): void
    {
        // Guards against a form being added later without csrf_field().
        // Sign out first: Shield redirects signed-in users away from /login.
        // Shield redirects signed-in users away from /login, so clear the session
        // left behind by earlier actingAs() calls in this class.
        $this->resetServices();
        service('session')->destroy();

        $formsWithTokens = [
            '/login'    => 1,
            '/register' => 1,
        ];

        foreach ($formsWithTokens as $url => $expected) {
            $body = (string) $this->get($url)->getBody();

            $this->assertSame(
                $expected,
                preg_match_all('/name="' . preg_quote(config('Security')->tokenName, '/') . '"/', $body),
                "{$url} should render exactly {$expected} CSRF token(s)",
            );
        }
    }

    public function testLogoutFormEmitsACsrfToken(): void
    {
        $user = $this->makeUser('csrf.form', 'customer');

        $body = (string) $this->actingAs($user)->get('/marketplace')->getBody();

        $this->assertStringContainsString('name="csrf_test_name"', $body);
        $this->assertStringContainsString('action="' . site_url('logout') . '"', $body);
    }
}
