<?php

declare(strict_types=1);

use App\Models\AddressModel;
use App\Models\BlogPostModel;
use App\Models\ProductModel;
use App\Models\SellerBusinessHourModel;
use App\Models\SellerProfileModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\MarketplaceFixtures;

/**
 * Ownership and isolation guarantees: sellers cannot touch each other's data;
 * customers cannot touch each other's private data.
 *
 * @internal
 */
final class OwnershipTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    public function testSellerCannotAccessOtherSellersProduct(): void
    {
        [$sellerA, $productA] = $this->makeProduct($this->makeSeller('sanggar-a')[0], $this->makeCategory('Ukir'));
        [$sellerB] = $this->makeSeller('sanggar-b');

        $productModel = new ProductModel();

        // Load as seller A: should find their own product.
        $owned = $productModel->findOwnedBy($productA, $sellerA);
        $this->assertNotNull($owned);
        $this->assertSame($productA, (int) $owned['id']);

        // Attempt to load as seller B: must return null (no 403 leak).
        $notOwned = $productModel->findOwnedBy($productA, $sellerB);
        $this->assertNull($notOwned);
    }

    public function testDeleteOwnedByOnlyDeletesWhenOwned(): void
    {
        [$sellerA, $productA] = $this->makeProduct($this->makeSeller('a')[0], $this->makeCategory('A'));
        [$sellerB] = $this->makeSeller('b');

        $productModel = new ProductModel();

        $this->assertFalse($productModel->deleteOwnedBy($productA, $sellerB));
        $this->assertNotNull($productModel->withDeleted()->find($productA));

        $this->assertTrue($productModel->deleteOwnedBy($productA, $sellerA));
        $this->assertNull($productModel->find($productA));
    }

    public function testBlogPostOwnershipScoped(): void
    {
        [$sellerA] = $this->makeSeller('a');
        [$sellerB] = $this->makeSeller('b');

        $blog = new BlogPostModel();

        $postId = $blog->insertRow([
            'seller_id'  => $sellerA,
            'title'      => 'Kisah Perajin',
            'slug'       => 'kisah-perajin-a',
            'excerpt'    => 'Tentang ukiran',
            'status'     => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotNull($blog->findOwnedBy($postId, $sellerA));
        $this->assertNull($blog->findOwnedBy($postId, $sellerB));

        $this->assertFalse($blog->publish($postId, $sellerB));
        $post = $blog->find($postId);
        $this->assertSame('draft', $post['status']);

        $this->assertTrue($blog->publish($postId, $sellerA));
        $post = $blog->find($postId);
        $this->assertSame(BlogPostModel::STATUS_PUBLISHED, $post['status']);
    }

    public function testSellerProfileOwnedByUserId(): void
    {
        [$sellerA, $profileA] = $this->makeSeller('a');
        [$sellerB] = $this->makeSeller('b');

        $profiles = new SellerProfileModel();

        $this->assertNotNull($profiles->findOwnedBy($profileA, $sellerA));
        $this->assertNull($profiles->findOwnedBy($profileA, $sellerB));
    }

    public function testAddressOwnershipScoped(): void
    {
        $userA = $this->makeUser('addr-a');
        $userB = $this->makeUser('addr-b');

        $addresses = new AddressModel();

        $id = $addresses->insertRow([
            'user_id'      => $userA,
            'recipient_name' => 'Budi',
            'address_line' => 'Jalan Mawar 1',
            'village'      => 'Candirejo',
            'district'     => 'Borobudur',
            'regency'      => 'Magelang',
            'province'     => 'Jawa Tengah',
            'postal_code'  => '56553',
            'is_default'   => 1,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotNull($addresses->findOwnedBy($id, $userA));
        $this->assertNull($addresses->findOwnedBy($id, $userB));

        $this->assertTrue($addresses->makeDefault($id, $userA));
        $this->assertFalse($addresses->makeDefault($id, $userB));
    }

    public function testSellerResourcesScopedPerSeller(): void
    {
        [$sellerA] = $this->makeSeller('a');
        [$sellerB] = $this->makeSeller('b');

        $hours = new SellerBusinessHourModel();

        $ok = $hours->replaceWeek($sellerA, [
            ['day_of_week' => 1, 'opens_at' => '09:00:00', 'closes_at' => '17:00:00', 'is_closed' => 0],
        ]);
        $this->assertTrue($ok);

        $forA = $hours->where('seller_id', $sellerA)->findAll();
        $forB = $hours->where('seller_id', $sellerB)->findAll();
        $this->assertNotEmpty($forA);
        $this->assertEmpty($forB);
    }
}
