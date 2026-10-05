<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;

/**
 * Seller product management.
 *
 * The whole group sits behind `role:seller`, and the seller id is read from
 * Shield — never from the request — so every lookup in `ProductService` is
 * already scoped to the signed-in seller by the time a product is touched.
 */
class ProductController extends BaseController
{
    public function index()
    {
        $sellerId = $this->requireUserId();
        $page     = max(1, (int) $this->request->getGet('page'));
        $limit    = 20;

        $result = $this->productService->listForSeller($sellerId, $limit, ($page - 1) * $limit);

        return view('seller/products/index', [
            'title'      => 'Produk Saya',
            'products'   => $result['products'],
            'total'      => $result['total'],
            'page'       => $page,
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function create()
    {
        return view('seller/products/form', [
            'title'      => 'Tambah Produk',
            'product'    => null,
            'categories' => $this->productService->categoryOptions(),
            'action'     => route_to('seller_product_store'),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function edit(int $id)
    {
        $sellerId = $this->requireUserId();
        $product  = $this->productService->findForEdit($id, $sellerId);

        // Another seller's product is a 404, not a 403: the page must not
        // confirm that the id exists.
        if ($product === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('seller/products/form', [
            'title'      => 'Ubah Produk',
            'product'    => $product,
            'categories' => $this->productService->categoryOptions(),
            'action'     => route_to('seller_product_update', $id),
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function store()
    {
        $sellerId = $this->requireUserId();
        $data     = $this->validated();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->productService->create($sellerId, $data);

        return redirect()
            ->route('seller_products')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function update(int $id)
    {
        $sellerId = $this->requireUserId();
        $data     = $this->validated();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $result = $this->productService->update($id, $sellerId, $data);

        return redirect()
            ->route('seller_products')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Publish or pull a product from the catalog.
     */
    public function publish(int $id)
    {
        $result = $this->productService->publish($id, $this->requireUserId());

        return redirect()
            ->route('seller_products')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(int $id)
    {
        $result = $this->productService->delete($id, $this->requireUserId());

        return redirect()
            ->route('seller_products')
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Upload one product photo.
     *
     * The file is validated by `UploadService` (magic-byte MIME check, size and
     * dimension limits, generated name) before any database row is written.
     */
    public function addImage(int $id)
    {
        $file = $this->request->getFile('photo');

        if ($file === null || ! $file->isValid()) {
            return redirect()
                ->route('seller_product_edit', [$id])
                ->with('error', 'Pilih file gambar yang valid.');
        }

        $result = $this->productService->addImage($id, $this->requireUserId(), $file);

        return redirect()
            ->route('seller_product_edit', [$id])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function deleteImage(int $id)
    {
        $result = $this->productService->deleteImage($id, $this->requireUserId());

        return redirect()
            ->back()
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Validate the product form.
     *
     * Only the fields the seller may set are read; `seller_id`, `status`,
     * `sold_count` and `rating_*` are never taken from the request.
     *
     * @return array<string, mixed>|null
     */
    private function validated(): ?array
    {
        $rules = [
            'name'                => 'required|max_length[180]',
            'subtitle'            => 'permit_empty|max_length[255]',
            'summary'             => 'permit_empty|max_length[500]',
            'description'         => 'permit_empty|max_length[5000]',
            'story'               => 'permit_empty|max_length[5000]',
            'material'            => 'permit_empty|max_length[150]',
            'finishing'           => 'permit_empty|max_length[150]',
            'category_id'         => 'permit_empty|is_natural_no_zero',
            'price'               => 'required|numeric|greater_than_equal_to[0]',
            'stock'               => 'permit_empty|numeric|greater_than_equal_to[0]',
            'low_stock_threshold' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'weight_gram'         => 'permit_empty|numeric|greater_than_equal_to[0]',
            'production_days'     => 'permit_empty|numeric|greater_than_equal_to[1]|less_than_equal_to[365]',
            'made_to_order'       => 'permit_empty|in_list[0,1]',
            'is_active'           => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return null;
        }

        $categoryId = $this->request->getPost('category_id');

        return [
            'name'                => (string) $this->request->getPost('name'),
            'subtitle'            => (string) $this->request->getPost('subtitle'),
            'summary'             => (string) $this->request->getPost('summary'),
            'description'         => (string) $this->request->getPost('description'),
            'story'               => (string) $this->request->getPost('story'),
            'material'            => (string) $this->request->getPost('material'),
            'finishing'           => (string) $this->request->getPost('finishing'),
            'category_id'         => ($categoryId === '' || $categoryId === null) ? null : (int) $categoryId,
            'price'               => (int) $this->request->getPost('price'),
            'stock'               => (int) ($this->request->getPost('stock') ?: 0),
            'low_stock_threshold' => (int) ($this->request->getPost('low_stock_threshold') ?: 0),
            'weight_gram'         => $this->request->getPost('weight_gram') ?: null,
            'production_days'     => $this->request->getPost('production_days') ?: null,
            'made_to_order'       => $this->request->getPost('made_to_order') === '1',
            'is_active'           => $this->request->getPost('is_active') !== '0',
        ];
    }
}
