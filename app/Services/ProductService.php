<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductCategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

/**
 * Seller product management.
 *
 * Ownership is the first thing checked in every write: a product is loaded with
 * `seller_id = $sellerId` before anything is touched, so a seller editing
 * `/mitra/produk/12/ubah` cannot reach product 12 when it belongs to someone
 * else — the lookup simply returns null.
 *
 * Price and stock are absent from `ProductModel::$allowedFields` on purpose, so
 * they can only be written through here, after the controller has validated them
 * as non-negative integers. Nothing here ever accepts a price from an order or a
 * cart.
 */
class ProductService
{
    private ProductModel $products;
    private ProductVariantModel $variants;
    private ProductImageModel $images;
    private ProductCategoryModel $categories;
    private UploadService $uploads;

    public function __construct()
    {
        $this->products   = new ProductModel();
        $this->variants   = new ProductVariantModel();
        $this->images     = new ProductImageModel();
        $this->categories = new ProductCategoryModel();
        $this->uploads    = new UploadService();
    }

    /**
     * A seller's products, any status, newest first.
     *
     * @return array{products: list<array<string, mixed>>, total: int}
     */
    public function listForSeller(int $sellerId, int $limit = 50, int $offset = 0): array
    {
        return [
            'products' => $this->products->newRows(
                $this->products->newQuery()
                    ->select('products.*, product_categories.name AS category_name')
                    ->join('product_categories', 'product_categories.id = products.category_id', 'left')
                    ->where('seller_id', $sellerId)
                    ->orderBy('updated_at', 'DESC'),
                $limit,
                $offset
            ),
            'total' => $this->products->newQuery()->where('seller_id', $sellerId)->countAllResults(),
        ];
    }

    /**
     * One product with its variants and images, or null when it is not the
     * seller's.
     *
     * @return array<string, mixed>|null
     */
    public function findForEdit(int $productId, int $sellerId): ?array
    {
        $product = $this->products->newRow(
            $this->products->newQuery()
                ->where('id', $productId)
                ->where('seller_id', $sellerId)
        );

        if ($product === null) {
            return null;
        }

        $product['variants'] = $this->variants->activeFor($productId);
        $product['images']   = $this->images->galleryFor($productId);
        $product['category_name'] = $product['category_id'] === null
            ? null
            : ($this->categories->find($product['category_id'])['name'] ?? null);

        return $product;
    }

    /**
     * Create a product for the seller.
     *
     * `$data` is the controller's validated, whitelisted array. `price` and
     * `stock` are cast to integers here so the column always holds an integer
     * amount of rupiah.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string, id: int|null}
     */
    public function create(int $sellerId, array $data): array
    {
        $slug = $this->uniqueSlug($this->slugify((string) $data['name']));

        $productId = $this->products->insertRow([
            'seller_id'           => $sellerId,
            'category_id'         => $data['category_id'] ?? null,
            'product_code'        => $this->nextProductCode(),
            'slug'                => $slug,
            'name'                => trim((string) $data['name']),
            'subtitle'            => $this->text($data['subtitle'] ?? null),
            'summary'             => $this->text($data['summary'] ?? null),
            'description'         => $this->text($data['description'] ?? null),
            'story'               => $this->text($data['story'] ?? null),
            'material'            => $this->text($data['material'] ?? null),
            'finishing'           => $this->text($data['finishing'] ?? null),
            'price'               => max(0, (int) ($data['price'] ?? 0)),
            'stock'               => max(0, (int) ($data['stock'] ?? 0)),
            'low_stock_threshold' => max(0, (int) ($data['low_stock_threshold'] ?? 0)),
            'weight_gram'         => $this->nullableInt($data['weight_gram'] ?? null),
            'made_to_order'       => ($data['made_to_order'] ?? false) ? 1 : 0,
            'production_days'     => $this->nullableInt($data['production_days'] ?? null),
            // A new product starts as a draft; the seller publishes it explicitly.
            'status'              => ProductModel::STATUS_DRAFT,
            'is_active'           => 1,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Produk disimpan sebagai draf.', 'id' => $productId];
    }

    /**
     * Update one of the seller's products.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function update(int $productId, int $sellerId, array $data): array
    {
        if ($this->findForEdit($productId, $sellerId) === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        $set = [
            'category_id'         => $data['category_id'] ?? null,
            'name'                => trim((string) $data['name']),
            'subtitle'            => $this->text($data['subtitle'] ?? null),
            'summary'             => $this->text($data['summary'] ?? null),
            'description'         => $this->text($data['description'] ?? null),
            'story'               => $this->text($data['story'] ?? null),
            'material'            => $this->text($data['material'] ?? null),
            'finishing'           => $this->text($data['finishing'] ?? null),
            'price'               => max(0, (int) ($data['price'] ?? 0)),
            'stock'               => max(0, (int) ($data['stock'] ?? 0)),
            'low_stock_threshold' => max(0, (int) ($data['low_stock_threshold'] ?? 0)),
            'weight_gram'         => $this->nullableInt($data['weight_gram'] ?? null),
            'made_to_order'       => ($data['made_to_order'] ?? false) ? 1 : 0,
            'production_days'     => $this->nullableInt($data['production_days'] ?? null),
            'is_active'           => ($data['is_active'] ?? true) ? 1 : 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        $this->products->updateWhere($set, ['id' => $productId, 'seller_id' => $sellerId]);

        return ['ok' => true, 'message' => 'Produk diperbarui.'];
    }

    /**
     * Publish or unpublish a product the seller owns.
     *
     * `published_at` is stamped on the first publish and kept afterwards, so the
     * catalog's "newest" sort reflects when a seller first listed the piece.
     *
     * @return array{ok: bool, message: string}
     */
    public function publish(int $productId, int $sellerId): array
    {
        $product = $this->products->newRow(
            $this->products->newQuery()
                ->where('id', $productId)
                ->where('seller_id', $sellerId)
        );

        if ($product === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        if ($product['status'] === ProductModel::STATUS_PUBLISHED) {
            $this->products->updateWhere(
                ['status' => ProductModel::STATUS_DRAFT, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $productId, 'seller_id' => $sellerId]
            );

            return ['ok' => true, 'message' => 'Produk ditarik dari katalog.'];
        }

        $now = date('Y-m-d H:i:s');

        $this->products->updateWhere([
            'status'       => ProductModel::STATUS_PUBLISHED,
            'published_at' => $product['published_at'] ?? $now,
            'updated_at'   => $now,
        ], ['id' => $productId, 'seller_id' => $sellerId]);

        return ['ok' => true, 'message' => 'Produk ditayangkan di katalog.'];
    }

    /**
     * Soft delete one of the seller's products.
     *
     * Order items keep their own name/price snapshot, so historic invoices are
     * unaffected by the product going away.
     *
     * @return array{ok: bool, message: string}
     */
    public function delete(int $productId, int $sellerId): array
    {
        if ($this->products->newRow($this->products->newQuery()
            ->where('id', $productId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        $this->products->updateWhere([
            'deleted_at' => date('Y-m-d H:i:s'),
            'is_active'  => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $productId, 'seller_id' => $sellerId]);

        return ['ok' => true, 'message' => 'Produk dihapus.'];
    }

    /**
     * Add a variant to one of the seller's products.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function addVariant(int $productId, int $sellerId, array $data): array
    {
        if ($this->products->newRow($this->products->newQuery()
            ->where('id', $productId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        $label = trim((string) $data['label']);

        if ($label === '') {
            return ['ok' => false, 'message' => 'Nama pilihan wajib diisi.'];
        }

        $price = $data['price'] ?? null;

        $this->variants->insertRow([
            'product_id'  => $productId,
            'variant_code' => $this->nextVariantCode($productId, $label),
            'sku'         => $this->text($data['sku'] ?? null),
            'label'       => $label,
            // A null variant price means "same as the product price", which is
            // what `effectivePrice()` resolves at read time.
            'price'       => $price === null || $price === '' ? null : max(0, (int) $price),
            'stock'       => max(0, (int) ($data['stock'] ?? 0)),
            'weight_gram' => $this->nullableInt($data['weight_gram'] ?? null),
            'is_default'  => ($data['is_default'] ?? false) ? 1 : 0,
            'is_active'   => 1,
            'position'    => 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Pilihan ukuran ditambahkan.'];
    }

    /**
     * Delete a variant, checking that the variant belongs to the seller's
     * product first.
     *
     * @return array{ok: bool, message: string}
     */
    public function deleteVariant(int $variantId, int $productId, int $sellerId): array
    {
        $owned = $this->products->newRow($this->products->newQuery()
            ->where('id', $productId)
            ->where('seller_id', $sellerId));

        if ($owned === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        $variant = $this->variants->newRow($this->variants->newQuery()
            ->where('id', $variantId)
            ->where('product_id', $productId));

        if ($variant === null) {
            return ['ok' => false, 'message' => 'Pilihan ukuran tidak ditemukan.'];
        }

        $this->variants->updateWhere(
            ['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $variantId, 'product_id' => $productId]
        );

        return ['ok' => true, 'message' => 'Pilihan ukuran dihapus.'];
    }

    /**
     * Attach an uploaded image to the seller's product.
     *
     * The file itself is written by `UploadService` (extension, MIME and size
     * checked, name generated), and only the resulting relative path reaches the
     * database.
     *
     * @return array{ok: bool, message: string}
     */
    public function addImage(int $productId, int $sellerId, $file): array
    {
        if ($this->products->newRow($this->products->newQuery()
            ->where('id', $productId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Produk tidak ditemukan.'];
        }

        $stored = $this->uploads->store($file, 'products', 'image');

        if ($stored === null) {
            return ['ok' => false, 'message' => 'Gambar tidak valid. Maksimal 5 MB, format JPG, PNG, atau WEBP.'];
        }

        $existing = $this->images->galleryFor($productId);
        $isFirst  = $existing === [];

        $this->images->insertRow([
            'product_id' => $productId,
            'file_path'  => $stored['path'],
            'thumb_path' => $stored['path'],
            'alt_text'   => null,
            // The first image becomes the product cover.
            'is_primary' => $isFirst ? 1 : 0,
            'position'   => count($existing),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Gambar ditambahkan.'];
    }

    /**
     * Remove an image from the seller's product and delete the stored file.
     *
     * @return array{ok: bool, message: string}
     */
    public function deleteImage(int $imageId, int $sellerId): array
    {
        $image = $this->images->newRow($this->images->newQuery()->where('id', $imageId));

        if ($image === null) {
            return ['ok' => false, 'message' => 'Gambar tidak ditemukan.'];
        }

        $productId = (int) $image['product_id'];

        if ($this->products->newRow($this->products->newQuery()
            ->where('id', $productId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Gambar tidak ditemukan.'];
        }

        $this->images->newQuery()->where('id', $imageId)->delete();

        // The file lives outside the web root, so removing the row alone would
        // leave an orphan on disk.
        $this->uploads->delete($image['file_path']);
        $this->uploads->delete($image['thumb_path']);

        // Never leave a product without a cover.
        if ((int) $image['is_primary'] === 1) {
            $this->images->clearPrimary($productId);
        }

        return ['ok' => true, 'message' => 'Gambar dihapus.'];
    }

    /**
     * Categories for the product form's select box.
     *
     * @return list<array<string, mixed>>
     */
    public function categoryOptions(): array
    {
        return $this->categories->orderBy('position')->orderBy('name')->findAll();
    }

    /**
     * A slug that is not already taken, suffixed when needed.
     */
    private function uniqueSlug(string $base): string
    {
        $base = $base === '' ? 'kriya' : $base;
        $slug = $base;
        $n    = 2;

        while ($this->products->newRow($this->products->newQuery()->where('slug', $slug)) !== null) {
            $slug = $base . '-' . $n;
            $n++;
        }

        return $slug;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        // Keep letters and digits for Indonesian text, which is plain ASCII in
        // practice, and collapse everything else into a single separator.
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * A product code the seller never has to fill in.
     */
    private function nextProductCode(): string
    {
        do {
            $code = 'KP-' . str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->products->newRow($this->products->newQuery()->where('product_code', $code)) !== null);

        return $code;
    }

    /**
     * A variant code unique across the table, built from the product and label.
     */
    private function nextVariantCode(int $productId, string $label): string
    {
        $base = 'P' . $productId . '-' . strtoupper($this->slugify($label));
        $base = substr($base, 0, 26);
        $code = $base;
        $n    = 2;

        while ($this->variants->newRow($this->variants->newQuery()->where('variant_code', $code)) !== null) {
            $code = substr($base, 0, 26 - strlen((string) $n)) . '-' . $n;
            $n++;
        }

        return $code;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
