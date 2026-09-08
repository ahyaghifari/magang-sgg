<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge(),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (User $record): string => $record->approved_at ? 'Disetujui' : 'Menunggu')
                    ->color(fn (string $state): string => $state === 'Disetujui' ? 'success' : 'warning')
                    ->icon(fn (string $state): string => $state === 'Disetujui' ? 'heroicon-m-check-circle' : 'heroicon-m-clock'),
                TextColumn::make('approved_at')
                    ->label('Disetujui pada')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('approvedBy.name')
                    ->label('Disetujui oleh')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Daftar pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(UserRole::class),
                TernaryFilter::make('approved_at')
                    ->label('Status persetujuan')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah disetujui')
                    ->falseLabel('Menunggu persetujuan'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->approved_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pendaftaran')
                    ->modalDescription(fn (User $record): string => "Akun {$record->email} akan bisa login setelah disetujui.")
                    ->action(fn (User $record) => $record->update([
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ])),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->approved_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Tolak pendaftaran')
                    ->modalDescription(fn (User $record): string => "Akun pendaftar {$record->email} akan dihapus permanen.")
                    ->action(fn (User $record) => $record->delete()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Setujui terpilih')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records) => $records->each(fn (User $record) => $record->update([
                            'approved_at' => $record->approved_at ?? now(),
                            'approved_by' => $record->approved_by ?? auth()->id(),
                        ]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
