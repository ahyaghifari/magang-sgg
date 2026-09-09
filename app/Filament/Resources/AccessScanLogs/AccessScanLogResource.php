<?php

namespace App\Filament\Resources\AccessScanLogs;

use App\Filament\Resources\AccessScanLogs\Pages\ListAccessScanLogs;
use App\Filament\Resources\AccessScanLogs\Tables\AccessScanLogsTable;
use App\Models\AccessScanLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Log mentah tap sidik jari — semua scan, termasuk yang NIP-nya tak dikenal.
 * Read-only: diisi oleh `attendance:sync`.
 */
class AccessScanLogResource extends Resource
{
    protected static ?string $model = AccessScanLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $navigationLabel = 'Log Sidik Jari';

    protected static ?string $modelLabel = 'Log Sidik Jari';

    protected static ?string $pluralModelLabel = 'Log Sidik Jari';

    protected static ?int $navigationSort = 32;

    public static function table(Table $table): Table
    {
        return AccessScanLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccessScanLogs::route('/'),
        ];
    }
}
