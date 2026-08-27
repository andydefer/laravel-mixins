<?php

declare(strict_types=1);

// src/Traits/HasRatingAttributes.php

namespace AndyDefer\Mixins\Traits;

use AndyDefer\LaravelRatings\Models\Rating;
use AndyDefer\LaravelRatings\Services\RatingService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides Eloquent attributes for rating information.
 *
 * This trait adds convenient accessors to any model that can be rated
 * (Doctor, Pharmacy, Hospital, Product, etc.). It uses Laravel Ratings
 * to calculate average ratings and counts.
 *
 * @mixin Model
 *
 * @property-read float $average_rating The average rating (0.0 if no ratings)
 * @property-read int $rating_count The total number of ratings (0 if none)
 * @property-read array<int, int> $rating_distribution Distribution of ratings by level (1-5)
 * @property-read bool $has_ratings True if the model has at least one rating
 * @property-read Collection<int, Rating> $ratings All ratings for this model
 */
trait HasRatingAttributes
{
    /**
     * Get all ratings for this model.
     *
     * @return MorphMany<Rating>
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    /**
     * Get the average rating for this model.
     *
     * Calculates the average of all ratings associated with this model.
     *
     * @return Attribute<float>
     */
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

                    return (float) $ratingService->getAverageRating($this);
                } catch (\Exception) {
                    return 0.0;
                }
            }
        );
    }

    /**
     * Get the total number of ratings for this model.
     *
     * @return Attribute<int>
     */
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
                } catch (\Exception) {
                    return 0;
                }
            }
        );
    }

    /**
     * Get the distribution of ratings by level.
     *
     * Returns an array where keys are rating levels (1-5) and values are counts.
     *
     * @return Attribute<array<int, int>>
     */
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
                } catch (\Exception) {
                    return [];
                }
            }
        );
    }

    /**
     * Determine if this model has any ratings.
     *
     * @return Attribute<bool>
     */
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
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    /**
     * Determine if the model can be rated.
     *
     * Override this method in your model to add custom conditions.
     *
     * @return bool True if the model is rateable
     */
    protected function isRateable(): bool
    {
        return true;
    }
}
