<?php

namespace App\Filament\Resources\Shifts;

use App\Filament\Resources\Shifts\Pages\CreateShift;
use App\Filament\Resources\Shifts\Pages\EditShift;
use App\Filament\Resources\Shifts\Pages\ListShifts;
use App\Filament\Resources\Shifts\Schemas\ShiftForm;
use App\Filament\Resources\Shifts\Tables\ShiftsTable;
use App\Models\Shift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Menu "Shift" — daftar shift per perusahaan (jenis Pagi/Siang beserta jam & toleransi) dan intern
 * yang terdaftar di tiap shift. Jadwal harian diisi Pembimbing/Mentor di portal. Hanya super admin
 * (lihat ShiftPolicy).
 */
class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Shift';

    protected static ?string $modelLabel = 'Shift';

    protected static ?string $pluralModelLabel = 'Shift';

    // Tepat setelah "Jadwal Kerja" (30).
    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShifts::route('/'),
            'create' => CreateShift::route('/create'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }
}
