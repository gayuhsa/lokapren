<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Exceptions\ValidationException;

class Settings extends BaseController
{
    /**
     * Role changes are a Shield permission, never a client-supplied fact.
     */
    private const MANAGE_ROLES_PERMISSION = 'users.manage';

    public function index(): string
    {
        $user   = auth()->user();
        $groups = $user->getGroups();

        return view('settings', [
            'currentUsername' => $user->username,
            'currentEmail'    => $user->getEmail(),
            'currentRole'     => $groups[0] ?? 'customer',
            'canManageRoles'  => $this->canManageRoles(),
            'assignableRoles' => $this->assignableRoles(),
        ]);
    }

    public function update(): RedirectResponse
    {
        $request = $this->request;

        $rules = [
            'username' => config('Auth')->usernameValidationRules,
            'email'    => config('Auth')->emailValidationRules,
        ];

        if (! $this->validateData($request->getPost(), $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user  = auth()->user();
        $users = auth()->getProvider();

        try {
            $user->fill([
                'username' => (string) $request->getPost('username'),
                'email'    => (string) $request->getPost('email'),
            ]);

            $users->save($user);

            $this->applyRoleChange($user, (string) $request->getPost('role'));
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pengaturan.');
        }

        return redirect()->to('settings')->with('message', 'Pengaturan berhasil diperbarui.');
    }

    private function canManageRoles(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can(self::MANAGE_ROLES_PERMISSION);
    }

    /**
     * Roles a user with users.manage may assign. Sourced from configuration so
     * an unknown group name can never be written through this endpoint.
     *
     * @return array<string, string> group => title
     */
    private function assignableRoles(): array
    {
        $roles = [];

        foreach (config('AuthGroups')->groups as $group => $info) {
            $roles[$group] = $info['title'];
        }

        return $roles;
    }

    /**
     * Applies a role change only when the current user holds users.manage.
     *
     * A submitted role is ignored outright when the caller is not permitted, so
     * a forged `role` field cannot escalate privileges; an unrecognised role is
     * likewise discarded rather than trusted.
     */
    private function applyRoleChange(object $user, string $role): void
    {
        if ($role === '' || ! $this->canManageRoles()) {
            return;
        }

        if (! array_key_exists($role, $this->assignableRoles())) {
            return;
        }

        if (! in_array($role, $user->getGroups(), true)) {
            $user->syncGroups($role);
        }
    }
}