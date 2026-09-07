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
                    ->label('Institusi')
                    ->relationship('institusi', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
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
