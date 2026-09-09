<?php

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Filament\Resources\AttendanceRecords\Tables\AttendanceRecordsTable;
use App\Models\AttendanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Rekap presensi harian (hasil `attendance:sync`). Read-only: baris di sini
 * ditulis oleh pipeline, bukan diinput manual.
 */
class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Rekap Presensi';

    protected static ?string $modelLabel = 'Rekap Presensi';

    protected static ?string $pluralModelLabel = 'Rekap Presensi';

    protected static ?int $navigationSort = 31;

    public static function table(Table $table): Table
    {
        return AttendanceRecordsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRecords::route('/'),
        ];
    }
}
