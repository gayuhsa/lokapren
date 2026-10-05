<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProductCategoryModel;
use App\Models\SellerDailyStatModel;
use App\Models\SellerProfileModel;

/**
 * The public catalog and product detail pages.
 *
 * Reads only. Nothing on these pages trusts a client-supplied price or seller
 * id: the filters are whitelisted in `CatalogService` and every figure rendered
 * comes from a product row.
 */
class CatalogController extends BaseController
{
    public function index()
    {
        $filters = [
            'q'          => (string) $this->request->getGet('q'),
            'category'   => (string) $this->request->getGet('category'),
            'seller'     => (string) $this->request->getGet('seller'),
            'min_price'  => (string) $this->request->getGet('min_price'),
            'max_price'  => (string) $this->request->getGet('max_price'),
            'sort'       => (string) $this->request->getGet('sort'),
            'page'       => (string) $this->request->getGet('page'),
        ];

        $result = $this->catalogService->search($filters);

        return view('catalog/index', [
            'title'      => 'Katalog Kriya Magelang',
            'result'     => $result,
            'categories' => (new ProductCategoryModel())->withProductCounts(),
            'sellers'    => $this->highlightedSellers(),
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Product detail, including variants, gallery, seller card and reviews.
     */
    public function product(string $slug)
    {
        $product = $this->catalogService->findPublishedBySlug($slug);

        if ($product === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // A view is recorded server-side; the counter is never taken from the
        // request and the daily rollup feeds the seller's own dashboard.
        $sellerId = (int) $product['seller_id'];

        (new SellerDailyStatModel())->increment($sellerId, date('Y-m-d'), [
            'product_view_count' => 1,
        ]);

        return view('catalog/show', [
            'title'         => $product['name'],
            'product'       => $product,
            'cart_count'    => $this->cartCount(),
            'unread_chat'   => $this->userId() === null ? 0 : $this->unreadChatCount(),
        ]);
    }

    /**
     * Sanggar featured on the catalog's hero and sidebar.
     *
     * @return list<array<string, mixed>>
     */
    private function highlightedSellers(): array
    {
        $sellers = new SellerProfileModel();

        return $sellers->newRows(
            $sellers->newQuery()
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->where('is_verified', 1)
                ->orderBy('rating_average', 'DESC')
                ->orderBy('rating_count', 'DESC'),
            6
        );
    }
}
