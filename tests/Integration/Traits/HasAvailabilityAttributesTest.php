<?php

declare(strict_types=1);

// tests/Integration/Traits/HasAvailabilityAttributesTest.php

namespace AndyDefer\Mixins\Tests\Integration\Traits;

use AndyDefer\LaravelChronos\Contracts\Services\AvailabilityServiceInterface;
use AndyDefer\LaravelChronos\Contracts\Services\SlotServiceInterface;
use AndyDefer\LaravelChronos\Models\Availability;
use AndyDefer\LaravelChronos\Models\Schedule;
use AndyDefer\LaravelChronos\Records\AvailabilityRecord;
use AndyDefer\LaravelChronos\Support\ChronosMutationContext;
use AndyDefer\LaravelChronos\ValueObjects\SlotVO;
use AndyDefer\LaravelChronos\ValueObjects\TimeZuluVO;
use AndyDefer\Mixins\Tests\Fixtures\Models\TestCar;
use AndyDefer\Mixins\Tests\IntegrationTestCase;
use Carbon\Carbon;

final class HasAvailabilityAttributesTest extends IntegrationTestCase
{
    private TestCar $testCar;

    private AvailabilityServiceInterface $availabilityService;

    private SlotServiceInterface $slotService;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time for consistent test results
        Carbon::setTestNow('2024-01-15 10:00:00');

        // Create a test entity
        $this->testCar = ChronosMutationContext::withAllowed(function () {
            return TestCar::create([
                'model' => 'Test Model',
                'license_plate' => 'TEST123',
                'type' => 'sedan',
                'capacity' => 5,
            ]);
        });

        $this->availabilityService = $this->app->make(AvailabilityServiceInterface::class);
        $this->slotService = $this->app->make(SlotServiceInterface::class);

        // Override isSchedulable to always return true for tests
        $this->testCar->isSchedulable = true;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    // ============================================================
    // TESTS: availabilities relationship
    // ============================================================

    public function test_availabilities_relationship_returns_collection(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '09:00:00', '17:00:00');

        // Act
        $availabilities = $this->testCar->availabilities;

        // Assert
        $this->assertCount(1, $availabilities);
        $this->assertInstanceOf(Availability::class, $availabilities->first());
        $this->assertEquals('Test Availability', $availabilities->first()->name);
    }

    // ============================================================
    // TESTS: isAvailableNow
    // ============================================================

    public function test_is_available_now_returns_true_when_current_time_is_within_availability(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '09:00:00', '17:00:00');

        // Act
        $isAvailable = $this->testCar->is_available_now;

        // Assert
        $this->assertTrue($isAvailable);
    }

    public function test_is_available_now_returns_false_when_current_time_is_outside_availability(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '14:00:00', '15:00:00');
        Carbon::setTestNow('2024-01-15 16:00:00');

        // Act
        $isAvailable = $this->testCar->is_available_now;

        // Assert
        $this->assertFalse($isAvailable);
    }

    public function test_is_available_now_returns_false_when_no_availability_exists(): void
    {
        // Act
        $isAvailable = $this->testCar->is_available_now;

        // Assert
        $this->assertFalse($isAvailable);
    }

    public function test_is_available_now_returns_false_when_model_is_not_schedulable(): void
    {
        // Arrange
        $this->testCar->isSchedulable = false;

        // Act
        $isAvailable = $this->testCar->is_available_now;

        // Assert
        $this->assertFalse($isAvailable);
    }

    // ============================================================
    // TESTS: nextSlot
    // ============================================================

    public function test_next_slot_returns_next_available_slot(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '09:00:00', '17:00:00');

        // Act
        $slot = $this->testCar->next_slot;

        // Assert
        $this->assertInstanceOf(SlotVO::class, $slot);
        $this->assertEquals('2024-01-15 10:00:00', $slot->getStart()->toDateTimeString());
        $this->assertEquals('2024-01-15 10:30:00', $slot->getEnd()->toDateTimeString());
    }

    public function test_next_slot_returns_null_when_no_availability_exists(): void
    {
        // Act
        $slot = $this->testCar->next_slot;

        // Assert
        $this->assertNull($slot);
    }

    public function test_next_slot_returns_null_when_all_slots_are_blocked(): void
    {
        // Arrange
        $record = AvailabilityRecord::from([
            'name' => 'Test Availability',
            'type' => 'test',
            'days' => ['monday'],
            'daily_start' => TimeZuluVO::from('09:00:00'),
            'daily_end' => TimeZuluVO::from('12:00:00'),
            'validity_start' => '2024-01-15T00:00:00Z',
            'validity_end' => '2024-01-15T23:59:59Z',
            'schedulable_type' => TestCar::class,
            'schedulable_id' => $this->testCar->id,
        ]);

        $availability = $this->availabilityService->create($record);

        ChronosMutationContext::withAllowed(function () use ($availability) {
            Schedule::create([
                'availability_id' => $availability->id,
                'schedulable_type' => TestCar::class,
                'schedulable_id' => $this->testCar->id,
                'title' => 'Blocking schedule',
                'start_datetime' => '2024-01-15 09:00:00',
                'end_datetime' => '2024-01-15 12:00:00',
            ]);
        });

        // Act
        $slot = $this->testCar->next_slot;

        // Assert
        $this->assertNull($slot);
    }

    public function test_next_slot_skips_blocked_slots(): void
    {
        // Arrange
        $availability = $this->createAvailabilityForCar($this->testCar, '09:00:00', '12:00:00');

        ChronosMutationContext::withAllowed(function () use ($availability) {
            Schedule::create([
                'availability_id' => $availability->id,
                'schedulable_type' => TestCar::class,
                'schedulable_id' => $this->testCar->id,
                'title' => 'Blocking schedule',
                'start_datetime' => '2024-01-15 09:00:00',
                'end_datetime' => '2024-01-15 10:00:00',
            ]);
        });

        // Act
        $slot = $this->testCar->next_slot;

        // Assert
        $this->assertInstanceOf(SlotVO::class, $slot);
        $this->assertEquals('2024-01-15 10:00:00', $slot->getStart()->toDateTimeString());
        $this->assertEquals('2024-01-15 10:30:00', $slot->getEnd()->toDateTimeString());
    }

    public function test_next_slot_returns_null_when_model_is_not_schedulable(): void
    {
        // Arrange
        $this->testCar->isSchedulable = false;

        // Act
        $slot = $this->testCar->next_slot;

        // Assert
        $this->assertNull($slot);
    }

    // ============================================================
    // TESTS: hasAvailabilityOnDate
    // ============================================================

    public function test_has_availability_on_date_returns_true_when_availability_exists(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '09:00:00', '17:00:00');

        // Act
        $hasAvailability = $this->testCar->has_availability_on_date;

        // Assert
        $this->assertTrue($hasAvailability);
    }

    public function test_has_availability_on_date_returns_false_when_no_availability_exists(): void
    {
        // Act
        $hasAvailability = $this->testCar->has_availability_on_date;

        // Assert
        $this->assertFalse($hasAvailability);
    }

    public function test_has_availability_on_date_returns_false_when_model_is_not_schedulable(): void
    {
        // Arrange
        $this->testCar->isSchedulable = false;

        // Act
        $hasAvailability = $this->testCar->has_availability_on_date;

        // Assert
        $this->assertFalse($hasAvailability);
    }

    // ============================================================
    // TESTS: totalAvailableMinutes
    // ============================================================

    public function test_total_available_minutes_returns_correct_total(): void
    {
        // Arrange
        $this->createAvailabilityForCar($this->testCar, '09:00:00', '12:00:00');

        // Act
        $totalMinutes = $this->testCar->total_available_minutes;

        // Assert
        $this->assertEquals(180, $totalMinutes);
    }

    public function test_total_available_minutes_returns_zero_when_no_availability_exists(): void
    {
        // Act
        $totalMinutes = $this->testCar->total_available_minutes;

        // Assert
        $this->assertEquals(0, $totalMinutes);
    }

    public function test_total_available_minutes_excludes_blocked_slots(): void
    {
        // Arrange
        $availability = $this->createAvailabilityForCar($this->testCar, '09:00:00', '12:00:00');

        ChronosMutationContext::withAllowed(function () use ($availability) {
            Schedule::create([
                'availability_id' => $availability->id,
                'schedulable_type' => TestCar::class,
                'schedulable_id' => $this->testCar->id,
                'title' => 'Blocking schedule',
                'start_datetime' => '2024-01-15 09:00:00',
                'end_datetime' => '2024-01-15 11:00:00',
            ]);
        });

        // Act
        $totalMinutes = $this->testCar->total_available_minutes;

        // Assert
        $this->assertEquals(60, $totalMinutes);
    }

    public function test_total_available_minutes_returns_zero_when_model_is_not_schedulable(): void
    {
        // Arrange
        $this->testCar->isSchedulable = false;

        // Act
        $totalMinutes = $this->testCar->total_available_minutes;

        // Assert
        $this->assertEquals(0, $totalMinutes);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function createAvailabilityForCar(TestCar $car, string $start, string $end): Availability
    {
        $record = AvailabilityRecord::from([
            'name' => 'Test Availability',
            'type' => 'test',
            'days' => ['monday'],
            'daily_start' => TimeZuluVO::from($start),
            'daily_end' => TimeZuluVO::from($end),
            'validity_start' => '2024-01-01T00:00:00Z',
            'validity_end' => '2024-01-31T23:59:59Z',
            'schedulable_type' => TestCar::class,
            'schedulable_id' => $car->id,
        ]);

        return $this->availabilityService->create($record);
    }
}
