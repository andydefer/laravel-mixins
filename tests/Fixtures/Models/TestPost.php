<?php

declare(strict_types=1);

// tests/Fixtures/models/TestPost.php

namespace AndyDefer\Mixins\Tests\Fixtures\Models;

use AndyDefer\Mixins\Traits\HasRatingAttributes;
use Illuminate\Database\Eloquent\Model;

final class TestPost extends Model
{
    use HasRatingAttributes;

    protected $table = 'test_posts';

    protected $fillable = [
        'user_id',
        'title',
        'body',
    ];

    public bool $isRateable = true;

    protected function isRateable(): bool
    {
        return $this->isRateable;
    }
}
