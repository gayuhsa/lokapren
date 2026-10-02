<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CatalogQuery;
use App\Services\Rupiah;

/**
 * The catalogue listing page.
 *
 * Reads every filter, category and product from the database through
 * CatalogQuery. An unknown category or sort key is treated as absent rather
 * than echoed back, so the page can never render an unfiltered result set and
 * call it filtered.
 */
class Marketplace extends BaseController
{
    private const PER_PAGE = 12;

    public function index(): string
    {
        $catalog = new CatalogQuery($this->db);

        $search = trim((string) $this->request->getGet('q'));
        $sort   = (string) $this->request->getGet('sort');
        $page   = (int) $this->request->getGet('page');

        if (! CatalogQuery::isSortable($sort)) {
            $sort = '';
        }

        $category = $this->knownCategory($catalog, (string) $this->request->getGet('category'));
        $store    = $this->knownStore($catalog, (string) $this->request->getGet('store'));

        $min = Rupiah::parse($this->request->getGet('min'));
        $max = Rupiah::parse($this->request->getGet('max'));

        $filters = [
            'search'   => $search,
            'category' => $category,
            'store'    => $store,
            'min'      => $min,
            'max'      => $max,
            'sort'     => $sort,
        ];

        $result = $catalog->paginate($filters, self::PER_PAGE, $page);

        return view('marketplace', [
            'products'      => $result['products'],
            'featured'      => $catalog->featured(4),
            'categories'    => $catalog->categories(),
            'total'         => $result['total'],
            'pages'         => $result['pages'],
            'page'          => $result['page'],
            'bounds'        => $catalog->priceBounds(),
            'sortOptions'   => CatalogQuery::sortOptions(),
            'filters'       => $filters,
            'search'        => $search,
            'sort'          => $sort,
            'category'      => $category,
            'store'         => $store,
            'min'           => $min,
            'max'           => $max,
            'hasFilters'    => $search !== '' || $category !== '' || $store !== ''
                || $min !== null || $max !== null || $sort !== '',
        ]);
    }

    /**
     * Resolves a category slug, ignoring anything not currently browsable.
     */
    private function knownCategory(CatalogQuery $catalog, string $slug): string
    {
        foreach ($catalog->categories() as $category) {
            if ($category['slug'] === $slug) {
                return (string) $category['slug'];
            }
        }

        return '';
    }

    /**
     * Resolves a store slug among verified stores.
     */
    private function knownStore(CatalogQuery $catalog, string $slug): string
    {
        if ($slug === '') {
            return '';
        }

        $exists = $this->db->table('stores')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->get()
            ->getRowArray();

        return $exists === null ? '' : $slug;
    }
}