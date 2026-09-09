<?php

namespace App\Filament\Resources\AttendanceRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('D, d M Y')
                    ->sortable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('intern.nama')
                    ->label('Nama')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('company.name')
                    ->label('Perusahaan')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('check_in_time')
                    ->label('Masuk')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('check_out_time')
                    ->label('Pulang')
                    ->time('H:i')
                    ->placeholder('—'),
                TextColumn::make('late_minutes')
                    ->label('Telat')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state.' mnt' : '—')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('early_leave_minutes')
                    ->label('Pulang cepat')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state.' mnt' : '—')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->toggleable(),
                TextColumn::make('working_minutes')
                    ->label('Kerja')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : intdiv($state, 60).'j '.($state % 60).'m')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'present' => 'Hadir',
                        'late' => 'Telat',
                        'absent' => 'Alfa',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('out_of_window')
                    ->label('Di luar jam wajar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'present' => 'Hadir',
                        'late' => 'Telat',
                        'absent' => 'Alfa',
                    ]),
                SelectFilter::make('company_id')
                    ->label('Perusahaan')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('date', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('date', '<=', $d));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
