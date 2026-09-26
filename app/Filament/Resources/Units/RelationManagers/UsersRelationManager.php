<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Daftar pegawai (akun non-intern: pembimbing, mentor, pimpinan, admin) di unit ini —
 * READ-ONLY (unit pegawai diubah dari halaman Pengguna). Klik baris untuk membuka
 * halaman edit pengguna tersebut.
 */
class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Pegawai';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->where('role', '!=', UserRole::Intern))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(collect(UserRole::cases())
                        ->reject(fn (UserRole $role) => $role === UserRole::Intern)
                        ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->getLabel()])
                        ->all()),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading('Belum ada pegawai di unit ini');
    }
}
