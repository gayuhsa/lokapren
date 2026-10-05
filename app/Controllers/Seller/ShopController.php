<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;

/**
 * The seller's storefront editor.
 *
 * Behind `role:seller`, and every write passes the signed-in user's id to
 * `ShopService`, which resolves the `seller_id` itself. A posted `seller_id`
 * reaches nothing.
 */
class ShopController extends BaseController
{
    public function index()
    {
        $sellerId = $this->requireUserId();
        $shop     = $this->shopService->editorFor($sellerId);

        if ($shop === null) {
            // A seller account without a sanggar profile cannot edit a storefront.
            return redirect()
                ->route('seller_dashboard')
                ->with('error', 'Profil sanggar belum lengkap. Hubungi kami untuk verifikasi.');
        }

        return view('seller/shop/index', [
            'title'      => 'Toko Saya',
            'shop'       => $shop,
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Storefront identity and free-text location, including the map pin.
     */
    public function update()
    {
        $sellerId = $this->requireUserId();

        $rules = [
            'display_name'         => 'required|max_length[150]',
            'owner_name'           => 'permit_empty|max_length[150]',
            'tagline'              => 'permit_empty|max_length[255]',
            'description'          => 'permit_empty|max_length[3000]',
            'craft_focus'          => 'permit_empty|max_length[150]',
            'address_line'         => 'permit_empty|max_length[255]',
            'village'              => 'permit_empty|max_length[100]',
            'district'             => 'permit_empty|max_length[100]',
            'regency'              => 'permit_empty|max_length[100]',
            'province'             => 'permit_empty|max_length[100]',
            'postal_code'          => 'permit_empty|max_length[10]',
            'landmark_name'        => 'permit_empty|max_length[150]',
            'landmark_distance_km' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'latitude'             => 'permit_empty|numeric|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'            => 'permit_empty|numeric|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'is_active'            => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->shopService->saveProfile($sellerId, [
            'display_name'         => (string) $this->request->getPost('display_name'),
            'owner_name'           => (string) $this->request->getPost('owner_name'),
            'tagline'              => (string) $this->request->getPost('tagline'),
            'description'          => (string) $this->request->getPost('description'),
            'craft_focus'          => (string) $this->request->getPost('craft_focus'),
            'address_line'         => (string) $this->request->getPost('address_line'),
            'village'              => (string) $this->request->getPost('village'),
            'district'             => (string) $this->request->getPost('district'),
            'regency'              => (string) $this->request->getPost('regency'),
            'province'             => (string) $this->request->getPost('province'),
            'postal_code'          => (string) $this->request->getPost('postal_code'),
            'landmark_name'        => (string) $this->request->getPost('landmark_name'),
            'landmark_distance_km' => $this->request->getPost('landmark_distance_km'),
            'latitude'             => $this->request->getPost('latitude'),
            'longitude'            => $this->request->getPost('longitude'),
            'is_active'            => $this->request->getPost('is_active') !== '0',
        ]);

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Weekly opening hours, one row per day.
     */
    public function updateHours()
    {
        $sellerId = $this->requireUserId();
        $week     = [];

        for ($day = 1; $day <= 7; $day++) {
            $week[$day] = [
                'opens_at'  => (string) ($this->request->getPost('opens_at')[$day] ?? ''),
                'closes_at' => (string) ($this->request->getPost('closes_at')[$day] ?? ''),
                'is_closed' => $this->request->getPost('is_closed')[$day] === '1' ? 1 : 0,
            ];
        }

        $result = $this->shopService->saveHours($sellerId, $week);

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Facility checkboxes. Only codes from the known vocabulary are kept.
     */
    public function updateFacilities()
    {
        $result = $this->shopService->saveFacilities(
            $this->requireUserId(),
            (array) $this->request->getPost('facilities')
        );

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateStory()
    {
        $result = $this->shopService->addStorySection($this->requireUserId(), [
            'title'    => (string) $this->request->getPost('title'),
            'subtitle' => (string) $this->request->getPost('subtitle'),
            'body'     => (string) $this->request->getPost('body'),
        ]);

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Upload a storefront photo or short video.
     */
    public function updateMedia()
    {
        $file = $this->request->getFile('media');

        if ($file === null || ! $file->isValid()) {
            return redirect()
                ->route('seller_shop')
                ->with('error', 'Pilih berkas yang valid.');
        }

        $result = $this->shopService->addMedia(
            $this->requireUserId(),
            (string) $this->request->getPost('media_type'),
            $file,
            (string) $this->request->getPost('title') ?: null,
            (string) $this->request->getPost('caption') ?: null,
        );

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function storeQuickReply()
    {
        $result = $this->shopService->addQuickReply($this->requireUserId(), [
            'title' => (string) $this->request->getPost('title'),
            'body'  => (string) $this->request->getPost('body'),
        ]);

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function deleteQuickReply(int $id)
    {
        $result = $this->shopService->deleteQuickReply($id, $this->requireUserId());

        return redirect()
            ->route('seller_shop')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
