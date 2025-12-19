<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Reset locale to default before each test
    App::setLocale(config('app.locale'));

    // Create a test route that returns the current locale
    Route::get('/test-locale', function () {
        return response()->json(['locale' => App::getLocale()]);
    })->middleware(\App\Http\Middleware\SetLocale::class);
});

test('middleware sets locale from lang query parameter', function () {
    $response = $this->get('/test-locale?lang=en');

    $response->assertJson(['locale' => 'en']);
});

test('middleware sets locale from Accept-Language header', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->get('/test-locale');

    $response->assertJson(['locale' => 'en']);
});

test('lang parameter takes priority over Accept-Language header', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->get('/test-locale?lang=id');

    $response->assertJson(['locale' => 'id']);
});

test('middleware falls back to default locale for unsupported locale in parameter', function () {
    $response = $this->get('/test-locale?lang=fr');

    $response->assertJson(['locale' => config('app.locale')]);
});

test('middleware falls back to default locale for unsupported locale in header', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'fr-FR,fr;q=0.9',
    ])->get('/test-locale');

    $response->assertJson(['locale' => config('app.locale')]);
});

test('middleware uses default locale when no locale specified', function () {
    $response = $this->get('/test-locale');

    $response->assertJson(['locale' => config('app.locale')]);
});

test('middleware validates locale against supported locales list', function () {
    // Test with supported locale
    $response = $this->get('/test-locale?lang=en');
    $response->assertJson(['locale' => 'en']);

    // Test with another supported locale
    $response = $this->get('/test-locale?lang=id');
    $response->assertJson(['locale' => 'id']);

    // Test with unsupported locale
    $response = $this->get('/test-locale?lang=es');
    $response->assertJson(['locale' => config('app.locale')]);
});

test('middleware parses complex Accept-Language header correctly', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7,id;q=0.6',
    ])->get('/test-locale');

    // Should select 'en' as it's the first supported locale in the list
    $response->assertJson(['locale' => 'en']);
});

test('middleware handles Accept-Language header with locale codes containing hyphens', function () {
    $response = $this->withHeaders([
        'Accept-Language' => 'en-US',
    ])->get('/test-locale');

    // Should extract 'en' from 'en-US'
    $response->assertJson(['locale' => 'en']);
});
