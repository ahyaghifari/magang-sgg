<?php

namespace App\Filament\Resources\CompanyFixedSchedules;

use App\Filament\Resources\CompanyFixedSchedules\Pages\CreateCompanyFixedSchedule;
use App\Filament\Resources\CompanyFixedSchedules\Pages\EditCompanyFixedSchedule;
use App\Filament\Resources\CompanyFixedSchedules\Pages\ListCompanyFixedSchedules;
use App\Filament\Resources\CompanyFixedSchedules\Schemas\CompanyFixedScheduleForm;
use App\Filament\Resources\CompanyFixedSchedules\Tables\CompanyFixedSchedulesTable;
use App\Models\CompanyFixedSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyFixedScheduleResource extends Resource
{
    protected static ?string $model = CompanyFixedSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Jadwal Kerja';

    protected static ?string $modelLabel = 'Jadwal Kerja';

    protected static ?string $pluralModelLabel = 'Jadwal Kerja';

    protected static ?int $navigationSort = 30;

    /** 0=Minggu … 6=Sabtu, cocok dengan Carbon::dayOfWeek. */
    public const DAYS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public static function form(Schema $schema): Schema
    {
        return CompanyFixedScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyFixedSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyFixedSchedules::route('/'),
            'create' => CreateCompanyFixedSchedule::route('/create'),
            'edit' => EditCompanyFixedSchedule::route('/{record}/edit'),
        ];
    }
}
