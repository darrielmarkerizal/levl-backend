<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class DevController extends Controller
{
    public function checkOctane(Request $request)
    {
        $isOctane = isset($_SERVER['LARAVEL_OCTANE']) || App::bound('octane');
        
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
        $start = microtime(true);
        
        if ($request->get('mode') === 'db') {
            // Test DB connection and query execution
            \Illuminate\Support\Facades\DB::select('select 1');
        }

        return response()->json([
            'status' => 'ok',
            'mode' => $request->get('mode', 'simple'),
            'duration' => microtime(true) - $start,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'pid' => getmypid(),
        ]);
    }
}

