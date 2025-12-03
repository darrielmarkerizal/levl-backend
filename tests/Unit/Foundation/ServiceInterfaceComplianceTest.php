<?php

namespace Tests\Unit\Foundation;

use App\Contracts\Services\ContentServiceInterface;
use App\Contracts\Services\ForumServiceInterface;
use App\Contracts\Services\ProfileServiceInterface;
use Modules\Auth\Services\ProfileService;
use Modules\Content\Services\ContentService;
use Modules\Forums\Services\ForumService;
use Tests\TestCase;

class ServiceInterfaceComplianceTest extends TestCase
{
    /**
     * Test that ForumService implements ForumServiceInterface.
     */
    public function test_forum_service_implements_interface(): void
    {
        $service = app(ForumServiceInterface::class);

        $this->assertInstanceOf(ForumServiceInterface::class, $service);
        $this->assertInstanceOf(ForumService::class, $service);
    }

    /**
     * Test that ContentService implements ContentServiceInterface.
     */
    public function test_content_service_implements_interface(): void
    {
        $service = app(ContentServiceInterface::class);

        $this->assertInstanceOf(ContentServiceInterface::class, $service);
        $this->assertInstanceOf(ContentService::class, $service);
    }

    /**
     * Test that ProfileService implements ProfileServiceInterface.
     */
    public function test_profile_service_implements_interface(): void
    {
        $service = app(ProfileServiceInterface::class);

        $this->assertInstanceOf(ProfileServiceInterface::class, $service);
        $this->assertInstanceOf(ProfileService::class, $service);
    }

    /**
     * Test that ForumService has all required interface methods.
     */
    public function test_forum_service_has_all_interface_methods(): void
    {
        $service = app(ForumServiceInterface::class);

        $this->assertTrue(method_exists($service, 'createThread'));
        $this->assertTrue(method_exists($service, 'updateThread'));
        $this->assertTrue(method_exists($service, 'deleteThread'));
        $this->assertTrue(method_exists($service, 'getThreadsForScheme'));
        $this->assertTrue(method_exists($service, 'searchThreads'));
        $this->assertTrue(method_exists($service, 'getThreadDetail'));
        $this->assertTrue(method_exists($service, 'createReply'));
        $this->assertTrue(method_exists($service, 'updateReply'));
        $this->assertTrue(method_exists($service, 'deleteReply'));
    }

    /**
     * Test that ContentService has all required interface methods.
     */
    public function test_content_service_has_all_interface_methods(): void
    {
        $service = app(ContentServiceInterface::class);

        $this->assertTrue(method_exists($service, 'createAnnouncement'));
        $this->assertTrue(method_exists($service, 'createNews'));
        $this->assertTrue(method_exists($service, 'updateAnnouncement'));
        $this->assertTrue(method_exists($service, 'updateNews'));
        $this->assertTrue(method_exists($service, 'publishContent'));
        $this->assertTrue(method_exists($service, 'scheduleContent'));
        $this->assertTrue(method_exists($service, 'cancelSchedule'));
        $this->assertTrue(method_exists($service, 'deleteContent'));
        $this->assertTrue(method_exists($service, 'getAnnouncementsForUser'));
        $this->assertTrue(method_exists($service, 'getNewsFeed'));
        $this->assertTrue(method_exists($service, 'searchContent'));
        $this->assertTrue(method_exists($service, 'markAsRead'));
        $this->assertTrue(method_exists($service, 'incrementViews'));
        $this->assertTrue(method_exists($service, 'getTrendingNews'));
        $this->assertTrue(method_exists($service, 'getFeaturedNews'));
    }

    /**
     * Test that ProfileService has all required interface methods.
     */
    public function test_profile_service_has_all_interface_methods(): void
    {
        $service = app(ProfileServiceInterface::class);

        $this->assertTrue(method_exists($service, 'updateProfile'));
        $this->assertTrue(method_exists($service, 'uploadAvatar'));
        $this->assertTrue(method_exists($service, 'deleteAvatar'));
        $this->assertTrue(method_exists($service, 'getProfileData'));
        $this->assertTrue(method_exists($service, 'getPublicProfile'));
        $this->assertTrue(method_exists($service, 'changePassword'));
        $this->assertTrue(method_exists($service, 'deleteAccount'));
        $this->assertTrue(method_exists($service, 'restoreAccount'));
    }

    /**
     * Test that ForumService method signatures match interface.
     */
    public function test_forum_service_method_signatures_match_interface(): void
    {
        $interfaceReflection = new \ReflectionClass(ForumServiceInterface::class);
        $serviceReflection = new \ReflectionClass(ForumService::class);

        foreach ($interfaceReflection->getMethods() as $interfaceMethod) {
            $this->assertTrue(
                $serviceReflection->hasMethod($interfaceMethod->getName()),
                "Method {$interfaceMethod->getName()} not found in ForumService"
            );

            $serviceMethod = $serviceReflection->getMethod($interfaceMethod->getName());

            // Check parameter count matches
            $this->assertEquals(
                $interfaceMethod->getNumberOfParameters(),
                $serviceMethod->getNumberOfParameters(),
                "Parameter count mismatch for method {$interfaceMethod->getName()}"
            );

            // Check return type matches
            $interfaceReturnType = $interfaceMethod->getReturnType();
            $serviceReturnType = $serviceMethod->getReturnType();

            if ($interfaceReturnType !== null && $serviceReturnType !== null) {
                $this->assertEquals(
                    $interfaceReturnType->getName(),
                    $serviceReturnType->getName(),
                    "Return type mismatch for method {$interfaceMethod->getName()}"
                );
            }
        }
    }

    /**
     * Test that ContentService method signatures match interface.
     */
    public function test_content_service_method_signatures_match_interface(): void
    {
        $interfaceReflection = new \ReflectionClass(ContentServiceInterface::class);
        $serviceReflection = new \ReflectionClass(ContentService::class);

        foreach ($interfaceReflection->getMethods() as $interfaceMethod) {
            $this->assertTrue(
                $serviceReflection->hasMethod($interfaceMethod->getName()),
                "Method {$interfaceMethod->getName()} not found in ContentService"
            );

            $serviceMethod = $serviceReflection->getMethod($interfaceMethod->getName());

            // Check parameter count matches
            $this->assertEquals(
                $interfaceMethod->getNumberOfParameters(),
                $serviceMethod->getNumberOfParameters(),
                "Parameter count mismatch for method {$interfaceMethod->getName()}"
            );
        }
    }

    /**
     * Test that ProfileService method signatures match interface.
     */
    public function test_profile_service_method_signatures_match_interface(): void
    {
        $interfaceReflection = new \ReflectionClass(ProfileServiceInterface::class);
        $serviceReflection = new \ReflectionClass(ProfileService::class);

        foreach ($interfaceReflection->getMethods() as $interfaceMethod) {
            $this->assertTrue(
                $serviceReflection->hasMethod($interfaceMethod->getName()),
                "Method {$interfaceMethod->getName()} not found in ProfileService"
            );

            $serviceMethod = $serviceReflection->getMethod($interfaceMethod->getName());

            // Check parameter count matches
            $this->assertEquals(
                $interfaceMethod->getNumberOfParameters(),
                $serviceMethod->getNumberOfParameters(),
                "Parameter count mismatch for method {$interfaceMethod->getName()}"
            );
        }
    }
}
