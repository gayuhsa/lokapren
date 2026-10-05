<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\SellerProfileModel;
use App\Models\UserProfileModel;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegisterController;
use CodeIgniter\Shield\Exceptions\ValidationException;

/**
 * Registration with an explicit marketplace role.
 *
 * Shield owns the account (username, email, password, group); the marketplace
 * tables are bootstrapped here so a brand new account already has a profile row
 * to write to. A seller also gets a `seller_profiles` row, because the seller
 * area reads the sanggar's name, location and slug from there.
 *
 * The role comes from a fixed two-value list — never from a free string — so it
 * cannot be used to invent a third role.
 */
class RegisterController extends ShieldRegisterController
{
    /**
     * The only two roles in the marketplace (AGENTS.md).
     *
     * @var list<string>
     */
    private const ROLES = ['customer', 'seller'];

    protected function getValidationRules(): array
    {
        $rules = parent::getValidationRules();

        $rules['role'] = [
            'label'  => 'Peran',
            'rules'  => 'required|in_list[' . implode(',', self::ROLES) . ']',
            'errors' => [
                'required' => 'Pilih peran: pembeli atau pengrajin.',
                'in_list'  => 'Peran yang dipilih tidak dikenal.',
            ],
        ];

        // The display name feeds `user_profiles.full_name` (VARCHAR 150) and a
        // seller's `display_name` (VARCHAR 120), so the length is bounded here
        // rather than being cut off by a database error later.
        $rules['full_name'] = [
            'label'       => 'Nama lengkap',
            'rules'       => 'permit_empty|max_length[150]',
            'description' => 'Dipakai pada profil dan nama toko Anda.',
            'errors'      => [
                'max_length' => 'Nama lengkap maksimal 150 karakter.',
            ],
        ];

        return $rules;
    }

    public function registerAction(): RedirectResponse
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config('Auth')->registerRedirect());
        }

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()
                ->with('error', lang('Auth.registerDisabled'));
        }

        $users = $this->getUserProvider();

        $rules = $this->getValidationRules();

        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $users->createNewUser([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ]);

        try {
            $users->save($user);
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $user = $users->findById($users->getInsertID());

        // Validated above against ROLES; re-asserted here so the group can only
        // ever be one of the two marketplace roles even if validation changes.
        $role = (string) $this->request->getPost('role');

        if (! in_array($role, self::ROLES, true)) {
            $role = 'customer';
        }

        $user->addGroup($role);

        $this->bootstrapProfiles(
            (int) $user->id,
            (string) $user->username,
            trim((string) $this->request->getPost('full_name')),
            $role
        );

        Events::trigger('register', $user);

        $authenticator = auth('session')->getAuthenticator();
        $authenticator->startLogin($user);

        $hasAction = $authenticator->startUpAction('register', $user);
        if ($hasAction) {
            return redirect()->route('auth-action-show');
        }

        $user->activate();
        $authenticator->completeLogin($user);

        return redirect()->to(config('Auth')->registerRedirect())
            ->with('message', $role === 'seller'
                ? 'Selamat datang! Lengkapi data toko Anda di menu Mitra.'
                : 'Selamat datang di Lokapren.');
    }

    /**
     * Give a new account its marketplace rows.
     *
     * Both rows are optional in the schema and are seeded from the username, so
     * the seller area and the profile page have something to read before the
     * user has filled anything in. Nothing here is trusted input: the only
     * value used is the account's own username.
     */
    private function bootstrapProfiles(int $userId, string $username, string $fullName, string $role): void
    {
        $now = date('Y-m-d H:i:s');

        $profiles = new UserProfileModel();

        if ($profiles->findByUserId($userId) === null) {
            // The typed name is preferred, but the account's username is the
            // fallback so `full_name` (NOT NULL) is never left empty. It is cut
            // to the 150-character column width so the insert cannot fail.
            $displayName = mb_substr($fullName !== '' ? $fullName : $username, 0, 150);

            $profiles->insertRow([
                'user_id'    => $userId,
                'full_name'  => $displayName,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($role !== 'seller') {
            return;
        }

        $sellers = new SellerProfileModel();

        if ($sellers->findForUser($userId) !== null) {
            return;
        }

        $shopName = $fullName !== '' ? $fullName : $username;

        $sellers->insertRow([
            'user_id'      => $userId,
            'slug'         => $this->uniqueSlug($username, $userId),
            'partner_code' => 'LKP-' . str_pad((string) $userId, 5, '0', STR_PAD_LEFT),
            'display_name' => $shopName,
            'owner_name'   => $shopName,
            'is_active'    => 1,
            'member_since' => date('Y-m-d'),
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    /**
     * A storefront slug that is both URL-safe and unique.
     *
     * `slug` is UNIQUE in the database, and two members can pick the same
     * username-looking handle, so the user id is always appended rather than
     * hoping the base slug is free.
     */
    private function uniqueSlug(string $username, int $userId): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $username), '-'));
        $base = $base === '' ? 'sanggar' : $base;

        return substr($base, 0, 100) . '-' . $userId;
    }
}
