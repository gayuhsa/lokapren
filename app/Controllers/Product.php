<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CatalogQuery;
use CodeIgniter\Exceptions\PageNotFoundException;

class Product extends BaseController
{
    public function show(string $slug): string
    {
        $catalog = new CatalogQuery($this->db);

        $product = $catalog->detail($slug);

        if ($product === null) {
            $product = $this->detailById($catalog, $slug);
        }

        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('product', [
            'product'  => $product,
            'reviews'  => $catalog->reviews((int) $product['id']),
            'related'  => $this->related($catalog, $product),
        ]);
    }

    /**
     * Falls back to an id lookup, so /product/9 keeps working.
     *
     * @return array<string, mixed>|null
     */
    private function detailById(CatalogQuery $catalog, string $reference): ?array
    {
        if (! ctype_digit($reference)) {
            return null;
        }

        $row = $this->db->table('products')
            ->select('slug')
            ->where('id', (int) $reference)
            ->where('status', 'active')
            ->get()
            ->getRowArray();

        return $row === null ? null : $catalog->detail((string) $row['slug']);
    }

    /**
     * Other products in the same category, for the "see also" strip.
     *
     * @param array<string, mixed> $product
     *
     * @return list<array<string, mixed>>
     */
    private function related(CatalogQuery $catalog, array $product): array
    {
        $related = $catalog->paginate([
            'category' => (string) ($product['category_slug'] ?? ''),
        ], 12);

        $siblings = array_values(array_filter(
            $related['products'],
            static fn (array $row): bool => (int) $row['id'] !== (int) $product['id'],
        ));

        return array_slice($siblings, 0, 4);
    }
}
