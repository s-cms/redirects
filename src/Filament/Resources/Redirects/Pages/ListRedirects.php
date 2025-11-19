<?php

namespace SmartCms\Redirects\Filament\Resources\Redirects\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use SmartCms\Redirects\Filament\Resources\Redirects\RedirectResource;
use SmartCms\Redirects\Models\Redirect;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    return $this->exportToCsv();
                }),
            Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required()
                        ->helperText('Upload a CSV file with columns: old_url, new_url, status_code'),
                ])
                ->action(function (array $data) {
                    $this->importFromCsv($data['file']);
                }),
            CreateAction::make(),
        ];
    }

    protected function exportToCsv()
    {
        $redirects = Redirect::all();
        $fileName = 'redirects-' . now()->format('Y-m-d-His') . '.csv';

        $csv = tmpfile();
        $csvPath = stream_get_meta_data($csv)['uri'];

        // Write headers
        fputcsv($csv, ['old_url', 'new_url', 'status_code', 'hit_count']);

        // Write data
        foreach ($redirects as $redirect) {
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
    }

    protected function importFromCsv(string $filePath)
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            Notification::make()
                ->title('Error')
                ->body('File not found')
                ->danger()
                ->send();

            return;
        }

        $file = fopen($fullPath, 'r');
        $header = fgetcsv($file);
        $imported = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            try {
                $redirect = new Redirect([
                    'old_url' => $row[0] ?? '',
                    'new_url' => $row[1] ?? '',
                    'status_code' => isset($row[2]) && in_array($row[2], [301, 302, '301', '302']) ? (int) $row[2] : 301,
                ]);

                // Check for loops
                if ($redirect->wouldCreateLoop()) {
                    $errors[] = "Skipped redirect {$redirect->old_url} -> {$redirect->new_url} (would create a loop)";

                    continue;
                }

                // Check for duplicates
                $existing = Redirect::where('old_url', $redirect->old_url)->first();
                if ($existing) {
                    $existing->update([
                        'new_url' => $redirect->new_url,
                        'status_code' => $redirect->status_code,
                    ]);
                } else {
                    $redirect->save();
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Error importing row: {$e->getMessage()}";
            }
        }

        fclose($file);

        // Clean up the uploaded file
        Storage::disk('local')->delete($filePath);

        $message = "Successfully imported {$imported} redirects.";
        if (! empty($errors)) {
            $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= "\n...and " . (count($errors) - 5) . ' more';
            }
        }

        Notification::make()
            ->title('Import Complete')
            ->body($message)
            ->success()
            ->send();
    }
}
