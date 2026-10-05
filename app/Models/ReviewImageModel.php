<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Photos attached to a review.
 */
class ReviewImageModel extends BaseModel
{
    protected $table = 'review_images';

    protected $returnType = 'array';

    protected $useSoftDeletes = false;

    /**
     * Only `created_at` exists; images are never edited.
     */
    protected $useTimestamps = false;

    protected $allowedFields = [
        'review_id',
        'file_path',
        'thumb_path',
        'position',
    ];

    protected array $casts = [
        'position' => 'int',
    ];

    protected $validationRules = [
        'review_id'  => 'required|is_natural_no_zero',
        'file_path'  => 'required|max_length[255]',
        'thumb_path' => 'permit_empty|max_length[255]',
        'position'   => 'permit_empty|is_natural',
    ];

    /**
     * Photos for a review in display order.
     */
    public function forReview(int $reviewId): array
    {
        return $this->newRows(
            $this->newQuery()
                ->where('review_id', $reviewId)
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
        );
    }
}