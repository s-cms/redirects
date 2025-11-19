<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use SmartCms\Redirects\Models\Redirect;

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
                    })
                    ->unique(ignoreRecord: true)
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $newUrl = $get('new_url');

                            // Only validate if both URLs are set
                            if (! $newUrl) {
                                return;
                            }

                            // Self-loop check
                            if ($value === $newUrl) {
                                $fail('The redirect cannot point to itself.');
                            }
                        },
                    ]),
                TextInput::make('new_url')->label(__('redirects::trans.to'))->required()->live()
                    ->afterStateUpdated(function ($set, $state) {
                        $path = parse_url($state, PHP_URL_PATH);
                        $set('new_url', $path ?: '/');
                    })
                    ->rules([
                        fn (Get $get, $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $oldUrl = $get('old_url');

                            // Only validate if both URLs are set
                            if (! $oldUrl) {
                                return;
                            }

                            // Self-loop check
                            if ($value === $oldUrl) {
                                $fail('The redirect cannot point to itself.');

                                return;
                            }

                            // Create a temporary redirect to check for loops
                            $redirect = new Redirect([
                                'old_url' => $oldUrl,
                                'new_url' => $value,
                                'status_code' => $get('status_code') ?? 301,
                            ]);

                            if ($record) {
                                $redirect->id = $record->id;
                            }

                            if ($redirect->wouldCreateLoop($record?->id)) {
                                $fail('This redirect would create a circular loop.');
                            }
                        },
                    ]),
                Radio::make('status_code')->label(__('redirects::trans.type'))->inline()->options([
                    '301' => '301',
                    '302' => '302',
                ])->default('301')->required(),
            ]);
    }
}
