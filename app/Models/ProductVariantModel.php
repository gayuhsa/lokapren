<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Priced size/grade combinations ("Ukuran L (35x28 cm)", "Kolektor XL").
 *
 * A variant belongs to a product, which belongs to a seller. `product_id` is
 * writable here because the owning seller is resolved from the product in the
 * service layer; access is still enforced through `ProductModel::ownedBy()`.
 */
class ProductVariantModel extends BaseModel
{
    protected $table = 'product_variants';

    protected $returnType = 'array';

    /**
     * `price` and `stock` are server-owned. A seller-entered price is treated
     * as a proposal and confirmed before it reaches this column.
     */
    protected $allowedFields = [
        'variant_code',
        'sku',
        'label',
        'weight_gram',
        'is_default',
        'is_active',
        'position',
    ];

    protected array $casts = [
        'price'       => '?int',
        'stock'       => 'int',
        'weight_gram' => '?int',
        'is_default'  => 'bool',
        'is_active'   => 'bool',
        'position'    => 'int',
    ];

    protected $validationRules = [
        'variant_code' => 'required|max_length[32]',
        'sku'          => 'permit_empty|max_length[64]',
        'label'        => 'required|max_length[100]',
        'weight_gram'  => 'permit_empty|is_natural',
        'position'     => 'permit_empty|is_natural',
    ];

    /**
     * Sellable variants for a product, cheapest first.
     *
     * @return list<array<string, mixed>>
     */
    public function activeFor(int $productId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('product_id', $productId)
                ->where('is_active', 1)
                ->orderBy('is_default', 'DESC')
                ->orderBy('price', 'ASC')
        );
    }

    /**
     * The price to charge for a variant row: its own price when set, otherwise
     * the parent product's price.
     *
     * @param array<string, mixed> $variant
     * @param array<string, mixed> $product
     */
    public function effectivePrice(array $variant, array $product): int
    {
        return $variant['price'] !== null ? (int) $variant['price'] : (int) $product['price'];
    }
}