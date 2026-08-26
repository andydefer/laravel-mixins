<?php

declare(strict_types=1);

// src/Traits/HasAvailabilityAttributes.php

namespace AndyDefer\Mixins\Traits;

use AndyDefer\LaravelChronos\Contracts\Configs\ChronosConfigInterface;
use AndyDefer\LaravelChronos\Contracts\Services\AvailabilityServiceInterface;
use AndyDefer\LaravelChronos\Contracts\Services\SlotServiceInterface;
use AndyDefer\LaravelChronos\Models\Availability;
use AndyDefer\LaravelChronos\ValueObjects\DateTimeZuluVO;
use AndyDefer\LaravelChronos\ValueObjects\SlotVO;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Provides Eloquent attributes for checking availability and slot information.
 *
 * This trait adds convenient accessors to any model that can be scheduled
 * (Doctor, Pharmacy, Hospital, etc.). It uses Laravel Chronos to check
 * availability and find available time slots.
 *
 * @mixin Model
 *
 * @property-read bool $is_available_now Whether the model is currently available
 * @property-read SlotVO|null $next_slot The next available slot
 * @property-read bool $has_availability_on_date Whether the model has availability today
 * @property-read int $total_available_minutes Total available minutes for today
 * @property-read Collection<int, Availability> $active_availabilities Active availabilities for today
 */
trait HasAvailabilityAttributes
{
    /**
     * Define the polymorphic relationship to availabilities.
     *
     * @return MorphMany<Availability>
     */
    public function availabilities(): MorphMany
    {
        return $this->morphMany(Availability::class, 'schedulable');
    }

    /**
     * Get the active availabilities for today.
     *
     * @return Attribute<Collection<int, Availability>>
     */
    public function activeAvailabilities(): Attribute
    {
        return Attribute::make(
            get: function (): Collection {
                /** @var Model $this */
                if (! $this->isSchedulable()) {
                    return new Collection;
                }

                try {
                    $availabilityService = app(AvailabilityServiceInterface::class);

                    return $availabilityService->findActiveAtDate(
                        $this,
                        DateTimeZuluVO::today()
                    );
                } catch (\Exception) {
                    return new Collection;
                }
            }
        );
    }

    /**
     * Get whether the model is currently available.
     *
     * Checks if the current time falls within any available slot for today.
     *
     * @return Attribute<bool>
     */
    public function isAvailableNow(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                /** @var Model $this */
                if (! $this->isSchedulable()) {
                    return false;
                }

                try {
                    $slotService = app(SlotServiceInterface::class);
                    $config = app(ChronosConfigInterface::class);
                    $duration = $config->getMinSlotSearchDuration();

                    $slots = $slotService->findSlotsForDay(
                        $this,
                        DateTimeZuluVO::today(),
                        $duration
                    );

                    $now = DateTimeZuluVO::now();

                    foreach ($slots as $slot) {
                        if ($now->isBetween($slot->getStart(), $slot->getEnd())) {
                            return true;
                        }
                    }

                    return false;
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    /**
     * Get the next available slot for this model.
     *
     * Searches for the next available slot starting from the current time.
     *
     * @return Attribute<SlotVO|null>
     */
    public function nextSlot(): Attribute
    {
        return Attribute::make(
            get: function (): ?SlotVO {
                /** @var Model $this */
                if (! $this->isSchedulable()) {
                    return null;
                }

                try {
                    $slotService = app(SlotServiceInterface::class);
                    $config = app(ChronosConfigInterface::class);
                    $duration = $config->getMinSlotSearchDuration();

                    return $slotService->findNextSlot(
                        $this,
                        DateTimeZuluVO::now(),
                        $duration
                    );
                } catch (\Exception) {
                    return null;
                }
            }
        );
    }

    /**
     * Get whether the model has any availability on today's date.
     *
     * Checks if any availability record exists for the current date.
     *
     * @return Attribute<bool>
     */
    public function hasAvailabilityOnDate(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                /** @var Model $this */
                if (! $this->isSchedulable()) {
                    return false;
                }

                try {
                    $slotService = app(SlotServiceInterface::class);

                    return $slotService->hasAvailabilityOnDate(
                        $this,
                        DateTimeZuluVO::today()
                    );
                } catch (\Exception) {
                    return false;
                }
            }
        );
    }

    /**
     * Get the total available minutes for today.
     *
     * Sums all available slots durations for the current date.
     *
     * @return Attribute<int>
     */
    public function totalAvailableMinutes(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                /** @var Model $this */
                if (! $this->isSchedulable()) {
                    return 0;
                }

                try {
                    $slotService = app(SlotServiceInterface::class);
                    $config = app(ChronosConfigInterface::class);
                    $duration = $config->getMinSlotSearchDuration();

                    $slots = $slotService->findSlotsForDay(
                        $this,
                        DateTimeZuluVO::today(),
                        $duration
                    );

                    return $slots->getTotalAvailableMinutes();
                } catch (\Exception) {
                    return 0;
                }
            }
        );
    }

    /**
     * Determine if the model can be scheduled.
     *
     * Override this method in your model to add custom conditions.
     *
     * @return bool True if the model is schedulable
     */
    protected function isSchedulable(): bool
    {
        return true;
    }
}
