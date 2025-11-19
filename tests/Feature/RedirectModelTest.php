<?php

use Illuminate\Support\Facades\Cache;
use SmartCms\Redirects\Models\Redirect;

beforeEach(function () {
    Cache::flush();
});

it('creates redirect with required fields', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    expect($redirect->old_url)->toBe('/old-page');
    expect($redirect->new_url)->toBe('/new-page');
    expect($redirect->status_code)->toBe(301);
});

it('uses default status code of 301', function () {
    $redirect = Redirect::factory()->create(['status_code' => 301]);

    expect($redirect->status_code)->toBe(301);
});

it('allows status code of 302', function () {
    $redirect = Redirect::create([
        'old_url' => '/temporary',
        'new_url' => '/new-page',
        'status_code' => 302,
    ]);

    expect($redirect->status_code)->toBe(302);
});

it('uses configurable table name', function () {
    config(['redirects.table_name' => 'custom_redirects']);

    $redirect = new Redirect();

    expect($redirect->getTable())->toBe('custom_redirects');
});

it('clears cache when redirect is created', function () {
    config(['redirects.cache.enabled' => true]);

    // Pre-populate cache
    Cache::put(config('redirects.cache.key'), ['test' => 'data'], 3600);

    expect(Cache::has(config('redirects.cache.key')))->toBeTrue();

    // Create redirect (should clear cache)
    Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    expect(Cache::has(config('redirects.cache.key')))->toBeFalse();
});

it('clears cache when redirect is updated', function () {
    config(['redirects.cache.enabled' => true]);

    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    // Pre-populate cache
    Cache::put(config('redirects.cache.key'), ['test' => 'data'], 3600);

    expect(Cache::has(config('redirects.cache.key')))->toBeTrue();

    // Update redirect (should clear cache)
    $redirect->update(['new_url' => '/updated-page']);

    expect(Cache::has(config('redirects.cache.key')))->toBeFalse();
});

it('clears cache when redirect is deleted', function () {
    config(['redirects.cache.enabled' => true]);

    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    // Pre-populate cache
    Cache::put(config('redirects.cache.key'), ['test' => 'data'], 3600);

    expect(Cache::has(config('redirects.cache.key')))->toBeTrue();

    // Delete redirect (should clear cache)
    $redirect->delete();

    expect(Cache::has(config('redirects.cache.key')))->toBeFalse();
});

it('can manually clear cache', function () {
    config(['redirects.cache.enabled' => true]);

    // Pre-populate cache
    Cache::put(config('redirects.cache.key'), ['test' => 'data'], 3600);

    expect(Cache::has(config('redirects.cache.key')))->toBeTrue();

    // Manually clear cache
    Redirect::clearCache();

    expect(Cache::has(config('redirects.cache.key')))->toBeFalse();
});

it('has timestamps', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    expect($redirect->created_at)->not->toBeNull();
    expect($redirect->updated_at)->not->toBeNull();
    expect($redirect->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($redirect->updated_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('updates updated_at timestamp when modified', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $originalUpdatedAt = $redirect->updated_at;

    sleep(1);

    $redirect->update(['new_url' => '/updated-page']);

    expect($redirect->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
});

it('can query by old_url', function () {
    Redirect::create([
        'old_url' => '/find-me',
        'new_url' => '/page',
        'status_code' => 301,
    ]);

    Redirect::create([
        'old_url' => '/other',
        'new_url' => '/page',
        'status_code' => 301,
    ]);

    $redirect = Redirect::where('old_url', '/find-me')->first();

    expect($redirect)->not->toBeNull();
    expect($redirect->old_url)->toBe('/find-me');
});

it('can query by new_url', function () {
    Redirect::create([
        'old_url' => '/old-1',
        'new_url' => '/target-page',
        'status_code' => 301,
    ]);

    Redirect::create([
        'old_url' => '/old-2',
        'new_url' => '/target-page',
        'status_code' => 301,
    ]);

    Redirect::create([
        'old_url' => '/old-3',
        'new_url' => '/other-page',
        'status_code' => 301,
    ]);

    $redirects = Redirect::where('new_url', '/target-page')->get();

    expect($redirects)->toHaveCount(2);
});

it('can query by status_code', function () {
    Redirect::create([
        'old_url' => '/permanent-1',
        'new_url' => '/page',
        'status_code' => 301,
    ]);

    Redirect::create([
        'old_url' => '/permanent-2',
        'new_url' => '/page',
        'status_code' => 301,
    ]);

    Redirect::create([
        'old_url' => '/temporary',
        'new_url' => '/page',
        'status_code' => 302,
    ]);

    $permanent = Redirect::where('status_code', 301)->get();
    $temporary = Redirect::where('status_code', 302)->get();

    expect($permanent)->toHaveCount(2);
    expect($temporary)->toHaveCount(1);
});

it('can be created using factory', function () {
    $redirect = Redirect::factory()->create();

    expect($redirect)->toBeInstanceOf(Redirect::class);
    expect($redirect->old_url)->toBeString();
    expect($redirect->new_url)->toBeString();
    expect($redirect->status_code)->toBeIn([301, 302]);
});

it('can be created using factory with custom attributes', function () {
    $redirect = Redirect::factory()->create([
        'old_url' => '/custom-old',
        'new_url' => '/custom-new',
        'status_code' => 302,
    ]);

    expect($redirect->old_url)->toBe('/custom-old');
    expect($redirect->new_url)->toBe('/custom-new');
    expect($redirect->status_code)->toBe(302);
});

it('can use factory unvisited state', function () {
    $redirect = Redirect::factory()->unvisited()->create();

    expect($redirect->hit_count)->toBe(0);
    expect($redirect->last_hit_at)->toBeNull();
});

it('can use factory recentlyHit state', function () {
    $redirect = Redirect::factory()->recentlyHit()->create();

    expect($redirect->hit_count)->toBeGreaterThan(0);
    expect($redirect->last_hit_at)->not->toBeNull();
    expect($redirect->last_hit_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
