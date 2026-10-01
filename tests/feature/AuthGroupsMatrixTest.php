<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Shield\Authorization\PermissionMatcher;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\CreatesUsers;

/**
 * Pins the intended permission grants per group.
 *
 * Shield resolves a group with PermissionMatcher against the *list* of
 * permission names in its matrix entry. The legacy `'permission' => 'scope'`
 * map form is never matched and silently grants nothing, which is how the
 * seller/admin permissions in this project ended up inert. These tests fail if
 * the matrix regresses to the map form.
 *
 * @internal
 */
final class AuthGroupsMatrixTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use CreatesUsers;
    use DatabaseTestTrait;
    use \CodeIgniter\Test\FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    /**
     * @return array<string, array{group: string, can: string[], cannot: string[]}>
     */
    public static function expectedMatrixProvider(): iterable
    {
        yield 'seller' => [
            'group'  => 'seller',
            'can'    => [
                'stores.manage',
                'products.manage',
                'orders.manage',
                'store_posts.manage',
                'conversations.reply',
            ],
            'cannot' => [
                'users.manage',
                'reviews.moderate',
            ],
        ];

        yield 'admin' => [
            'group'  => 'admin',
            'can'    => [
                'users.manage',
                'stores.manage',
                'products.manage',
                'orders.manage',
                'reviews.moderate',
                'conversations.reply',
            ],
            'cannot' => [],
        ];
    }

    /**
     * @dataProvider expectedMatrixProvider
     *
     * @param array<int, string> $can
     * @param array<int, string> $cannot
     */
    public function testGroupHoldsExactlyTheExpectedPermissions(string $group, array $can, array $cannot): void
    {
        $user = $this->createUserWithGroup("matrix.{$group}", $group);

        foreach ($can as $permission) {
            $this->assertTrue(
                $user->can($permission),
                "{$group} should hold {$permission}",
            );
        }

        foreach ($cannot as $permission) {
            $this->assertFalse(
                $user->can($permission),
                "{$group} should NOT hold {$permission}",
            );
        }
    }

    public function testCustomerHoldsNoManagementPermissions(): void
    {
        $user = $this->createUserWithGroup('matrix.customer', 'customer');

        foreach (array_keys(config('AuthGroups')->permissions) as $permission) {
            $this->assertFalse(
                $user->can($permission),
                "customer should not hold {$permission}",
            );
        }
    }

    /**
     * The matrix entry must be a list. A map form resolves to nothing at all,
     * which is the exact bug this file guards against.
     */
    public function testMatrixEntriesArePlainListsOfPermissionNames(): void
    {
        foreach (config('AuthGroups')->matrix as $group => $entry) {
            $this->assertIsArray($entry, "{$group} matrix entry should be an array");

            foreach ($entry as $permission) {
                $this->assertIsString($permission, "{$group} matrix entry should hold permission names, not a permission => scope map");

                $this->assertArrayHasKey(
                    $permission,
                    config('AuthGroups')->permissions,
                    "{$group} grants undeclared permission {$permission}",
                );

                $this->assertTrue(
                    PermissionMatcher::matches($permission, $entry),
                    "{$permission} granted to {$group} is not matchable by Shield",
                );
            }
        }
    }

    public function testEveryPermissionIsGrantedToAtLeastOneGroup(): void
    {
        $granted = [];

        foreach (config('AuthGroups')->matrix as $entry) {
            $granted = array_merge($granted, $entry);
        }

        foreach (array_keys(config('AuthGroups')->permissions) as $permission) {
            $this->assertContains($permission, $granted, "{$permission} is declared but never granted");
        }
    }

    public function testAdminAndSellerDoNotOverlapInUserManagement(): void
    {
        $seller = $this->createUserWithGroup('matrix.seller2', 'seller');
        $admin  = $this->createUserWithGroup('matrix.admin2', 'admin');

        $this->assertFalse($seller->can('users.manage'));
        $this->assertTrue($admin->can('users.manage'));
    }
}
