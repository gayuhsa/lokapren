<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Controllers\RegisterController as ShieldRegisterController;
use CodeIgniter\Shield\Exceptions\ValidationException;

class RegisterController extends ShieldRegisterController
{
    protected function getValidationRules(): array
    {
        $rules = parent::getValidationRules();

        $rules['role'] = [
            'label'  => 'Role',
            'rules'  => 'required|in_list[customer,seller]',
            'errors' => [
                'required' => 'Please choose a role.',
                'in_list'  => 'Please choose a valid role.',
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
        $user->addGroup($this->request->getPost('role'));

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
            ->with('message', lang('Auth.registerSuccess'));
    }
}
