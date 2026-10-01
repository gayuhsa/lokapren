<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Helper for posting to routes now that CSRF protection is enabled globally.
 *
 * CSRF tokens are regenerated per response and are bound to the session, so a
 * token has to be scraped from a real page first. `postWithCsrf()` does the GET
 * and reuses the token for the POST.
 *
 * @internal
 */
trait PostsWithCsrf
{
    /**
     * @param array<string, mixed> $data
     */
    protected function postWithCsrf(string $url, array $data, ?string $tokenSourcePath = null): \CodeIgniter\Test\TestResponse
    {
        $token = $this->scrapeCsrfToken($tokenSourcePath ?? $url);

        return $this->post($url, $data + [
            config('Security')->tokenName => $token,
        ]);
    }

    /**
     * Reads a live CSRF token out of a rendered page.
     */
    protected function scrapeCsrfToken(string $url): string
    {
        $body = (string) $this->get($url)->getBody();

        if (preg_match('/name="' . preg_quote(config('Security')->tokenName, '/') . '" value="([a-f0-9]+)"/', $body, $m) !== 1) {
            $this->fail("No CSRF token found on {$url}");
        }

        return $m[1];
    }
}
