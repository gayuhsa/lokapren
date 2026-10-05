<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSellerOwnership;

/**
 * Storefront gallery, 360° tours and documentary video.
 *
 * `media_type` is a whitelisted string rather than an ENUM so the same model
 * works on MariaDB and the SQLite test engine.
 */
class SellerMediaModel extends BaseModel
{
    use HasSellerOwnership;

    protected $table = 'seller_media';

    protected $returnType = 'array';

    /**
     * `is_published` and `published_at` are server-owned: publication is an
     * application decision, not a client-supplied flag.
     */
    protected $allowedFields = [
        'media_type',
        'title',
        'caption',
        'file_path',
        'thumbnail_path',
        'duration_seconds',
        'position',
    ];

    protected array $casts = [
        'duration_seconds' => '?int',
        'position'         => 'int',
    ];

    protected $validationRules = [
        'media_type'       => 'required|in_list[image,gallery,video_360,documentary]',
        'title'            => 'permit_empty|max_length[150]',
        'file_path'        => 'required|max_length[255]',
        'thumbnail_path'   => 'permit_empty|max_length[255]',
        'duration_seconds' => 'permit_empty|is_natural',
        'position'         => 'permit_empty|is_natural',
    ];

    /**
     * Published storefront media for a seller.
     */
    public function publishedFor(int $sellerId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('seller_id', $sellerId)
                ->where('is_published', 1)
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
        );
    }

    /**
     * Total watch time contributed by documentaries, used on the storefront.
     */
    public function documentaryRuntime(int $sellerId): int
    {
        $row = $this->newQuery()
            ->selectSum('duration_seconds', 'seconds')
            ->where('seller_id', $sellerId)
            ->where('media_type', 'documentary')
            ->where('is_published', 1)
            ->get()
            ->getRowArray();

        return (int) ($row['seconds'] ?? 0);
    }
}