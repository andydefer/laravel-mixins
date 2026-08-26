<?php

declare(strict_types=1);

namespace AndyDefer\Mixins\Tests\Fixtures\Models;

use AndyDefer\Mixins\Traits\HasAvailabilityAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Test Car model for polymorphic relationships.
 *
 * Used in testing to verify schedule/availability polymorphic attachments.
 */
final class TestCar extends Model
{
    use HasAvailabilityAttributes;

    protected $table = 'test_cars';

    protected $fillable = [
        'model',
        'license_plate',
        'type',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];
}
