<?php

use SmartCms\Redirects\Models\Redirect;

it('initializes hit_count to 0 when redirect is created', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);
    expect($redirect->hit_count)->toBe(0);
    expect($redirect->last_hit_at)->toBeNull();
});

it('tracks hit using trackHit method', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $redirect->trackHit();
    $redirect->refresh();

    expect($redirect->hit_count)->toBe(1);
    expect($redirect->last_hit_at)->not->toBeNull();
    expect($redirect->last_hit_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('increments hit_count on multiple hits', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $redirect->trackHit();
    $redirect->trackHit();
    $redirect->trackHit();
    $redirect->refresh();

    expect($redirect->hit_count)->toBe(3);
});

it('updates last_hit_at timestamp on each hit', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $redirect->trackHit();
    $redirect->refresh();
    $firstHitTime = $redirect->last_hit_at;

    sleep(1);

    $redirect->trackHit();
    $redirect->refresh();
    $secondHitTime = $redirect->last_hit_at;

    expect($secondHitTime)->not->toBe($firstHitTime);
    expect($secondHitTime->isAfter($firstHitTime))->toBeTrue();
});

it('casts last_hit_at to Carbon instance', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $redirect->trackHit();
    $redirect->refresh();

    expect($redirect->last_hit_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

it('casts hit_count to integer', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
        'hit_count' => '42',
    ]);

    $redirect->refresh();

    expect($redirect->hit_count)->toBeInt();
    expect($redirect->hit_count)->toBe(42);
});

it('maintains hit count when redirect is updated', function () {
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    $redirect->trackHit();
    $redirect->trackHit();
    $redirect->refresh();

    expect($redirect->hit_count)->toBe(2);

    // Update the redirect
    $redirect->update(['new_url' => '/updated-page']);
    $redirect->refresh();

    // Hit count should be preserved
    expect($redirect->hit_count)->toBe(2);
});

it('can query redirects by hit count', function () {
    Redirect::create([
        'old_url' => '/popular',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 1000,
    ]);

    Redirect::create([
        'old_url' => '/unpopular',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 5,
    ]);

    Redirect::create([
        'old_url' => '/very-popular',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 5000,
    ]);

    $popularRedirects = Redirect::where('hit_count', '>', 100)->get();

    expect($popularRedirects)->toHaveCount(2);
    expect($popularRedirects->pluck('old_url'))->toContain('/popular', '/very-popular');
});

it('can query redirects by last_hit_at', function () {
    Redirect::create([
        'old_url' => '/recent',
        'new_url' => '/page',
        'status_code' => 301,
        'last_hit_at' => now()->subHours(2),
    ]);

    Redirect::create([
        'old_url' => '/old',
        'new_url' => '/page',
        'status_code' => 301,
        'last_hit_at' => now()->subDays(30),
    ]);

    Redirect::create([
        'old_url' => '/never-hit',
        'new_url' => '/page',
        'status_code' => 301,
        'last_hit_at' => null,
    ]);

    $recentRedirects = Redirect::where('last_hit_at', '>', now()->subDays(7))->get();

    expect($recentRedirects)->toHaveCount(1);
    expect($recentRedirects->first()->old_url)->toBe('/recent');
});

it('can find redirects that have never been hit', function () {
    Redirect::create([
        'old_url' => '/hit-once',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 1,
        'last_hit_at' => now(),
    ]);

    Redirect::create([
        'old_url' => '/never-hit-1',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 0,
        'last_hit_at' => null,
    ]);

    Redirect::create([
        'old_url' => '/never-hit-2',
        'new_url' => '/page',
        'status_code' => 301,
        'hit_count' => 0,
        'last_hit_at' => null,
    ]);

    $neverHit = Redirect::where('hit_count', 0)
        ->whereNull('last_hit_at')
        ->get();

    expect($neverHit)->toHaveCount(2);
});
