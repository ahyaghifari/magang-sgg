<?php

namespace App\Filament\Resources\AccessScanLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AccessScanLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scanned_at')
                    ->label('Waktu scan')
                    ->dateTime('D, d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('employee_id')
                    ->label('ID mesin')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('matched')
                    ->label('Cocok')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Dikenal' : 'Tidak dikenal')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                TextColumn::make('intern.nama')
                    ->label('Nama')
                    ->placeholder('— tidak dikenal')
                    ->searchable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('device_name')
                    ->label('Perangkat')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('scanned_at', 'desc')
            ->filters([
                TernaryFilter::make('matched')
                    ->label('Status kecocokan')
                    ->placeholder('Semua')
                    ->trueLabel('Hanya yang dikenal')
                    ->falseLabel('Hanya yang tidak dikenal'),
                Filter::make('scanned_at')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('scanned_at', '>=', $d))
                        ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('scanned_at', '<=', $d))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
