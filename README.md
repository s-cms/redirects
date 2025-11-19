# This is my package redirects

[![Latest Version on Packagist](https://img.shields.io/packagist/v/smart-cms/redirects.svg?style=flat-square)](https://packagist.org/packages/smart-cms/redirects)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/smart-cms/redirects/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/smart-cms/redirects/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/smart-cms/redirects/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/smart-cms/redirects/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/smart-cms/redirects.svg?style=flat-square)](https://packagist.org/packages/smart-cms/redirects)



A Laravel/Filament v4 package that provides comprehensive URL redirect management with automatic redirect handling, hit tracking, loop detection, and a powerful Filament admin panel for managing redirects.

## Features

- **Automatic Redirects**: Middleware automatically handles URL redirects (301 & 302)
- **Hit Tracking**: Track redirect usage with hit count and last hit timestamp
- **Loop Detection**: Prevents creating circular redirect loops (A → B → A)
- **Caching**: Built-in caching support for optimal performance
- **Filament Resource**: Full-featured admin panel with inline create/edit modals
- **Import/Export**: Bulk operations via CSV import and export
- **Validation**: Prevents duplicate old_urls and validates redirect chains

## Installation

You can install the package via composer:

```bash
composer require smart-cms/redirects
```

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file or your app's css file if using the standalone packages.

```css
@source '../../../../vendor/smart-cms/redirects/resources/**/*.blade.php';
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="redirects-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="redirects-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="redirects-views"
```

This is the contents of the published config file:

```php
return [
    // Database table name for redirects
    'table_name' => 'redirects',

    // Cache configuration
    'cache' => [
        'enabled' => true,              // Enable/disable caching
        'ttl' => 60 * 60 * 24,         // Cache TTL in seconds (24 hours)
        'key' => 'redirects_cache',    // Cache key
    ],
];
```

## Usage

### Register the Filament Plugin

Add the plugin to your Filament panel provider:

```php
use SmartCms\Redirects\RedirectsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            RedirectsPlugin::make(),
            // Or with custom navigation group:
            RedirectsPlugin::make('Settings'),
        ]);
}
```

### Middleware

The redirect middleware is automatically registered to the `web` middleware group. When a user visits a URL that matches a redirect's `old_url`, they will be automatically redirected to the `new_url` with the specified status code (301 or 302).

### Creating Redirects Programmatically

```php
use SmartCms\Redirects\Models\Redirect;

// Create a permanent redirect (301)
Redirect::create([
    'old_url' => '/old-page',
    'new_url' => '/new-page',
    'status_code' => 301,
]);

// Create a temporary redirect (302)
Redirect::create([
    'old_url' => '/temporary',
    'new_url' => '/new-location',
    'status_code' => 302,
]);
```

### Hit Tracking

The package automatically tracks how many times each redirect is used:

```php
$redirect = Redirect::find(1);

echo $redirect->hit_count;      // Number of times this redirect was used
echo $redirect->last_hit_at;    // Carbon instance of last hit

// Find popular redirects
$popular = Redirect::where('hit_count', '>', 100)->get();

// Find recently used redirects
$recent = Redirect::where('last_hit_at', '>', now()->subDays(7))->get();

// Find unused redirects
$unused = Redirect::where('hit_count', 0)
    ->whereNull('last_hit_at')
    ->get();
```

### Loop Detection

The package prevents creating circular redirect loops:

```php
// This will be prevented:
Redirect::create(['old_url' => '/page', 'new_url' => '/page']); // Self-loop

// If you have: /a → /b
// This will be prevented: /b → /a (creates circular loop)

// The validation also detects complex loops:
// /a → /b → /c → /a (prevents closing the loop)
```

### Import/Export

Use the Filament admin panel to:

- **Export All**: Export all redirects to CSV via the header "Export CSV" button
- **Export Selected**: Select specific redirects and use the bulk action "Export Selected"
- **Import CSV**: Click "Import CSV" and upload a CSV file with columns: `old_url`, `new_url`, `status_code`

CSV Format:
```csv
old_url,new_url,status_code,hit_count
/old-page,/new-page,301,0
/temporary,/destination,302,0
```

### Caching

Redirects are cached for performance. The cache is automatically cleared when:
- A redirect is created
- A redirect is updated
- A redirect is deleted

To manually clear the cache:

```php
Redirect::clearCache();
```

To disable caching:

```php
// In config/redirects.php
'cache' => [
    'enabled' => false,
],
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [maxboyko](https://github.com/SmartCms)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
