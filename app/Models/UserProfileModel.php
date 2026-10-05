<?php

declare(strict_types=1);

namespace App\Models;

/**
 * 1:1 customer/seller profile.
 *
 * `user_id` is server-owned and taken from the authenticated Shield user.
 * The `*_total` counters are recomputed by the application, never by a request.
 */
class UserProfileModel extends BaseModel
{
    protected $table = 'user_profiles';

    protected $returnType = 'array';

    protected $allowedFields = [
        'full_name',
        'nickname',
        'photo_path',
        'bio',
        'birth_date',
        'gender',
        'prefers_antar_terima',
        'notify_chat',
    ];

    protected array $casts = [
        'contribution_total'   => 'int',
        'orders_total'         => 'int',
        'reviews_written'      => 'int',
        'prefers_antar_terima' => 'bool',
        'notify_chat'          => 'bool',
    ];

    protected $validationRules = [
        'full_name' => 'required|max_length[150]',
        'nickname'  => 'permit_empty|max_length[100]',
        'photo_path' => 'permit_empty|max_length[255]',
        'birth_date' => 'permit_empty|valid_date',
        'gender'    => 'permit_empty|in_list[male,female,other]',
    ];

    /**
     * Profile for a user id, or null when they have not created one.
     */
    public function findByUserId(int $userId): ?array
    {
        return $this->newRow(
            $this->newQuery()->where('user_id', $userId)
        );
    }
}