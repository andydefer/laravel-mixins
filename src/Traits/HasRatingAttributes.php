<?php

declare(strict_types=1);

// src/Traits/HasRatingAttributes.php

namespace AndyDefer\Mixins\Traits;

use AndyDefer\LaravelRatings\Services\RatingService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

trait HasRatingAttributes
{
    public function averageRating(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                /** @var Model $this */
                if (! $this->isRateable()) {
                    return 0.0;
                }

                try {
                    $ratingService = app(RatingService::class);

                    return $ratingService->getAverageRating($this);
                } catch (\Exception $e) {
                    return 0.0;
                }
            }
        );
    }

    public function ratingCount(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                /** @var Model $this */
                if (! $this->isRateable()) {
                    return 0;
                }

                try {
                    $ratingService = app(RatingService::class);

                    return $ratingService->countRatings($this);
                } catch (\Exception $e) {
                    return 0;
                }
            }
        );
    }

    public function ratingDistribution(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                /** @var Model $this */
                if (! $this->isRateable()) {
                    return [];
                }

                try {
                    $ratingService = app(RatingService::class);

                    return $ratingService->getRatingDistribution($this);
                } catch (\Exception $e) {
                    return [];
                }
            }
        );
    }

    public function hasRatings(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                /** @var Model $this */
                if (! $this->isRateable()) {
                    return false;
                }

                try {
                    $ratingService = app(RatingService::class);

                    return $ratingService->countRatings($this) > 0;
                } catch (\Exception $e) {
                    return false;
                }
            }
        );
    }

    /**
     * Check if the model is rateable.
     * Override this method in your model if needed.
     */
    protected function isRateable(): bool
    {
        return true;
    }
}
