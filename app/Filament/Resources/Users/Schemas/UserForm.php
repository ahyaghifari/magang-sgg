<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('role')
                    ->label('Peran')
                    ->options(UserRole::class)
                    ->default(UserRole::User)
                    ->native(false)
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->company->name} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->placeholder('Tidak terikat unit (mis. admin)'),
                DateTimePicker::make('email_verified_at')
                    ->label('Email diverifikasi pada'),
                DateTimePicker::make('approved_at')
                    ->label('Disetujui pada')
                    ->helperText('Kosongkan bila akun masih menunggu persetujuan. Terisi = user boleh login.')
                    ->default(now()),
                TextInput::make('password')
                    ->label('Kata sandi')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
            ]);
    }
}
