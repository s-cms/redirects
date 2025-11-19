<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class RedirectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('old_url')->label(__('redirects::trans.from'))->searchable(),
                TextColumn::make('new_url')->label(__('redirects::trans.to'))->searchable(),
                TextColumn::make('status_code')->label(__('redirects::trans.type'))->badge(),
                TextColumn::make('hit_count')->label(__('redirects::trans.hit_count'))->sortable()->default(0),
                TextColumn::make('last_hit_at')->label(__('redirects::trans.last_hit_at'))->since()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('redirects::trans.updated_at'))->since()->toggleable(isToggledHiddenByDefault: true),
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
                    BulkAction::make('export_selected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $fileName = 'redirects-' . now()->format('Y-m-d-His') . '.csv';

                            $csv = tmpfile();

                            // Write headers
                            fputcsv($csv, ['old_url', 'new_url', 'status_code', 'hit_count']);

                            // Write data
                            foreach ($records as $redirect) {
                                fputcsv($csv, [
                                    $redirect->old_url,
                                    $redirect->new_url,
                                    $redirect->status_code,
                                    $redirect->hit_count,
                                ]);
                            }

                            rewind($csv);

                            return response()->streamDownload(function () use ($csv) {
                                echo stream_get_contents($csv);
                                fclose($csv);
                            }, $fileName, [
                                'Content-Type' => 'text/csv',
                            ]);
                        }),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
