<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class DevController extends Controller
{
    public function checkOctane(Request $request)
    {
        $isOctane = isset($_SERVER['LARAVEL_OCTANE']);
        
        $data = [
            'is_octane' => $isOctane,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => phpversion(),
            'octane_server' => $_SERVER['LARAVEL_OCTANE'] ?? 'N/A',
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(true),
            'environment' => app()->environment(),
        ];

        return view('dev.octane-check', $data);
    }

    public function benchmarkView()
    {
        return view('dev.benchmark');
    }

    public function benchmarkApi(Request $request)
    {
        $mode = $request->query('mode', 'simple');
        
        $result = match($mode) {
            // Scenario 1: Student Dashboard - Most common query
            'dashboard' => $this->benchmarkStudentDashboard(),
            
            // Scenario 2: Course Listing with filters
            'courses' => $this->benchmarkCourseList(),
            
            // Scenario 3: Enrollment check (frequent operation)
            'enrollment' => $this->benchmarkEnrollmentCheck(),
            
            // Scenario 4: Database connection test (baseline)
            'db' => \DB::select('select 1'),
            
            // Scenario 5: Simple response (no DB)
            default => ['simple' => true],
        };

        return response()->json([
            'status' => 'ok',
            'mode' => $mode,
            'result' => 1,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Benchmark: Student Dashboard Query
     * Simulates getting enrolled courses with progress for a student
     */
    private function benchmarkStudentDashboard()
    {
        // Get a random user ID (simulate different students)
        $userId = \DB::table('users')
            ->where('deleted_at', null)
            ->inRandomOrder()
            ->value('id') ?? 1;

        // Realistic dashboard query: enrolled courses with units count
        return \DB::table('enrollments as e')
            ->join('courses as c', 'c.id', '=', 'e.course_id')
            ->leftJoin('units as u', 'u.course_id', '=', 'c.id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'c.category_id')
            ->where('e.user_id', $userId)
            ->whereNull('c.deleted_at')
            ->select(
                'c.id',
                'c.title',
                'c.code',
                'c.status',
                'cat.name as category_name',
                \DB::raw('COUNT(DISTINCT u.id) as total_units'),
                'e.status as enrollment_status',
                'e.enrolled_at'
            )
            ->groupBy('c.id', 'c.title', 'c.code', 'c.status', 'cat.name', 'e.status', 'e.enrolled_at')
            ->limit(10)
            ->get();
    }

    /**
     * Benchmark: Course Listing Query
     * Simulates browsing published courses with category filter
     */
    private function benchmarkCourseList()
    {
        return \DB::table('courses as c')
            ->leftJoin('categories as cat', 'cat.id', '=', 'c.category_id')
            ->leftJoin('users as instructor', 'instructor.id', '=', 'c.instructor_id')
            ->whereNull('c.deleted_at')
            ->where('c.status', 'published')
            ->select(
                'c.id',
                'c.title',
                'c.code',
                'c.short_desc',
                'c.level_tag',
                'c.duration_estimate',
                'cat.name as category_name',
                'instructor.name as instructor_name',
                'c.published_at'
            )
            ->orderBy('c.published_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Benchmark: Enrollment Check Query
     * Simulates checking if user is enrolled in a course
     */
    private function benchmarkEnrollmentCheck()
    {
        $userId = \DB::table('users')
            ->where('deleted_at', null)
            ->inRandomOrder()
            ->value('id') ?? 1;

        $courseId = \DB::table('courses')
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->value('id') ?? 1;

        return \DB::table('enrollments')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }
}

