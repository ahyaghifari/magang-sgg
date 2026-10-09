<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Jenis shift')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Masuk')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('Pulang')
                    ->time('H:i'),
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
                TextColumn::make('checkin_buffer_minutes')
                    ->label('Jendela masuk')
                    ->suffix(' mnt')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checkout_buffer_minutes')
                    ->label('Jendela pulang')
                    ->suffix(' mnt')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_time')
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
                    // Shift yang sudah dipakai jadwal intern dilewati (lihat ShiftPolicy::delete()).
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->emptyStateHeading('Belum ada shift')
            ->emptyStateDescription('Tambahkan shift (Pagi, Siang, atau Malam) untuk perusahaan yang memakai jadwal shift. Intern yang memakai shift dipilih di data Intern (Memakai jadwal shift: Ya).');
    }
}
