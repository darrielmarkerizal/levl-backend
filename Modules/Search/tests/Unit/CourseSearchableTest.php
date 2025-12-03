<?php

namespace Modules\Search\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Searchable;
use Modules\Auth\Models\User;
use Modules\Common\Models\Category;
use Modules\Schemes\Models\Course;
use Tests\TestCase;

class CourseSearchableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Scout syncing for tests to avoid Meilisearch connection
        config(['scout.driver' => 'collection']);
    }

    public function test_course_model_uses_searchable_trait(): void
    {
        $course = new Course;

        $this->assertContains(
            Searchable::class,
            class_uses_recursive($course)
        );
    }

    public function test_to_searchable_array_returns_correct_data(): void
    {
        $category = Category::factory()->create(['name' => 'Programming']);
        $instructor = User::factory()->create(['name' => 'John Doe']);

        $course = Course::factory()->create([
            'title' => 'Laravel Basics',
            'short_desc' => 'Learn Laravel framework',
            'code' => 'LAR-101',
            'level_tag' => 'dasar',
            'category_id' => $category->id,
            'instructor_id' => $instructor->id,
            'status' => 'published',
            'type' => 'okupasi',
        ]);

        $searchableArray = $course->toSearchableArray();

        $this->assertArrayHasKey('id', $searchableArray);
        $this->assertArrayHasKey('title', $searchableArray);
        $this->assertArrayHasKey('short_desc', $searchableArray);
        $this->assertArrayHasKey('code', $searchableArray);
        $this->assertArrayHasKey('level_tag', $searchableArray);
        $this->assertArrayHasKey('category_id', $searchableArray);
        $this->assertArrayHasKey('category_name', $searchableArray);
        $this->assertArrayHasKey('instructor_id', $searchableArray);
        $this->assertArrayHasKey('instructor_name', $searchableArray);
        $this->assertArrayHasKey('tags', $searchableArray);
        $this->assertArrayHasKey('status', $searchableArray);

        $this->assertEquals('Laravel Basics', $searchableArray['title']);
        $this->assertEquals('Programming', $searchableArray['category_name']);
        $this->assertEquals('John Doe', $searchableArray['instructor_name']);
        $this->assertEquals('dasar', $searchableArray['level_tag']);
    }

    public function test_searchable_as_returns_correct_index_name(): void
    {
        $course = new Course;

        $this->assertEquals('courses_index', $course->searchableAs());
    }

    public function test_only_published_courses_should_be_searchable(): void
    {
        $publishedCourse = Course::factory()->create(['status' => 'published']);
        $draftCourse = Course::factory()->create(['status' => 'draft']);

        $this->assertTrue($publishedCourse->shouldBeSearchable());
        $this->assertFalse($draftCourse->shouldBeSearchable());
    }

    public function test_searchable_array_includes_tags(): void
    {
        $course = Course::factory()->create();

        // Create tags using the Tag factory
        $tag1 = \Modules\Schemes\Models\Tag::factory()->create(['name' => 'PHP']);
        $tag2 = \Modules\Schemes\Models\Tag::factory()->create(['name' => 'Web Development']);

        // Attach tags to course
        $course->tags()->attach([$tag1->id, $tag2->id]);

        $searchableArray = $course->fresh()->toSearchableArray();

        $this->assertIsArray($searchableArray['tags']);
        $this->assertContains('PHP', $searchableArray['tags']);
        $this->assertContains('Web Development', $searchableArray['tags']);
    }
}
