<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('old_url')->label(__('redirects::trans.from'))->searchable(),
                TextColumn::make('new_url')->label(__('redirects::trans.to'))->searchable(),
                TextColumn::make('status_code')->label(__('redirects::trans.type'))->badge(),
                TextColumn::make('updated_at')->label(__('redirects::trans.updated_at'))->since(),
                TextColumn::make('created_at')->label(__('redirects::trans.created_at'))->toggleable(isToggledHiddenByDefault: true)->since(),
            ])
            ->filters([
                SelectFilter::make('status_code')->options([
                    '301' => '301',
                    '302' => '302',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
