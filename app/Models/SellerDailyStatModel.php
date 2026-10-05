<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Database\RawSql;
use CodeIgniter\I18n\Time;

/**
 * Per-seller daily dashboard rollup: visits, orders and revenue.
 *
 * Every column is server-owned. The dashboard reads this table; nothing writes
 * it from a request.
 */
class SellerDailyStatModel extends BaseModel
{
    protected $table = 'seller_daily_stats';

    protected $returnType = 'array';

    /**
     * Intentionally narrow: only `stat_date` is writable, so a counter can
     * never be reset by a client.
     */
    protected $allowedFields = [
        'stat_date',
    ];

    protected array $casts = [
        'visit_count'        => 'int',
        'visitor_count'      => 'int',
        'product_view_count' => 'int',
        'chat_started_count' => 'int',
        'order_count'        => 'int',
        'revenue_total'      => 'int',
        'completed_count'    => 'int',
    ];

    protected $validationRules = [
        'stat_date' => 'required|valid_date',
        'seller_id' => 'required|is_natural_no_zero',
    ];

    /**
     * Add to the counters for one seller-day, creating the row when absent.
     *
     * A single upsert does both halves so two concurrent hits on the same day
     * cannot lose a row or lose a count, and the arithmetic stays in SQL instead
     * of a read-modify-write in PHP.
     *
     * `updateFields()` carries `RawSql` expressions, which both MySQLi
     * (`ON DUPLICATE KEY UPDATE`) and SQLite (`ON CONFLICT DO UPDATE`) honour.
     *
     * @param array<string, int> $deltas
     */
    public function increment(int $sellerId, string $statDate, array $deltas = []): void
    {
        $allowed = [
            'visit_count', 'visitor_count', 'product_view_count',
            'chat_started_count', 'order_count', 'revenue_total',
            'completed_count',
        ];

        $insert = ['seller_id' => $sellerId, 'stat_date' => $statDate];
        $update = [];

        foreach ($allowed as $column) {
            $delta = (int) ($deltas[$column] ?? 0);

            if ($delta !== 0) {
                // A brand new day starts at zero rather than at the delta, so a
                // negative correction can never create a negative row.
                $insert[$column] = max(0, $delta);
                $update[$column] = new RawSql(sprintf('%s + %d', $column, $delta));
            }
        }

        if ($update === []) {
            return;
        }

        // `updateFields()` carries the `col + delta` expressions, and `upsert()` takes
        // the insert row: on a duplicate `(seller_id, stat_date)` the driver
        // applies those expressions, which is the atomic increment we need.
        $this->db->table($this->table)
            ->updateFields($update)
            ->upsert($insert);
    }

    /**
     * Today in the application timezone, as the rollup key.
     */
    public function today(): string
    {
        return Time::now(app_timezone())->toDateString();
    }

    /**
     * Stat rows for a seller across a date range, oldest first.
     *
     * @return list<array<string, mixed>>
     */
    public function range(int $sellerId, string $from, string $to): array
    {
        $builder = $this->newQuery()
            ->where('seller_id', $sellerId)
            ->where('stat_date >=', $from)
            ->where('stat_date <=', $to)
            ->orderBy('stat_date', 'ASC');

        return $this->newRows($builder);
    }

    /**
     * Totals for a seller across a date range.
     *
     * The dashboard shows a 30-day figure, which has to be the sum of every day
     * in the window rather than one arbitrary row.
     *
     * @return array<string, int>
     */
    public function totalsBetween(int $sellerId, string $from, string $to): array
    {
        $columns = [
            'visit_count',
            'visitor_count',
            'product_view_count',
            'chat_started_count',
            'order_count',
            'revenue_total',
            'completed_count',
        ];

        $select = [];

        foreach ($columns as $column) {
            $select[] = 'COALESCE(SUM(' . $column . '), 0) AS ' . $column;
        }

        $row = $this->newRow(
            $this->newQuery()
                ->select($select)
                ->where('seller_id', $sellerId)
                ->where('stat_date >=', $from)
                ->where('stat_date <=', $to)
        );

        $totals = [];

        foreach ($columns as $column) {
            $totals[$column] = (int) ($row[$column] ?? 0);
        }

        return $totals;
    }
}