<?php

use SmartCms\Redirects\Models\Redirect;

it('detects self-loop (A → A)', function () {
    $redirect = new Redirect([
        'old_url' => '/same-page',
        'new_url' => '/same-page',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeTrue();
});

it('detects simple circular loop (A → B, B → A)', function () {
    // Create first redirect: /page-a → /page-b
    Redirect::create([
        'old_url' => '/page-a',
        'new_url' => '/page-b',
        'status_code' => 301,
    ]);

    // Try to create second redirect: /page-b → /page-a (would create loop)
    $redirect = new Redirect([
        'old_url' => '/page-b',
        'new_url' => '/page-a',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeTrue();
});

it('detects three-way circular loop (A → B → C → A)', function () {
    // Create: /page-a → /page-b
    Redirect::create([
        'old_url' => '/page-a',
        'new_url' => '/page-b',
        'status_code' => 301,
    ]);

    // Create: /page-b → /page-c
    Redirect::create([
        'old_url' => '/page-b',
        'new_url' => '/page-c',
        'status_code' => 301,
    ]);

    // Try to create: /page-c → /page-a (would complete the loop)
    $redirect = new Redirect([
        'old_url' => '/page-c',
        'new_url' => '/page-a',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeTrue();
});

it('allows valid redirect chains without loops', function () {
    // Create: /old-1 → /old-2
    Redirect::create([
        'old_url' => '/old-1',
        'new_url' => '/old-2',
        'status_code' => 301,
    ]);

    // Create: /old-2 → /final
    Redirect::create([
        'old_url' => '/old-2',
        'new_url' => '/final',
        'status_code' => 301,
    ]);

    // Create: /old-3 → /final (no loop)
    $redirect = new Redirect([
        'old_url' => '/old-3',
        'new_url' => '/final',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeFalse();
});

it('allows redirect to a URL that is not in the redirects table', function () {
    $redirect = new Redirect([
        'old_url' => '/old-page',
        'new_url' => '/actual-page',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeFalse();
});

it('prevents deep circular loops (more than 10 levels)', function () {
    // Create a chain: /page-1 → /page-2 → ... → /page-11
    for ($i = 1; $i <= 11; $i++) {
        Redirect::create([
            'old_url' => "/page-{$i}",
            'new_url' => '/page-' . ($i + 1),
            'status_code' => 301,
        ]);
    }

    // Try to create: /page-12 → /page-1 (would create very deep loop)
    $redirect = new Redirect([
        'old_url' => '/page-12',
        'new_url' => '/page-1',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeTrue();
});

it('allows updating existing redirect without false loop detection', function () {
    // Create initial redirect
    $redirect = Redirect::create([
        'old_url' => '/old-page',
        'new_url' => '/new-page',
        'status_code' => 301,
    ]);

    // Update the same redirect (should not detect a loop with itself)
    $redirect->new_url = '/updated-page';

    expect($redirect->wouldCreateLoop($redirect->id))->toBeFalse();
});

it('detects loop when updating redirect to create circular reference', function () {
    // Create: /page-a → /page-b
    $redirectA = Redirect::create([
        'old_url' => '/page-a',
        'new_url' => '/page-b',
        'status_code' => 301,
    ]);

    // Create: /page-b → /page-c
    $redirectB = Redirect::create([
        'old_url' => '/page-b',
        'new_url' => '/page-c',
        'status_code' => 301,
    ]);

    // Try to update redirectB to point back to /page-a (would create loop)
    $redirectB->new_url = '/page-a';

    expect($redirectB->wouldCreateLoop($redirectB->id))->toBeTrue();
});

it('handles complex redirect chains correctly', function () {
    // Create a complex but valid chain:
    // /a → /b
    // /b → /c
    // /c → /d
    // /e → /d
    // /f → /e
    Redirect::create(['old_url' => '/a', 'new_url' => '/b', 'status_code' => 301]);
    Redirect::create(['old_url' => '/b', 'new_url' => '/c', 'status_code' => 301]);
    Redirect::create(['old_url' => '/c', 'new_url' => '/d', 'status_code' => 301]);
    Redirect::create(['old_url' => '/e', 'new_url' => '/d', 'status_code' => 301]);
    Redirect::create(['old_url' => '/f', 'new_url' => '/e', 'status_code' => 301]);

    // Try to add: /d → /final (should be fine)
    $redirect = new Redirect([
        'old_url' => '/d',
        'new_url' => '/final',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeFalse();
});

it('detects loop when trying to close a complex chain', function () {
    // Create a chain:
    // /a → /b → /c → /d
    Redirect::create(['old_url' => '/a', 'new_url' => '/b', 'status_code' => 301]);
    Redirect::create(['old_url' => '/b', 'new_url' => '/c', 'status_code' => 301]);
    Redirect::create(['old_url' => '/c', 'new_url' => '/d', 'status_code' => 301]);

    // Try to add: /d → /b (would create loop: b → c → d → b)
    $redirect = new Redirect([
        'old_url' => '/d',
        'new_url' => '/b',
        'status_code' => 301,
    ]);

    expect($redirect->wouldCreateLoop())->toBeTrue();
});
