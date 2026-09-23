<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Exceptions\ValidationException;

class Settings extends BaseController
{
    public function index(): string
    {
        $user   = auth()->user();
        $groups = $user->getGroups();

        return view('settings', [
            'currentUsername' => $user->username,
            'currentEmail'    => $user->getEmail(),
            'currentRole'     => $groups[0] ?? 'customer',
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

            $role = (string) $request->getPost('role');
            if ($role !== '' && ! in_array($role, $user->getGroups(), true)) {
                $user->syncGroups($role);
            }
        } catch (ValidationException) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan pengaturan.');
        }

        return redirect()->to('settings')->with('message', 'Pengaturan berhasil diperbarui.');
    }
}