<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Seller blog posts. `seller_id` is server-owned and `status` is an application
 * decision, so neither is writable from a request.
 */
class BlogPostModel extends BaseModel
{
    use HasSellerOwnership;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $table = 'blog_posts';

    protected $returnType = 'array';

    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';

    protected $allowedFields = [
        'category_id',
        'title',
        'excerpt',
        'body',
        'cover_path',
    ];

    protected array $casts = [
        'view_count' => 'int',
    ];

    protected $validationRules = [
        'title'      => 'required|max_length[200]',
        'slug'       => 'required|max_length[150]',
        'excerpt'    => 'permit_empty|max_length[500]',
        'cover_path' => 'permit_empty|max_length[255]',
        'category_id' => 'permit_empty|is_natural_no_zero',
    ];

    /**
     * Published posts for the public blog index.
     */
    public function published(int $limit = 10, int $offset = 0)
    {
        $builder = $this->newQuery()
            ->where('status', self::STATUS_PUBLISHED)
            ->orderBy('published_at', 'DESC');

        return $this->newRows($builder, $limit, $offset);
    }

    /**
     * Publish a post owned by `$sellerId`, stamping `published_at`.
     */
    public function publish(int $postId, int $sellerId): bool
    {
        if ($this->findOwnedBy($postId, $sellerId) === null) {
            return false;
        }

        $this->newQuery()
            ->where('id', $postId)
            ->set([
                'status'       => self::STATUS_PUBLISHED,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s'),
            ])
            ->update();

        return true;
    }

    /**
     * Increment the view counter without loading the row first.
     */
    public function recordView(int $postId): void
    {
        $this->newQuery()
            ->where('id', $postId)
            ->set('view_count', 'view_count + 1', false)
            ->update();
    }
}