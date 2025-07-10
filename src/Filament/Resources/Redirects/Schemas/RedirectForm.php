<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('old_url')->label(__('redirects::trans.from'))->live()
                    ->required()->afterStateUpdated(function ($set, $state) {
                        $path = parse_url($state, PHP_URL_PATH);
                        $set('old_url', $path ?: '/');
                    })->unique(ignoreRecord: true),
                TextInput::make('new_url')->label(__('redirects::trans.to'))->required()->live()->afterStateUpdated(function ($set, $state) {
                    $path = parse_url($state, PHP_URL_PATH);
                    $set('new_url', $path ?: '/');
                }),
                Radio::make('status_code')->label(__('redirects::trans.type'))->inline()->options([
                    '301' => '301',
                    '302' => '302',
                ])->default('301')->required(),
            ]);
    }
}
