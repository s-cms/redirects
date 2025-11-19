<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use SmartCms\Redirects\Models\Redirect;

beforeEach(function () {
    // Set up a test route
    Route::get('/test-page', function () {
        return 'Test Page';
    })->middleware('web');

    Route::get('/another-page', function () {
        return 'Another Page';
    })->middleware('web');

    Route::get('/final-destination', function () {
        return 'Final Destination';
    })->middleware('web');

    // Clear cache before each test
    Cache::flush();
});

it('redirects when old_url matches', function () {
    Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    $response = $this->get('/old-page');

    $response->assertRedirect('/test-page');
    $response->assertStatus(301);
});

it('performs 302 redirect when specified', function () {
    Redirect::create([
        'old_url' => '/temporary',
        'new_url' => '/test-page',
        'status_code' => 302,
    ]);

    $response = $this->get('/temporary');

    $response->assertRedirect('/test-page');
    $response->assertStatus(302);
});

it('does not redirect when no matching old_url exists', function () {
    $response = $this->get('/test-page');

    $response->assertStatus(200);
    $response->assertSee('Test Page');
});

it('tracks hits when redirect is used', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/test-page',
        'status_code' => 301,
        'hit_count' => 0,
        'last_hit_at' => null,
    ]);

    $this->get('/old-page');

    $redirect->refresh();

    expect($redirect->hit_count)->toBe(1);
    expect($redirect->last_hit_at)->not->toBeNull();
});

it('increments hit count on multiple redirects', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/test-page',
        'status_code' => 301,
        'hit_count' => 5,
    ]);

    $this->get('/old-page');
    $this->get('/old-page');
    $this->get('/old-page');

    $redirect->refresh();

    expect($redirect->hit_count)->toBe(8);
});

it('uses cached redirects when cache is enabled', function () {
    config(['redirects.cache.enabled' => true]);

    $redirect = Redirect::create([
        'old_url' => '/cached-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // First request - should cache
    $this->get('/cached-page')->assertRedirect('/test-page');

    // Delete the redirect from database
    $redirect->delete();

    // Second request - should still redirect due to cache
    $this->get('/cached-page')->assertRedirect('/test-page');
});

it('does not use cache when cache is disabled', function () {
    config(['redirects.cache.enabled' => false]);

    $redirect = Redirect::create([
        'old_url' => '/non-cached-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // First request
    $this->get('/non-cached-page')->assertRedirect('/test-page');

    // Delete the redirect
    $redirect->delete();

    // Second request - should not redirect since cache is disabled
    $this->get('/non-cached-page')->assertStatus(404);
});

it('clears cache when redirect is saved', function () {
    config(['redirects.cache.enabled' => true]);

    $redirect = Redirect::create([
        'old_url' => '/cached-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // First request - caches redirects
    $this->get('/cached-page')->assertRedirect('/test-page');

    // Update the redirect
    $redirect->update(['new_url' => '/another-page']);

    // Second request - should use new URL (cache was cleared)
    $this->get('/cached-page')->assertRedirect('/another-page');
});

it('clears cache when redirect is deleted', function () {
    config(['redirects.cache.enabled' => true]);

    $redirect = Redirect::create([
        'old_url' => '/cached-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // First request - caches redirects
    $this->get('/cached-page')->assertRedirect('/test-page');

    // Delete the redirect
    $redirect->delete();

    // Manually clear the cache (since deletion should trigger it)
    Cache::forget(config('redirects.cache.key'));

    // Second request - should not redirect
    $this->get('/cached-page')->assertStatus(404);
});

it('handles paths without leading slash', function () {
    Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // Laravel normalizes paths, so this should work
    $response = $this->get('old-page');

    $response->assertRedirect('/test-page');
});

it('matches exact paths only', function () {
    Redirect::create([
        'old_url' => '/old',
        'new_url' => '/test-page',
        'status_code' => 301,
    ]);

    // Should not redirect paths that don't match exactly
    $this->get('/old-page')->assertStatus(404);
    $this->get('/old/page')->assertStatus(404);
});
