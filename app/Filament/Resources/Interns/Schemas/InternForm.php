<?php

namespace App\Filament\Resources\Interns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InternForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('institusi_id')
                    ->label('Institusi (asal sekolah/kampus)')
                    ->relationship('institusi', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit penempatan')
                    ->relationship('unit', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => "{$record->company->name} — {$record->name}")
                    ->searchable()
                    ->preload()
                    ->placeholder('Belum ditempatkan'),
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nip')
                    ->label('NIP')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Nomor Induk Pegawai — opsional, diisi manual oleh admin.'),
                Select::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ])
                    ->native(false)
                    ->required(),
            ]);
    }
}
