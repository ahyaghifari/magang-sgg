<?php

namespace App\Filament\Resources\CompanyFixedSchedules\Tables;

use App\Filament\Resources\CompanyFixedSchedules\CompanyFixedScheduleResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyFixedSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->formatStateUsing(fn ($state) => CompanyFixedScheduleResource::DAYS[$state] ?? $state)
                    ->sortable(),
                IconColumn::make('is_off_day')
                    ->label('Libur')
                    ->boolean(),
                TextColumn::make('start_time')
                    ->label('Masuk')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('end_time')
                    ->label('Pulang')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('break_minutes')
                    ->label('Istirahat')
                    ->suffix(' mnt')
                    ->toggleable(),
                TextColumn::make('late_tolerance_minutes')
                    ->label('Tol. telat')
                    ->suffix(' mnt')
                    ->toggleable(),
                TextColumn::make('early_leave_tolerance_minutes')
                    ->label('Tol. pulang cepat')
                    ->suffix(' mnt')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('day_of_week')
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
