# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Laravel/Filament v4 package that provides URL redirect management with a middleware for automatic redirects and a Filament admin panel resource.

## Commands

```bash
# Run tests (Pest)
composer test

# Lint code with Pint
composer lint

# Check lint without fixing
composer test:lint

# Static analysis with PHPStan (level 4)
composer analyse
```

## Architecture

### Core Components

- **RedirectsServiceProvider** (`src/RedirectsServiceProvider.php`) - Package service provider using spatie/laravel-package-tools. Registers the `RedirectMiddleware` to the `web` middleware group.

- **RedirectsPlugin** (`src/RedirectsPlugin.php`) - Filament v4 plugin that registers the RedirectResource. Use `RedirectsPlugin::make()` to add to a Filament panel.

- **RedirectMiddleware** (`src/Http/Middlewares/RedirectMiddleware.php`) - Intercepts requests and performs redirects based on `old_url` matches in the database.

- **Redirect Model** (`src/Models/Redirect.php`) - Eloquent model with `old_url`, `new_url`, `status_code` fields. Table name is configurable via `config('redirects.table_name')`.

### Filament Resource Structure

Forms and tables are extracted to separate classes (following the pattern for complex resources):

- `src/Filament/Resources/Redirects/RedirectResource.php` - Main resource
- `src/Filament/Resources/Redirects/Schemas/RedirectForm.php` - Form definition
- `src/Filament/Resources/Redirects/Tables/RedirectsTable.php` - Table definition
- `src/Filament/Resources/Redirects/Pages/ListRedirects.php` - List page (with inline create/edit modals)

### Configuration

Config file at `config/redirects.php` with:
- `table_name` - Database table name (default: 'redirects')

### Translations

Located in `resources/lang/{locale}/trans.php`

## Testing

Uses Pest with Orchestra Testbench. Test base class at `tests/TestCase.php` includes all required Filament service providers and uses `LazilyRefreshDatabase` and `WithWorkbench` traits.
