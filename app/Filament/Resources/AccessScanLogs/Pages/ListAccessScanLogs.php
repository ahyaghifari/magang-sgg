<?php

namespace App\Filament\Resources\AccessScanLogs\Pages;

use App\Filament\Resources\AccessScanLogs\AccessScanLogResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ListAccessScanLogs extends ListRecords
{
    protected static string $resource = AccessScanLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync Sidik Jari')
                ->icon(Heroicon::ArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync data sidik jari')
                ->modalDescription('Menarik tap terbaru dari mesin sidik jari (attendance:sync). Proses ini bisa memakan beberapa detik.')
                ->action(function () {
                    $code = Artisan::call('attendance:sync');
                    $out = trim(Artisan::output());

                    Notification::make()
                        ->title($code === 0 ? 'Sinkron selesai' : 'Sinkron gagal')
                        ->body($out !== '' ? $out : 'Tidak ada keluaran.')
                        ->{$code === 0 ? 'success' : 'danger'}()
                        ->send();

                    $this->resetTable();
                }),
        ];
    }
}
