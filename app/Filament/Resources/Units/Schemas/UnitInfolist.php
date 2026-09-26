<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Enums\UserRole;
use App\Models\Unit;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rincian Unit')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Unit'),
                        TextEntry::make('company.name')
                            ->label('Perusahaan')
                            ->placeholder('—'),
                        TextEntry::make('interns_total')
                            ->label('Jumlah Intern')
                            ->state(fn (Unit $record): int => $record->interns()->count())
                            ->badge(),
                        TextEntry::make('pegawai_total')
                            ->label('Jumlah Pegawai')
                            ->state(fn (Unit $record): int => $record->users()->where('role', '!=', UserRole::Intern)->count())
                            ->badge(),
                    ]),
            ]);
    }
}
