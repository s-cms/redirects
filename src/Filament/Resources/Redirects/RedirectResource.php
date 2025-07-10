<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects;

use SmartCms\Redirects\Filament\Resources\Redirects\Pages\ListRedirects;
use SmartCms\Redirects\Filament\Resources\Redirects\Schemas\RedirectForm;
use SmartCms\Redirects\Filament\Resources\Redirects\Tables\RedirectsTable;
use SmartCms\Redirects\Models\Redirect;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use SmartCms\Redirects\RedirectsPlugin;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightEndOnRectangle;

    protected static ?int $navigationSort = 99;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        if (RedirectsPlugin::$navigationGroup) {
            return __(RedirectsPlugin::$navigationGroup);
        }
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return RedirectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RedirectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRedirects::route('/'),
        ];
    }
}
