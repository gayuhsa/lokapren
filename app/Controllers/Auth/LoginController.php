<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Authentication\Passwords;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLoginController;

/**
 * Login that accepts either an email address or a username in one field.
 *
 * Shield's `Session` authenticator records a login attempt against exactly one
 * credential key, so a combined "Email atau Nama Pengguna" input has to be
 * mapped onto `email` or `username` before the attempt is made. Which key it is
 * follows from the shape of the value: something containing an `@` is treated
 * as an email, anything else as a username. That mapping only chooses *which*
 * column to look in — the password is still verified by Shield, and a wrong
 * guess simply fails to find a user and is counted as a failed attempt.
 */
class LoginController extends ShieldLoginController
{
    /**
     * The field the combined form posts. The value decides whether it is
     * treated as an email or as a username; the key it is stored under stays
     * `email` so `old('email')` still repopulates the form.
     */
    private const COMBINED_FIELD = 'email';

    public function loginView(): string
    {
        helper('form');

        return view('auth/login', [
            'title' => 'Masuk ke Lokapren',
        ]);
    }

    public function loginAction(): RedirectResponse
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->loginRedirect())->withCookies();
        }

        $identifier = trim((string) $this->request->getPost(self::COMBINED_FIELD));
        $password   = (string) $this->request->getPost('password');

        if ($identifier === '' || $password === '') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Masukkan email atau nama pengguna beserta kata sandi.');
        }

        // Shield's own login rules validate the identifier as an email, which
        // would reject a perfectly valid username. The rules here therefore
        // check the email format only when the value actually looks like an
        // email, and the password rule is taken from Shield so the bcrypt
        // 72-byte ceiling still applies.
        $isEmail = str_contains($identifier, '@');

        $rules = [
            self::COMBINED_FIELD => [
                'label'  => 'Email atau nama pengguna',
                'rules'  => $isEmail
                    ? ['required', 'valid_email', 'max_length[255]']
                    : ['required', 'string', 'max_length[255]'],
                'errors' => [
                    'valid_email' => 'Format email tidak valid.',
                ],
            ],
            'password' => [
                'label'  => 'Kata sandi',
                'rules'  => ['required', Passwords::getMaxLengthRule()],
                'errors' => [
                    'max_byte' => 'Kata sandi terlalu panjang.',
                    'required' => 'Kata sandi wajib diisi.',
                ],
            ],
        ];

        if (! $this->validateData(
            $this->request->getPost(),
            $rules,
            [],
            config('Auth')->DBGroup,
        )) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Shape decides the column, never the value itself. The password is
        // part of the credential set because Shield's authenticator reads it
        // from there, not from the request.
        $credentials = [
            $isEmail ? 'email' : 'username' => $identifier,
            'password'                      => $password,
        ];

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $result = $authenticator->remember((bool) $this->request->getPost('remember'))
            ->attempt($credentials);

        if (! $result->isOK()) {
            // Shield's own message would name the field the client did not use.
            return redirect()->back()
                ->withInput()
                ->with('error', 'Email, nama pengguna, atau kata sandi salah.');
        }

        if ($authenticator->hasAction()) {
            return redirect()->route('auth-action-show')->withCookies();
        }

        return redirect()->to(config('Auth')->loginRedirect())->withCookies();
    }
}

