<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CatalogQuery;
use App\Services\StorefrontQuery;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * The public storefront of one store: profile, curated catalogue, chat and
 * contact entry points.
 *
 * Everything shown here is public information about a verified marketplace
 * seller, so the route needs no session. Store ownership questions belong to
 * the seller area, not this page.
 */
class Storefront extends BaseController
{
    private const CATALOGUE_SIZE = 9;

    public function show(string $slug): string
    {
        $storefront = new StorefrontQuery($this->db, new CatalogQuery($this->db));

        $store = $storefront->profile($slug);

        if ($store === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $category = $this->knownCategory($store, (string) $this->request->getGet('kategori'));

        return view('storefront', [
            'store'      => $store,
            'products'   => $storefront->catalogue($store['slug'], $category, self::CATALOGUE_SIZE),
            'category'   => $category,
            'categories' => $store['categories'],
        ]);
    }

    /**
     * Keeps only categories this store actually sells, so an unknown slug in
     * the query string cannot widen the grid beyond the storefront.
     *
     * @param array<string, mixed> $store
     */
    private function knownCategory(array $store, string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        foreach ($store['categories'] as $category) {
            if ($category['slug'] === $slug) {
                return $slug;
            }
        }

        return '';
    }
}