<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Reset locale to default before each test
    App::setLocale(config('app.locale'));
});

test('SetLocale middleware is applied to API routes', function () {
    // Create a test API route
    Route::get('/api/test-middleware-applied', function () {
        return response()->json([
            'locale' => App::getLocale(),
            'middleware_applied' => true,
        ]);
    })->middleware('api');

    // Test with lang parameter
    $response = $this->get('/api/test-middleware-applied?lang=en');

    $response->assertStatus(200);
    $response->assertJson([
        'locale' => 'en',
        'middleware_applied' => true,
    ]);
});

test('middleware runs before controller execution on API routes', function () {
    $localeInController = null;

    // Create a test API route that captures the locale
    Route::get('/api/test-middleware-order', function () use (&$localeInController) {
        $localeInController = App::getLocale();

        return response()->json([
            'locale' => $localeInController,
        ]);
    })->middleware('api');

    // Make request with lang parameter
    $response = $this->get('/api/test-middleware-order?lang=en');

    // Verify the locale was set before the controller executed
    expect($localeInController)->toBe('en');
    $response->assertJson(['locale' => 'en']);
});

test('middleware works with Accept-Language header on API routes', function () {
    Route::get('/api/test-accept-language', function () {
        return response()->json([
            'locale' => App::getLocale(),
        ]);
    })->middleware('api');

    $response = $this->withHeaders([
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->get('/api/test-accept-language');

    $response->assertStatus(200);
    $response->assertJson(['locale' => 'en']);
});

test('middleware priority works correctly on API routes', function () {
    Route::get('/api/test-priority', function () {
        return response()->json([
            'locale' => App::getLocale(),
        ]);
    })->middleware('api');

    // lang parameter should take priority over Accept-Language header
    $response = $this->withHeaders([
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->get('/api/test-priority?lang=id');

    $response->assertStatus(200);
    $response->assertJson(['locale' => 'id']);
});
