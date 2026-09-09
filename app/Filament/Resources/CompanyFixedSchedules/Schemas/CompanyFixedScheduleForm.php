<?php

namespace App\Filament\Resources\CompanyFixedSchedules\Schemas;

use App\Filament\Resources\CompanyFixedSchedules\CompanyFixedScheduleResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyFixedScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('day_of_week')
                    ->label('Hari')
                    ->options(CompanyFixedScheduleResource::DAYS)
                    ->required(),
                Toggle::make('is_off_day')
                    ->label('Hari libur')
                    ->helperText('Bila aktif, scan pada hari ini diabaikan (tidak menghasilkan rekap).')
                    ->live()
                    ->default(false),
                TimePicker::make('start_time')
                    ->label('Jam masuk')
                    ->seconds(false)
                    ->required(fn ($get) => ! $get('is_off_day'))
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TimePicker::make('end_time')
                    ->label('Jam pulang')
                    ->seconds(false)
                    ->required(fn ($get) => ! $get('is_off_day'))
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TextInput::make('break_minutes')
                    ->label('Istirahat (menit)')
                    ->helperText('Dipotong dari durasi kerja.')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TextInput::make('late_tolerance_minutes')
                    ->label('Toleransi telat (menit)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TextInput::make('early_leave_tolerance_minutes')
                    ->label('Toleransi pulang cepat (menit)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TextInput::make('checkin_buffer_minutes')
                    ->label('Buffer sebelum jam masuk (menit)')
                    ->helperText('Hanya untuk menandai "di luar jam wajar".')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('is_off_day')),
                TextInput::make('checkout_buffer_minutes')
                    ->label('Buffer setelah jam pulang (menit)')
                    ->helperText('Hanya untuk menandai "di luar jam wajar".')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->visible(fn ($get) => ! $get('is_off_day')),
            ]);
    }
}
