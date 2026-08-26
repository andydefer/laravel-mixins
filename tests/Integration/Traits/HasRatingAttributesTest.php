<?php

declare(strict_types=1);

// tests/Integration/Traits/HasRatingAttributesTest.php

namespace AndyDefer\Mixins\Tests\Integration\Traits;

use AndyDefer\LaravelRatings\Enums\RatingLevel;
use AndyDefer\LaravelRatings\Services\RatingService;
use AndyDefer\Mixins\Tests\Fixtures\Models\TestPost;
use AndyDefer\Mixins\Tests\Fixtures\Models\TestUser;
use AndyDefer\Mixins\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class HasRatingAttributesTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private RatingService $ratingService;

    private TestUser $user;

    private TestPost $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ratingService = app(RatingService::class);

        $this->user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->post = TestPost::create([
            'user_id' => $this->user->id,
            'title' => 'Test Post',
            'body' => 'Test content',
        ]);

        // Override isRateable to always return true for tests
        $this->post->isRateable = true;
    }

    // ============================================================
    // TESTS: averageRating
    // ============================================================

    public function test_average_rating_returns_correct_average(): void
    {
        // Arrange
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->ratingService->rate($user2, $this->post, RatingLevel::FIVE);
        $this->ratingService->rate($user3, $this->post, RatingLevel::FOUR);

        // Act
        $average = $this->post->average_rating;

        // Assert
        $this->assertEquals(4.5, $average);
    }

    public function test_average_rating_returns_zero_when_no_ratings(): void
    {
        // Act
        $average = $this->post->average_rating;

        // Assert
        $this->assertEquals(0.0, $average);
    }

    public function test_average_rating_returns_zero_when_model_is_not_rateable(): void
    {
        // Arrange
        $this->post->isRateable = false;

        // Act
        $average = $this->post->average_rating;

        // Assert
        $this->assertEquals(0.0, $average);
    }

    // ============================================================
    // TESTS: ratingCount
    // ============================================================

    public function test_rating_count_returns_correct_number(): void
    {
        // Arrange
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->ratingService->rate($user2, $this->post, RatingLevel::FIVE);
        $this->ratingService->rate($user3, $this->post, RatingLevel::FOUR);

        // Act
        $count = $this->post->rating_count;

        // Assert
        $this->assertEquals(2, $count);
    }

    public function test_rating_count_returns_zero_when_no_ratings(): void
    {
        // Act
        $count = $this->post->rating_count;

        // Assert
        $this->assertEquals(0, $count);
    }

    public function test_rating_count_returns_zero_when_model_is_not_rateable(): void
    {
        // Arrange
        $this->post->isRateable = false;

        // Act
        $count = $this->post->rating_count;

        // Assert
        $this->assertEquals(0, $count);
    }

    // ============================================================
    // TESTS: ratingDistribution
    // ============================================================

    public function test_rating_distribution_returns_correct_distribution(): void
    {
        // Arrange
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $user3 = TestUser::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->ratingService->rate($user2, $this->post, RatingLevel::FIVE);
        $this->ratingService->rate($user3, $this->post, RatingLevel::FIVE);
        $this->ratingService->rate($this->user, $this->post, RatingLevel::THREE);

        // Act
        $distribution = $this->post->rating_distribution;

        // Assert
        $this->assertEquals(0, $distribution[1]);
        $this->assertEquals(0, $distribution[2]);
        $this->assertEquals(1, $distribution[3]);
        $this->assertEquals(0, $distribution[4]);
        $this->assertEquals(2, $distribution[5]);
    }

    public function test_rating_distribution_returns_empty_array_when_no_ratings(): void
    {
        // Act
        $distribution = $this->post->rating_distribution;

        // Assert - Vérifier que toutes les valeurs sont à 0
        $this->assertIsArray($distribution);
        $this->assertEquals([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $distribution);
    }

    public function test_rating_distribution_returns_empty_array_when_model_is_not_rateable(): void
    {
        // Arrange
        $this->post->isRateable = false;

        // Act
        $distribution = $this->post->rating_distribution;

        // Assert
        $this->assertEmpty($distribution);
    }

    // ============================================================
    // TESTS: hasRatings
    // ============================================================

    public function test_has_ratings_returns_true_when_ratings_exist(): void
    {
        // Arrange
        $user2 = TestUser::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'status' => 'active',
            'role' => 'user',
        ]);

        $this->ratingService->rate($user2, $this->post, RatingLevel::FIVE);

        // Act
        $hasRatings = $this->post->has_ratings;

        // Assert
        $this->assertTrue($hasRatings);
    }

    public function test_has_ratings_returns_false_when_no_ratings(): void
    {
        // Act
        $hasRatings = $this->post->has_ratings;

        // Assert
        $this->assertFalse($hasRatings);
    }

    public function test_has_ratings_returns_false_when_model_is_not_rateable(): void
    {
        // Arrange
        $this->post->isRateable = false;

        // Act
        $hasRatings = $this->post->has_ratings;

        // Assert
        $this->assertFalse($hasRatings);
    }
}
