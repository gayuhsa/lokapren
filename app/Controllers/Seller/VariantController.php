<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;

/**
 * Variant ("pilihan ukuran") management for one of the seller's products.
 *
 * Both routes carry the product id, and `ProductService` re-checks that the
 * product belongs to the signed-in seller before a variant is written, so the
 * id pair in the URL is validated rather than trusted.
 */
class VariantController extends BaseController
{
    public function index(int $productId)
    {
        $sellerId = $this->requireUserId();
        $product  = $this->productService->findForEdit($productId, $sellerId);

        if ($product === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('seller/products/variants', [
            'title'      => 'Pilihan Ukuran',
            'product'    => $product,
            'cart_count' => $this->cartCount(),
        ]);
    }

    public function store(int $productId)
    {
        $sellerId = $this->requireUserId();

        $rules = [
            'label'      => 'required|max_length[100]',
            'sku'        => 'permit_empty|max_length[64]',
            'price'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'stock'      => 'permit_empty|numeric|greater_than_equal_to[0]',
            'weight_gram' => 'permit_empty|numeric|greater_than_equal_to[0]',
            'is_default' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()
                ->route('seller_variants', [$productId])
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $result = $this->productService->addVariant($productId, $sellerId, [
            'label'       => (string) $this->request->getPost('label'),
            'sku'         => (string) $this->request->getPost('sku'),
            // An empty price means "inherit the product price", which is
            // resolved at read time rather than copied into the row.
            'price'       => $this->request->getPost('price'),
            'stock'       => (int) ($this->request->getPost('stock') ?: 0),
            'weight_gram' => $this->request->getPost('weight_gram') ?: null,
            'is_default'  => $this->request->getPost('is_default') === '1',
        ]);

        return redirect()
            ->route('seller_variants', [$productId])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(int $productId, int $variantId)
    {
        $result = $this->productService->deleteVariant($variantId, $productId, $this->requireUserId());

        return redirect()
            ->route('seller_variants', [$productId])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
