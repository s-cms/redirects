<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use SmartCms\Redirects\Models\Redirect;

beforeEach(function () {
    Storage::fake('local');
});

it('exports redirects to CSV format', function () {
    Redirect::create([
        'old_url' => '/old-1',
        'new_url' => '/new-1',
        'status_code' => 301,
        'hit_count' => 100,
    ]);

    Redirect::create([
        'old_url' => '/old-2',
        'new_url' => '/new-2',
        'status_code' => 302,
        'hit_count' => 50,
    ]);

    $redirects = Redirect::all();

    // Simulate CSV export
    $csv = tmpfile();
    fputcsv($csv, ['old_url', 'new_url', 'status_code', 'hit_count']);

    foreach ($redirects as $redirect) {
        fputcsv($csv, [
            $redirect->old_url,
            $redirect->new_url,
            $redirect->status_code,
            $redirect->hit_count,
        ]);
    }

    rewind($csv);
    $content = stream_get_contents($csv);
    fclose($csv);

    expect($content)->toContain('old_url,new_url,status_code,hit_count');
    expect($content)->toContain('/old-1,/new-1,301,100');
    expect($content)->toContain('/old-2,/new-2,302,50');
});

it('imports redirects from CSV format', function () {
    // Create a test CSV file
    $csvContent = "old_url,new_url,status_code\n";
    $csvContent .= "/old-1,/new-1,301\n";
    $csvContent .= "/old-2,/new-2,302\n";
    $csvContent .= "/old-3,/new-3,301\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    rewind($file);

    // Read and parse CSV
    $header = fgetcsv($file);
    $imported = 0;

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 2) {
            continue;
        }

        $redirect = new Redirect([
            'old_url' => $row[0] ?? '',
            'new_url' => $row[1] ?? '',
            'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
        ]);

        if (! $redirect->wouldCreateLoop()) {
            $redirect->save();
            $imported++;
        }
    }

    fclose($file);

    expect($imported)->toBe(3);
    expect(Redirect::count())->toBe(3);

    $redirect1 = Redirect::where('old_url', '/old-1')->first();
    expect($redirect1)->not->toBeNull();
    expect($redirect1->new_url)->toBe('/new-1');
    expect($redirect1->status_code)->toBe(301);
});

it('skips invalid rows during import', function () {
    $csvContent = "old_url,new_url,status_code\n";
    $csvContent .= "/valid-1,/new-1,301\n";
    $csvContent .= "\n"; // Empty row
    $csvContent .= "/valid-2,/new-2,302\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    rewind($file);

    $header = fgetcsv($file);
    $imported = 0;

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 2) {
            continue;
        }

        $redirect = new Redirect([
            'old_url' => $row[0] ?? '',
            'new_url' => $row[1] ?? '',
            'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
        ]);

        if (! $redirect->wouldCreateLoop()) {
            $redirect->save();
            $imported++;
        }
    }

    fclose($file);

    expect($imported)->toBe(2);
});

it('uses default status code 301 when not provided in import', function () {
    $csvContent = "old_url,new_url\n";
    $csvContent .= "/old-1,/new-1\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    rewind($file);

    $header = fgetcsv($file);
    $row = fgetcsv($file);

    $redirect = new Redirect([
        'old_url' => $row[0] ?? '',
        'new_url' => $row[1] ?? '',
        'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
    ]);

    $redirect->save();

    fclose($file);

    expect($redirect->status_code)->toBe(301);
});

it('prevents importing redirects that would create loops', function () {
    // Create existing redirect: /page-a → /page-b
    Redirect::create([
        'old_url' => '/page-a',
        'new_url' => '/page-b',
        'status_code' => 301,
    ]);

    // Try to import: /page-b → /page-a (would create loop)
    $csvContent = "old_url,new_url,status_code\n";
    $csvContent .= "/page-b,/page-a,301\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    rewind($file);

    $header = fgetcsv($file);
    $imported = 0;
    $skipped = 0;

    while (($row = fgetcsv($file)) !== false) {
        if (count($row) < 2) {
            continue;
        }

        $redirect = new Redirect([
            'old_url' => $row[0] ?? '',
            'new_url' => $row[1] ?? '',
            'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
        ]);

        if ($redirect->wouldCreateLoop()) {
            $skipped++;

            continue;
        }

        $redirect->save();
        $imported++;
    }

    fclose($file);

    expect($skipped)->toBe(1);
    expect($imported)->toBe(0);
    expect(Redirect::count())->toBe(1); // Only the original redirect
});

it('updates existing redirects during import if old_url already exists', function () {
    // Create existing redirect
    $existing = Redirect::create([
        'old_url' => '/existing',
        'new_url' => '/old-target',
        'status_code' => 301,
    ]);

    // Import CSV with same old_url but different new_url
    $csvContent = "old_url,new_url,status_code\n";
    $csvContent .= "/existing,/new-target,302\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    rewind($file);

    $header = fgetcsv($file);
    $row = fgetcsv($file);

    $redirect = new Redirect([
        'old_url' => $row[0] ?? '',
        'new_url' => $row[1] ?? '',
        'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
    ]);

    $existingRedirect = Redirect::where('old_url', $redirect->old_url)->first();
    if ($existingRedirect) {
        $existingRedirect->update([
            'new_url' => $redirect->new_url,
            'status_code' => $redirect->status_code,
        ]);
    } else {
        $redirect->save();
    }

    fclose($file);

    expect(Redirect::count())->toBe(1);

    $updated = Redirect::where('old_url', '/existing')->first();
    expect($updated->new_url)->toBe('/new-target');
    expect($updated->status_code)->toBe(302);
});

it('can export and re-import redirects', function () {
    // Create redirects
    Redirect::create([
        'old_url' => '/export-1',
        'new_url' => '/target-1',
        'status_code' => 301,
        'hit_count' => 100,
    ]);

    Redirect::create([
        'old_url' => '/export-2',
        'new_url' => '/target-2',
        'status_code' => 302,
        'hit_count' => 50,
    ]);

    // Export to CSV
    $redirects = Redirect::all();
    $csv = tmpfile();
    fputcsv($csv, ['old_url', 'new_url', 'status_code', 'hit_count']);

    foreach ($redirects as $redirect) {
        fputcsv($csv, [
            $redirect->old_url,
            $redirect->new_url,
            $redirect->status_code,
            $redirect->hit_count,
        ]);
    }

    // Clear database
    Redirect::truncate();
    expect(Redirect::count())->toBe(0);

    // Re-import from CSV
    rewind($csv);
    $header = fgetcsv($csv);

    while (($row = fgetcsv($csv)) !== false) {
        Redirect::create([
            'old_url' => $row[0],
            'new_url' => $row[1],
            'status_code' => (int) $row[2],
            'hit_count' => (int) ($row[3] ?? 0),
        ]);
    }

    fclose($csv);

    expect(Redirect::count())->toBe(2);

    $redirect1 = Redirect::where('old_url', '/export-1')->first();
    expect($redirect1->hit_count)->toBe(100);

    $redirect2 = Redirect::where('old_url', '/export-2')->first();
    expect($redirect2->hit_count)->toBe(50);
});
