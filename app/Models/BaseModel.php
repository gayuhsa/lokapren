<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Shared base for every marketplace model.
 *
 * Two deliberate choices live here:
 *
 * 1. `$dateFormat = 'datetime'` so `created_at`/`updated_at` match the
 *    `DATETIME` columns written by the migrations.
 * 2. `$useSoftDeletes` is enabled per model rather than here, because only some
 *    tables actually carry a `deleted_at` column.
 */
abstract class BaseModel extends Model
{
    /**
     * `created_at` / `updated_at` are real DATETIME columns.
     */
    protected $dateFormat = 'datetime';

    /**
     * Domain models return arrays: views and JSON responses read them directly,
     * and `casts` still apply. Call `->asObject()` per query when an object is
     * preferred.
     */
    protected $returnType = 'array';

    /**
     * Refuse to write a column that is not listed in `$allowedFields`.
     *
     * This is the first line of defence against mass assignment: a controller
     * that forwards `$this->request->getPost()` wholesale still cannot set
     * `seller_id`, `price`, `grand_total`, `status` or any other server-owned
     * column, because those are deliberately excluded from `$allowedFields`
     * and must be set by application code.
     */
    protected $protectFields = true;

    /**
     * Apply this model's `$casts` to a row fetched straight from the builder.
     *
     * `find()`, `first()` and `findAll()` already do this. It is needed when a
     * query is composed on `$this->builder()` directly — for example the
     * ownership helpers in `HasSellerOwnership` — and the caller wants the same
     * PHP types a normal find would return.
     *
     * @param array<string, mixed>|null $row
     *
     * @return array<string, mixed>|null
     */
    protected function castRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        // The converter only exists when the model declares casts.
        return $this->converter === null ? $row : $this->converter->fromDataSource($row);
    }

    /**
     * A clean builder for this model's table.
     *
     * `builder()` returns the model's *shared* builder, so a `select()`,
     * `groupBy()` or `where()` left on it leaks into the next unrelated query —
     * including `update()` and `delete()`, which reuse the same conditions.
     * Every scope helper starts from here instead:
     *
     *     return $this->newQuery()
     *         ->where('product_id', $productId)
     *         ->get()
     *         ->getResultArray();
     *
     * The soft-delete filter is applied here because a raw builder query does
     * not get it for free the way `findAll()` does.
     */
    protected function newQuery(): BaseBuilder
    {
        $this->resetQuery();

        $builder = $this->builder();

        if ($this->useSoftDeletes) {
            $builder->where($this->deletedField, null);
        }

        return $builder;
    }

    /**
     * Insert a row written entirely by trusted application code.
     *
     * `$allowedFields` exists to reject *request* data, so a table with
     * server-owned columns can never be populated through `insert()`. Server
     * code fills those columns explicitly through here instead.
     *
     * @param array<string, mixed> $data
     *
     * @return int The new primary key.
     */
    public function insertRow(array $data): int
    {
        $this->newQuery()->insert($data);

        return (int) $this->db->insertID();
    }

    /**
     * Update rows matching a simple `column => value` map.
     *
     * Starts from `newQuery()` so an earlier scope cannot add conditions to
     * this write by accident.
     *
     * @param array<string, mixed> $set
     * @param array<string, mixed> $where
     */
    protected function updateWhere(array $set, array $where): bool
    {
        $builder = $this->newQuery()->where($where);

        return (bool) $builder->update($set);
    }

    /**
     * Execute a composed query and return every row with casts applied.
     *
     * @return list<array<string, mixed>>
     */
    protected function newRows(?BaseBuilder $builder = null, ?int $limit = null, ?int $offset = null): array
    {
        $builder ??= $this->newQuery();

        if ($limit !== null) {
            $builder->limit($limit, $offset ?? 0);
        }

        $rows = [];

        foreach ($builder->get()->getResultArray() as $row) {
            $rows[] = $this->castRow($row);
        }

        return $rows;
    }

    /**
     * Execute a composed query and return the first row with casts applied.
     *
     * @return array<string, mixed>|null
     */
    protected function newRow(?BaseBuilder $builder = null): ?array
    {
        return $this->castRow(($builder ?? $this->newQuery())->get()->getRowArray());
    }
}