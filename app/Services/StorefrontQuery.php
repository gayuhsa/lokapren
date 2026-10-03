<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Everything the public storefront of one store shows.
 *
 * Aggregates come from the tables that already hold the truth — completed
 * orders for sold counts, product_reviews for ratings, messages for response
 * speed — so a store page can never advertise numbers the order and review
 * tables do not support.
 */
final class StorefrontQuery
{
    private const BOROBUDUR = ['lat' => -7.6079, 'lng' => 110.2038];

    private const RESPONSE_SAMPLE = 100;

    private const EARTH_KM = 6371.0088;

    private const COVER_DIR = 'assets/img/kriya/store/';

    private const COVER_FALLBACK = self::COVER_DIR . 'store-cover.svg';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly CatalogQuery $catalog,
    ) {}

    /**
     * The store behind a slug, or null when it is unknown or not public.
     *
     * Only a verified store gets a storefront, which is the same rule the
     * catalogue applies before it lists a store's works. Without it the check
     * would be bypassable through a second URL.
     *
     * @return array<string, mixed>|null
     */
    public function profile(string $slug): ?array
    {
        $store = $this->db->table('stores s')
            ->select('s.*, u.username AS owner_username')
            ->join('users u', 'u.id = s.user_id')
            ->where('s.slug', $slug)
            ->where('s.is_active', true)
            ->where('s.verification_status', 'verified')
            ->get()
            ->getRowArray();

        if ($store === null) {
            return null;
        }

        $id          = (int) $store['id'];
        $productIds  = $this->productIds($id);
        $sold        = $this->soldCounts($productIds);
        $ratings     = $this->ratingCounts($productIds);
        $coordinates = $this->coordinates($store);

        $store['product_count']  = count($productIds);
        $store['sold_count']     = array_sum($sold);
        $store['review_count']   = array_sum(array_column($ratings, 'total'));
        $store['rating_average'] = $this->weightedAverage($ratings);
        $store['hours']          = StoreHours::describe($store['opening_hours'] ?? null);
        $store['distance_km']    = $coordinates;
        $store['response']       = $this->responseSpeed($id);
        $store['categories']     = $this->categories($id);
        $store['materials']      = $this->attributes($productIds, ['material']);
        $store['craft']          = $this->attributes($productIds, ['teknik', 'finishing']);
        $store['origins']        = $this->attributes($productIds, ['origin']);
        $store['documentary']    = $this->documentary($id);
        $store['cover']          = $this->cover($store);

        return $store;
    }

    /**
     * The storefront banner image: whatever the store uploaded, otherwise the
     * drawn placeholder that matches its slug, otherwise the shared one.
     *
     * @param array<string, mixed> $store
     */
    private function cover(array $store): string
    {
        $uploaded = trim((string) ($store['cover_url'] ?? ''));

        if ($uploaded !== '') {
            return $uploaded;
        }

        $slug     = (string) $store['slug'];
        $candidate = self::COVER_DIR . $slug . '.svg';

        return is_file(FCPATH . $candidate) ? $candidate : self::COVER_FALLBACK;
    }

    /**
     * A store's own products, for the curated grid.
     *
     * Reuses the catalogue query so the storefront applies exactly the same
     * visibility rules, price floor and image selection as /marketplace.
     *
     * @return list<array<string, mixed>>
     */
    public function catalogue(string $storeSlug, string $categorySlug = '', int $limit = 9): array
    {
        $result = $this->catalog->paginate([
            'store'    => $storeSlug,
            'category' => $categorySlug,
            'sort'     => 'terbaru',
        ], $limit);

        $products = $result['products'];

        if ($products === []) {
            return [];
        }

        $ids     = array_column($products, 'id');
        $sold    = $this->soldCounts($ids);
        $ratings = $this->ratingCounts($ids);

        foreach ($products as &$product) {
            $id            = (int) $product['id'];
            $product['sold_count']   = $sold[$id] ?? 0;
            $product['rating_count'] = (int) ($ratings[$id]['total'] ?? 0);
            $product['rating_average'] = round((float) ($ratings[$id]['average'] ?? 0), 1);
        }

        return $products;
    }

    /**
     * @return list<int>
     */
    private function productIds(int $storeId): array
    {
        return array_map('intval', array_column(
            $this->db->table('products')
                ->select('id')
                ->where('store_id', $storeId)
                ->where('status', 'active')
                ->get()
                ->getResultArray(),
            'id',
        ));
    }

    /**
     * Units sold per product, counting completed orders only.
     *
     * @param list<int> $productIds
     *
     * @return array<int, int>
     */
    private function soldCounts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = $this->db->table('order_items')
            ->select('product_id, SUM(qty) AS sold')
            ->join('orders', 'orders.id = order_items.order_id')
            ->whereIn('order_items.product_id', $productIds)
            ->where('orders.status', 'completed')
            ->groupBy('product_id')
            ->get()
            ->getResultArray();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['product_id']] = (int) $row['sold'];
        }

        return $counts;
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<int, array{total: int, average: float}>
     */
    private function ratingCounts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = $this->db->table('product_reviews')
            ->select('product_id, COUNT(*) AS total, AVG(rating) AS average')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->get()
            ->getResultArray();

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['product_id']] = [
                'total'   => (int) $row['total'],
                'average' => (float) $row['average'],
            ];
        }

        return $counts;
    }

    /**
     * Averages ratings by review count, so a store is not flattered by one
     * five-star review.
     *
     * @param array<int, array{total: int, average: float}> $ratings
     */
    private function weightedAverage(array $ratings): float
    {
        $total   = 0;
        $weighted = 0.0;

        foreach ($ratings as $rating) {
            $total     += $rating['total'];
            $weighted += $rating['average'] * $rating['total'];
        }

        return $total === 0 ? 0.0 : round($weighted / $total, 1);
    }

    /**
     * Straight-line distance to Candi Borobudur, the landmark every Magelang
     * workshop is described against. Null when the store has no coordinates.
     *
     * @param array<string, mixed> $store
     */
    private function coordinates(array $store): ?float
    {
        if ($store['lat'] === null || $store['lng'] === null) {
            return null;
        }

        $lat1 = deg2rad((float) $store['lat']);
        $lat2 = deg2rad(self::BOROBUDUR['lat']);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad(self::BOROBUDUR['lng'] - (float) $store['lng']);

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return round(self::EARTH_KM * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }

    /**
     * How quickly the seller answers, measured from the chat itself.
     *
     * Only the most recent threads are read: a response average over the whole
     * history would be dominated by conversations from years ago.
     *
     * @return array{asked: int, answered: int, rate: int, minutes: int|null}
     */
    private function responseSpeed(int $storeId): array
    {
        $threads = array_map('intval', array_column(
            $this->db->table('conversations')
                ->select('id')
                ->where('store_id', $storeId)
                ->orderBy('last_message_at', 'DESC')
                ->get(self::RESPONSE_SAMPLE)
                ->getResultArray(),
            'id',
        ));

        if ($threads === []) {
            return ['asked' => 0, 'answered' => 0, 'rate' => 0, 'minutes' => null];
        }

        $messages = $this->db->table('messages')
            ->select('conversation_id, sender_role, created_at')
            ->whereIn('conversation_id', $threads)
            ->orderBy('conversation_id', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        $asked     = 0;
        $answered  = 0;
        $durations = [];
        $waiting   = [];

        foreach ($messages as $message) {
            $thread = (int) $message['conversation_id'];

            if ($message['sender_role'] === 'customer') {
                $asked++;
                $waiting[$thread] = strtotime((string) $message['created_at']) ?: null;

                continue;
            }

            $askedAt = $waiting[$thread] ?? null;
            unset($waiting[$thread]);

            if ($askedAt !== null && $askedAt > 0) {
                $answered++;
                $durations[] = max(0, (int) round((strtotime((string) $message['created_at']) - $askedAt) / 60));
            }
        }

        return [
            'asked'    => $asked,
            'answered' => $answered,
            'rate'     => $asked === 0 ? 0 : (int) round($answered / $asked * 100),
            'minutes'  => $durations === [] ? null : (int) round(array_sum($durations) / count($durations)),
        ];
    }

    /**
     * Categories this store actually sells, for the filter chips.
     *
     * @return list<array<string, mixed>>
     */
    private function categories(int $storeId): array
    {
        return $this->db->table('categories c')
            ->select('c.name, c.slug, COUNT(p.id) AS product_count')
            ->join('products p', 'p.category_id = c.id')
            ->where('p.store_id', $storeId)
            ->where('p.status', 'active')
            ->where('c.is_active', true)
            ->groupBy('c.id, c.name, c.slug')
            ->orderBy('product_count', 'DESC')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Distinct values across the requested attribute keys, most used first.
     *
     * @param list<int>  $productIds
     * @param list<string> $keys
     *
     * @return list<array{label: string, count: int}>
     */
    private function attributes(array $productIds, array $keys): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = $this->db->table('products')
            ->select('attributes')
            ->whereIn('id', $productIds)
            ->get()
            ->getResultArray();

        $tally = [];

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row['attributes'], true);

            if (! is_array($decoded)) {
                continue;
            }

            foreach ($keys as $key) {
                $value = $decoded[$key] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    $label = trim($value);
                    $tally[$label] = ($tally[$label] ?? 0) + 1;
                }
            }
        }

        arsort($tally);

        $values = [];

        foreach ($tally as $label => $count) {
            $values[] = ['label' => (string) $label, 'count' => $count];
        }

        return $values;
    }

    /**
     * The store's latest published documentary, if it published one.
     *
     * @return array<string, mixed>|null
     */
    private function documentary(int $storeId): ?array
    {
        return $this->db->table('store_posts')
            ->where('store_id', $storeId)
            ->where('post_type', 'documentary')
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->get(1)
            ->getRowArray();
    }
}