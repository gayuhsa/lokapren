<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StoreModel;
use CodeIgniter\Shield\Entities\User;
use Throwable;

/**
 * Resolves the caller's store and answers ownership questions.
 *
 * Permissions answer "may this role act on stores at all". They deliberately
 * do not answer "which store" — that is always derived from the authenticated
 * user here, never from a submitted store_id. Seller A therefore cannot reach
 * Seller B's rows even when both hold the same permission.
 */
final class StoreAccess
{
    public function __construct(private readonly StoreModel $stores = new StoreModel())
    {
    }

    /**
     * The caller's own store row, or null when they do not own one.
     *
     * @return array<string, mixed>|null
     */
    public function forUser(?User $user): ?array
    {
        if ($user === null || $user->id === null) {
            return null;
        }

        // Stores are 1:1 with sellers via the unique user_id column.
        return $this->stores->find((int) $user->id, 'user_id');
    }

    public function idForUser(?User $user): ?int
    {
        $store = $this->forUser($user);

        return $store === null ? null : (int) $store['id'];
    }

    public function ownsStore(?User $user, ?int $storeId): bool
    {
        $owned = $this->idForUser($user);

        return $owned !== null && $storeId !== null && $owned === $storeId;
    }

    /**
     * Throws when the caller may not act on the given store.
     *
     * @throws StoreAccessDenied
     */
    public function assertStore(?User $user, ?int $storeId, string $permission): array
    {
        if ($user === null || $storeId === null) {
            throw StoreAccessDenied::notAuthenticated();
        }

        if (! $user->can($permission)) {
            throw StoreAccessDenied::missingPermission($permission);
        }

        $store = $this->stores->find($storeId);

        if ($store === null) {
            throw StoreAccessDenied::unknownStore();
        }

        // Admins moderate across every store; sellers are confined to their own.
        if (! $this->ownsStore($user, $storeId) && ! $user->can('users.manage')) {
            throw StoreAccessDenied::notOwner();
        }

        return $store;
    }

    /**
     * Same as assertStore() but for a product row, resolved through its store.
     *
     * @param array<string, mixed> $product
     *
     * @throws StoreAccessDenied
     * @throws Throwable
     */
    public function assertProduct(?User $user, array $product, string $permission): array
    {
        return $this->assertStore($user, (int) $product['store_id'], $permission);
    }
}
