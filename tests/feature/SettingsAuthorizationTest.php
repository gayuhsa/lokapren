<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Shield\Authorization\PermissionMatcher;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Settings must never let a user change their own role. Role changes are gated
 * on the Shield `users.manage` permission, and the submitted value is validated
 * against the configured groups rather than trusted.
 *
 * @internal
 */
final class SettingsAuthorizationTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    private function makeUser(string $username, string $group): User
    {
        $provider = auth()->getProvider();

        $user = new User([
            'username' => $username,
            'email'    => "{$username}@example.com",
            'password' => 'Password!2345',
        ]);

        // Shield's UserModel::save() returns a bool and does not backfill the id
        // onto the entity, so it has to be read from the model before addGroup().
        $provider->save($user);
        $user->id = (int) $provider->getInsertID();
        $user->addGroup($group);

        return $user;
    }

    /**
     * @return array<int, string>
     */
    private function groupsOf(User $user): array
    {
        $fresh = auth()->getProvider()->find($user->id);

        $this->assertNotNull($fresh, 'user should still exist');

        return $fresh->getGroups();
    }

    public function testCustomerCannotPromoteThemselvesToAdminByPostingARole(): void
    {
        $user = $this->makeUser('escalator', 'customer');

        $this->actingAs($user)->post('/settings', [
            'username' => 'escalator',
            'email'    => 'escalator@example.com',
            'role'     => 'admin',
        ]);

        $this->assertNotContains('admin', $this->groupsOf($user));
        $this->assertContains('customer', $this->groupsOf($user));
    }

    public function testCustomerCannotGrantThemselvesTheSellerGroupEither(): void
    {
        $user = $this->makeUser('aspirant', 'customer');

        $this->actingAs($user)->post('/settings', [
            'username' => 'aspirant',
            'email'    => 'aspirant@example.com',
            'role'     => 'seller',
        ]);

        $this->assertNotContains('seller', $this->groupsOf($user));
    }

    public function testRoleSelectorIsNotRenderedToUsersWithoutThePermission(): void
    {
        $user = $this->makeUser('plainuser', 'customer');

        $result = $this->actingAs($user)->get('/settings');

        $result->assertOK();
        $result->assertDontSee('name="role"');
        $result->assertSee('users.manage');
    }

    public function testAdminHoldingThePermissionCanChangeARole(): void
    {
        // Settings::update() only ever edits the authenticated account, so the
        // request carries the admin's own details.
        $admin = $this->makeUser('modadmin', 'admin');

        $result = $this->actingAs($admin)->post('/settings', [
            'username' => 'modadmin',
            'email'    => 'modadmin@example.com',
            'role'     => 'seller',
        ]);

        $result->assertRedirect();
        $this->assertContains('seller', $this->groupsOf($admin));
    }

    public function testUnknownGroupNameIsRejectedEvenWithThePermission(): void
    {
        $admin = $this->makeUser('strictadmin', 'admin');

        $this->actingAs($admin)->post('/settings', [
            'username' => 'strictadmin',
            'email'    => 'strictadmin@example.com',
            'role'     => 'superadmin-does-not-exist',
        ]);

        $this->assertNotContains('superadmin-does-not-exist', $this->groupsOf($admin));
        $this->assertContains('admin', $this->groupsOf($admin));
    }

    public function testCustomerKeepsUpdatingTheirOwnProfileDetails(): void
    {
        $user = $this->makeUser('editor', 'customer');

        $result = $this->actingAs($user)->post('/settings', [
            'username' => 'editor',
            'email'    => 'renamed@example.com',
            'role'     => '',
        ]);

        $result->assertRedirect();

        $this->assertSame('renamed@example.com', $user->getEmail());
    }

    public function testAdminGroupIsConfiguredWithTheManageUsersPermission(): void
    {
        $matrix = config('AuthGroups')->matrix;

        $this->assertArrayHasKey('users.manage', config('AuthGroups')->permissions);
        $this->assertArrayHasKey('admin', $matrix);
        $this->assertContains('users.manage', $matrix['admin']);
    }

    public function testSellerGroupIsNotGrantedTheManageUsersPermission(): void
    {
        $matrix = config('AuthGroups')->matrix;

        $this->assertArrayHasKey('seller', $matrix);
        $this->assertNotContains('users.manage', $matrix['seller']);
    }

    public function testTheManageUsersMatrixEntryIsInTheFormShieldActuallyResolves(): void
    {
        $this->assertTrue(
            PermissionMatcher::matches('users.manage', config('AuthGroups')->matrix['admin']),
            'Shield resolves a group against the list of permission names, not a permission => scope map',
        );
    }
}
