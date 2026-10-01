<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Shield\Entities\User;

/**
 * Creates Shield users with groups for tests.
 *
 * Shield's UserModel::save() returns a bool and does not backfill the generated
 * id onto the entity that was passed in, so addGroup() must be given an entity
 * whose id has been populated explicitly. Getting this wrong produces a
 * NOT NULL violation on db_auth_groups_users.user_id.
 */
trait CreatesUsers
{
    private const TEST_PASSWORD = 'Password!2345';

    private static int $userSequence = 0;

    protected function createUserWithGroup(string $usernamePrefix, string $group): User
    {
        $provider = auth()->getProvider();

        self::$userSequence++;
        $username = sprintf('%s.%d', $usernamePrefix, self::$userSequence);

        $user = new User([
            'username' => $username,
            'email'    => "{$username}@example.test",
            'password' => self::TEST_PASSWORD,
        ]);

        $provider->save($user);
        $user->id = (int) $provider->getInsertID();
        $user->addGroup($group);

        return $user;
    }

    protected function createCustomer(string $prefix = 'test.customer'): User
    {
        return $this->createUserWithGroup($prefix, 'customer');
    }

    protected function createSeller(string $prefix = 'test.seller'): User
    {
        return $this->createUserWithGroup($prefix, 'seller');
    }

    protected function createAdmin(string $prefix = 'test.admin'): User
    {
        return $this->createUserWithGroup($prefix, 'admin');
    }
}
