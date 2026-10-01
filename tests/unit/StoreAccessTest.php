<?php

declare(strict_types=1);

namespace Tests\unit;

use App\Services\StoreAccess;
use App\Services\StoreAccessDenied;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;

/**
 * StoreAccess is the server-side ownership gate.
 *
 * The rule it enforces: a seller's reachable store is always derived from the
 * authenticated Shield user, never from a submitted store_id. So Seller A must
 * not be able to act on Seller B's store even though both hold the same
 * permission.
 *
 * @internal
 */
final class StoreAccessTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;

    protected $migrate = true;

    protected $namespace = null;

    private StoreAccess $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = new StoreAccess();
    }

    public function testResolvesTheCallersOwnStore(): void
    {
        $seller  = $this->createSeller('access.own');
        $store   = $this->makeStore($seller);

        $this->assertSame((int) $store['id'], $this->access->idForUser($seller));
        $this->assertTrue($this->access->ownsStore($seller, (int) $store['id']));
    }

    public function testReturnsNullForUsersWithNoStore(): void
    {
        $customer = $this->createCustomer('access.nostore');

        $this->assertNull($this->access->forUser($customer));
        $this->assertNull($this->access->idForUser($customer));
    }

    public function testSellerOwnsTheirOwnStore(): void
    {
        $seller = $this->createSeller('access.alice');
        $store  = $this->makeStore($seller);

        $this->assertTrue($this->access->ownsStore($seller, (int) $store['id']));
    }

    public function testSellerCannotActOnAnotherSellersStore(): void
    {
        $aliceStore = $this->makeStore($this->createSeller('access.alice'));
        $bob        = $this->createSeller('access.bob');

        $this->assertFalse($this->access->ownsStore($bob, (int) $aliceStore['id']));
    }

    public function testAssertStoreThrowsNotOwnerForAnotherSellersStore(): void
    {
        $aliceStore = $this->makeStore($this->createSeller('access.alice'));
        $bob        = $this->createSeller('access.bob');

        try {
            $this->access->assertStore($bob, (int) $aliceStore['id'], 'products.manage');
            $this->fail('Seller B should not be allowed to manage Seller A\'s store');
        } catch (StoreAccessDenied $e) {
            $this->assertSame(StoreAccessDenied::TYPE_NOT_OWNER, $e->getType());
        }
    }

    public function testAssertStoreReturnsTheStoreForTheOwner(): void
    {
        $seller = $this->createSeller('access.return');
        $store  = $this->makeStore($seller);

        $resolved = $this->access->assertStore($seller, (int) $store['id'], 'products.manage');

        $this->assertSame((int) $store['id'], (int) $resolved['id']);
    }

    public function testMissingPermissionIsCheckedBeforeOwnership(): void
    {
        $aliceStore = $this->makeStore($this->createSeller('access.alice'));
        $bob        = $this->createSeller('access.noperm');

        try {
            // products.manage is held by sellers, so use one they lack.
            $this->access->assertStore($bob, (int) $aliceStore['id'], 'users.manage');
            $this->fail('Seller should not hold users.manage');
        } catch (StoreAccessDenied $e) {
            $this->assertSame(StoreAccessDenied::TYPE_MISSING_PERMISSION, $e->getType());
            $this->assertSame('users.manage', $e->getPermission());
        }
    }

    public function testCustomerHoldingNoManagementPermissionIsRejected(): void
    {
        $customer = $this->createCustomer('access.customer');
        $store    = $this->makeStore($customer);

        try {
            $this->access->assertStore($customer, (int) $store['id'], 'products.manage');
            $this->fail('Customer should not hold products.manage');
        } catch (StoreAccessDenied $e) {
            $this->assertSame(StoreAccessDenied::TYPE_MISSING_PERMISSION, $e->getType());
        }
    }

    public function testUnauthenticatedCallerIsRejected(): void
    {
        try {
            $this->access->assertStore(null, 1, 'products.manage');
            $this->fail('Anonymous caller should be rejected');
        } catch (StoreAccessDenied $e) {
            $this->assertSame(StoreAccessDenied::TYPE_NOT_AUTHENTICATED, $e->getType());
        }
    }

    public function testUnknownStoreIsReportedAsSuch(): void
    {
        $seller = $this->createSeller('access.unknown');

        try {
            $this->access->assertStore($seller, 999999, 'products.manage');
            $this->fail('Missing store should throw');
        } catch (StoreAccessDenied $e) {
            $this->assertSame(StoreAccessDenied::TYPE_UNKNOWN_STORE, $e->getType());
        }
    }

    public function testAdminMayActAcrossStores(): void
    {
        $aliceStore = $this->makeStore($this->createSeller('access.alice'));
        $admin      = $this->createAdmin('access.admin');

        $store = $this->access->assertStore($admin, (int) $aliceStore['id'], 'reviews.moderate');

        $this->assertSame((int) $aliceStore['id'], (int) $store['id']);
    }

    public function testAssertProductResolvesOwnershipThroughItsStore(): void
    {
        $alice = $this->createSeller('access.prodalice');
        $store = $this->makeStore($alice);
        $aliceProduct = $this->makeProduct($store);

        $bob = $this->createSeller('access.prodbob');
        $this->makeStore($bob);

        // Bob owns a store, but not the one this product belongs to.
        $this->assertTrue($this->access->ownsStore($bob, (int) $this->access->idForUser($bob)));

        $this->expectException(StoreAccessDenied::class);

        $this->access->assertProduct($bob, $aliceProduct, 'products.manage');
    }

    public function testStoresAreOneToOneWithUsers(): void
    {
        $seller = $this->createSeller('access.dup');
        $this->makeStore($seller);

        $this->expectException(\Throwable::class);

        // stores.user_id carries a unique key, so a second store is rejected.
        $this->makeStore($seller, ['store_code' => 'DUP002', 'slug' => 'toko-dua']);
    }
}
