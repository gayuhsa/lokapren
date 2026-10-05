<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Ownership scoping for seller-owned resources.
 *
 * AGENTS.md requires that "Sellers may only manage their own products, blogs,
 * profile/location data, and seller resources" and that resource ownership is
 * verified server-side. Every seller-owned table carries a column referencing
 * `users.id`, and no such column is ever part of a model's `$allowedFields` —
 * the owning id is injected by application code from the authenticated Shield
 * user, never from the request.
 *
 * These helpers make the ownership check explicit at the call site instead of
 * relying on a controller to remember it:
 *
 *     $products = $productModel->ownedBy($sellerId)->findAll();
 *     $product  = $productModel->findOwnedBy($id, $sellerId); // null when not the owner
 */
trait HasSellerOwnership
{
    /**
     * Column holding the owning user id.
     *
     * Override in a model whose table names the column differently — the
     * storefront uses `user_id` rather than `seller_id`.
     */
    protected function sellerIdField(): string
    {
        return 'seller_id';
    }

    /**
     * Scope the next query to rows owned by `$sellerId`.
     *
     * Returns the model itself so `->findAll()`, `->first()` and
     * `->countAllResults()` can be chained and still apply soft-delete
     * filtering and casts.
     *
     * The query builder is shared, so call `resetQuery()` (or use a fresh
     * model instance) before reusing it for a different owner.
     */
    public function ownedBy(int $sellerId): static
    {
        $this->builder()->where($this->sellerIdField(), $sellerId);

        return $this;
    }

    /**
     * Find a single row by primary key, but only when `$sellerId` owns it.
     *
     * Returns null for a row that exists but belongs to someone else, which is
     * what lets a controller answer 404 instead of 403 and avoid confirming
     * that another seller's data exists.
     *
     * @param int|string $id
     *
     * @return array<string, mixed>|null
     */
    public function findOwnedBy($id, int $sellerId): ?array
    {
        $builder = $this->newQuery()
            ->where($this->primaryKey, $id)
            ->where($this->sellerIdField(), $sellerId);

        return $this->newRow($builder);
    }

    /**
     * Delete a row by primary key only when `$sellerId` owns it.
     *
     * @param int|string $id
     */
    public function deleteOwnedBy($id, int $sellerId): bool
    {
        if ($this->findOwnedBy($id, $sellerId) === null) {
            return false;
        }

        return (bool) $this->delete($id);
    }
}