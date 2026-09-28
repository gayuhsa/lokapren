<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthPageTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    protected $migrate = true;

    public function testLoginPageRendersTheLokaprenDesign(): void
    {
        $result = $this->get('/login');

        $result->assertOK();
        $result->assertSee('Lokapren');
        $result->assertSee('Kriya Magelang');
        $result->assertSee('Akses Akun Digital');
        $result->assertSee('Selamat Datang di Lokapren');
        $result->assertSee('Masuk ke Lokapren');
        $result->assertSee('assets/css/auth.css');
    }

    public function testAuthPagesLinkEveryComponentStylesheet(): void
    {
        foreach (['/login', '/register'] as $url) {
            $body = $this->get($url)->getBody();

            $this->assertStringContainsString('assets/css/auth.css', $body);
            $this->assertStringContainsString('assets/css/partials/header.css', $body);
            $this->assertStringContainsString('assets/css/partials/footer.css', $body);
        }
    }

    public function testComponentStylesheetsExistAndAreLinkedFromDisk(): void
    {
        $root = ROOTPATH . 'public/assets/css';

        foreach (['auth.css', 'partials/header.css', 'partials/footer.css'] as $file) {
            $this->assertFileExists($root . '/' . $file);
        }
    }

    public function testLoginPageNoLongerShowsTheTemporaryPlaceholder(): void
    {
        $result = $this->get('/login');

        $result->assertOK();
        $result->assertDontSee('Temporary placeholder frontend');
    }

    public function testLoginFormPostsTheFieldsShieldExpects(): void
    {
        $result = $this->get('/login');

        $result->assertSee('name="email"');
        $result->assertSee('name="password"');
        $result->assertSee('name="remember"');
        $result->assertSee('csrf');
    }

    public function testRegisterPageRendersTheSellerRoleChoice(): void
    {
        $result = $this->get('/register');

        $result->assertOK();
        $result->assertSee('Daftar Akun Baru');
        $result->assertSee('name="username"');
        $result->assertSee('name="password_confirm"');
        $result->assertSee('value="customer"');
        $result->assertSee('value="seller"');
    }

    public function testAuthPagesLinkToEachOther(): void
    {
        $login = $this->get('/login');
        $login->assertSee('href="' . base_url('register') . '"');

        $register = $this->get('/register');
        $register->assertSee('href="' . base_url('login') . '"');
    }

    public function testUnauthenticatedUserIsShownLoginRatherThanTheAccountArea(): void
    {
        $result = $this->get('/settings');

        $result->assertRedirect();
    }

    public function testOldInputIsRepopulatedAndEscaped(): void
    {
        $body = $this->withSession([
            '_ci_old_input' => ['post' => ['email' => '<script>alert(1)</script>']],
        ])->get('/login')->getBody();

        // The repopulated value must be escaped, never rendered as live markup.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $body);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
    }

    public function testValidationErrorsAreEscapedAndMarkedUp(): void
    {
        $body = $this->withSession([
            'errors' => ['email' => '<img src=x onerror=alert(1)>'],
        ])->get('/login')->getBody();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $body);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $body);
    }
}
