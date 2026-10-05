<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * The signed-in user's own profile page.
 *
 * Works for both roles: a customer sees their account summary, a seller also
 * sees their sanggar dashboard tiles. The user id always comes from Shield.
 */
class ProfileController extends BaseController
{
    public function index()
    {
        $userId = $this->requireUserId();

        return view('account/profile', [
            'title'      => 'Akun Saya',
            'dashboard'  => $this->profileService->dashboardFor($userId),
            'is_seller'  => $this->inGroup('seller'),
            'cart_count' => $this->cartCount(),
            'unread_chat' => $this->unreadChatCount(),
        ]);
    }

    /**
     * Update the profile.
     *
     * `saveProfile()` intersects the input against a whitelist of columns, so
     * posting `user_id` or a verification flag changes nothing.
     */
    public function update()
    {
        $userId = $this->requireUserId();

        $rules = [
            'full_name'  => 'required|max_length[150]',
            'nickname'   => 'permit_empty|max_length[60]',
            'bio'        => 'permit_empty|max_length[500]',
            'birth_date' => 'permit_empty|valid_date',
            'gender'     => 'permit_empty|in_list[pria,wanita]',
        ];

        if (! $this->validate($rules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $result = $this->profileService->saveProfile($userId, [
            'full_name'  => trim((string) $this->request->getPost('full_name')),
            'nickname'   => trim((string) $this->request->getPost('nickname')),
            'bio'        => trim((string) $this->request->getPost('bio')),
            'birth_date' => (string) $this->request->getPost('birth_date') ?: null,
            'gender'     => (string) $this->request->getPost('gender') ?: null,
        ]);

        return redirect()
            ->back()
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
