<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Imports\ProductsImport;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ImportProducts')
                ->label('Import Products')
                ->icon('heroicon-s-arrow-up-tray')
                ->color('danger')
                ->form([
                    FileUpload::make('attachment')
                    ->label('Upload Template Product')
                    ->rules('required', 'mimes:xlsx'),
                ])
                ->action(function (array $data) {
                    $file = public_path('storage/' . $data['attachment']);

                    try {
                        Excel::import(new ProductsImport, $file);
                        Notification::make()
                            ->success()
                            ->title('Products imported successfully')
                            ->send();
                    } catch (\Throwable $th) {
                        Notification::make()
                            ->danger()
                            ->title('Failed to import products,'. $th->getMessage())
                            ->send();
                    }
                }),
                Action::make('Download Template')
                ->url(route('download-template'))
                ->icon('heroicon-s-arrow-down-tray')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
