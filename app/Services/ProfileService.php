<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AddressModel;
use App\Models\ConversationModel;
use App\Models\OrderModel;
use App\Models\ReviewModel;
use App\Models\SellerProfileModel;
use App\Models\UserProfileModel;

/**
 * The signed-in user's own account data: profile, address book and the
 * rollups the profile page shows under "Dampak Kriya Anda".
 *
 * `user_id` is always taken from the authenticated Shield user in the
 * controller and passed in here; nothing in this service reads an owner id from
 * a request.
 */
class ProfileService
{
    private UserProfileModel $profiles;
    private AddressModel $addresses;
    private OrderModel $orders;
    private ReviewModel $reviews;
    private SellerProfileModel $sellers;

    public function __construct()
    {
        $this->profiles  = new UserProfileModel();
        $this->addresses = new AddressModel();
        $this->orders    = new OrderModel();
        $this->reviews   = new ReviewModel();
        $this->sellers   = new SellerProfileModel();
    }

    /**
     * The `user_profiles` row for a user, or null when they have not filled in
     * their profile yet.
     *
     * @return array<string, mixed>|null
     */
    public function profileFor(int $userId): ?array
    {
        return $this->profiles->findByUserId($userId);
    }

    /**
     * Create or update the profile row for a user.
     *
     * `user_id` is applied last so a caller-supplied value can never attach a
     * profile to another account.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function saveProfile(int $userId, array $data): array
    {
        $existing = $this->profiles->findByUserId($userId);

        // Only the columns a customer may set; counters and verification flags
        // stay server-owned.
        $writable = array_intersect_key($data, array_flip([
            'full_name', 'nickname', 'photo_path', 'bio', 'birth_date', 'gender',
        ]));

        $writable['user_id']    = $userId;
        $writable['updated_at'] = date('Y-m-d H:i:s');

        if ($existing !== null) {
            $ok = $this->profiles->updateWhere($writable, ['user_id' => $userId]);

            return [
                'ok'      => $ok,
                'message' => $ok ? 'Profil diperbarui.' : 'Tidak ada perubahan untuk disimpan.',
            ];
        }

        $writable['created_at'] = date('Y-m-d H:i:s');

        try {
            $this->profiles->insertRow($writable);
        } catch (\Throwable) {
            return ['ok' => false, 'message' => 'Profil gagal disimpan.'];
        }

        return ['ok' => true, 'message' => 'Profil disimpan.'];
    }

    /**
     * Everything the profile page renders.
     *
     * @return array<string, mixed>
     */
    public function dashboardFor(int $userId): array
    {
        $profile    = $this->profileFor($userId);
        $seller     = $this->sellers->findForUser($userId);
        $orders     = $this->orders->forCustomerHistory($userId, 10);
        $conversations = new ConversationModel();

        // Spending counts every finished order, not just the ten on this page.
        $contributionTotal = $this->orders->totalForCustomer($userId, [
            OrderModel::STATUS_SHIPPED,
            OrderModel::STATUS_DELIVERED,
            OrderModel::STATUS_COMPLETED,
        ]);

        $activeOrderCount = 0;

        foreach ($orders as $order) {
            if (! in_array($order['status'], [
                OrderModel::STATUS_COMPLETED,
                OrderModel::STATUS_CANCELLED,
                OrderModel::STATUS_REFUNDED,
            ], true)) {
                $activeOrderCount++;
            }
        }

        return [
            'profile'            => $profile,
            'seller_profile'     => $seller,
            'addresses'          => $this->addresses->forUser($userId),
            'recent_orders'      => $orders,
            'active_order_count' => $activeOrderCount,
            'contribution_total' => $contributionTotal,
            'orders_total'       => $this->orders->countForCustomer($userId),
            'reviews_written'    => (int) ($profile['reviews_written'] ?? 0),
            'unread_chat'        => $conversations->totalUnreadFor($userId),
        ];
    }
}
