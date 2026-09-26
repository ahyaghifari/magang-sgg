<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Interns\InternResource;
use App\Models\Intern;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Daftar intern yang ditempatkan di unit ini — READ-ONLY (penempatan diubah dari
 * halaman Intern). Klik baris untuk membuka halaman edit intern tersebut.
 */
class InternsRelationManager extends RelationManager
{
    protected static string $relationship = 'interns';

    protected static ?string $title = 'Intern';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama')
            ->modifyQueryUsing(fn ($query) => $query->with(['institusi', 'pembimbing', 'mentor']))
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('institusi.name')
                    ->label('Institusi')
                    ->placeholder('—'),
                TextColumn::make('pembimbing.name')
                    ->label('Pembimbing')
                    ->placeholder('—'),
                TextColumn::make('mentor.name')
                    ->label('Mentor')
                    ->placeholder('—'),
                TextColumn::make('tanggal_mulai')
                    ->label('Magang Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tanggal_selesai')
                    ->label('Magang Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status_magang')
                    ->label('Status')
                    ->state(fn (Intern $record): string => $record->tanggal_selesai && $record->tanggal_selesai->isPast() ? 'Selesai' : 'Berjalan')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Selesai' ? 'gray' : 'success'),
            ])
            ->defaultSort('nama')
            ->recordUrl(fn (Intern $record): string => InternResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Belum ada intern di unit ini');
    }
}
