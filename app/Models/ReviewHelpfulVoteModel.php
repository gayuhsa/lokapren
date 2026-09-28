<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ReviewHelpfulVoteModel extends Model
{
    protected $table = 'review_helpful_votes';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'review_id',
        'voter_id',
    ];
}
