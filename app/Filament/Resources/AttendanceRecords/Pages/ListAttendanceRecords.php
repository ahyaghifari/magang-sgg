<?php

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ListAttendanceRecords extends ListRecords
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync Sidik Jari')
                ->icon(Heroicon::ArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync data sidik jari')
                ->modalDescription('Menarik tap terbaru dari mesin sidik jari (attendance:sync) lalu memperbarui rekap presensi.')
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
