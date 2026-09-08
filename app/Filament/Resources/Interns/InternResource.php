<?php

namespace App\Filament\Resources\Interns;

use App\Filament\Resources\Interns\Pages\CreateIntern;
use App\Filament\Resources\Interns\Pages\EditIntern;
use App\Filament\Resources\Interns\Pages\ListInterns;
use App\Filament\Resources\Interns\Schemas\InternForm;
use App\Filament\Resources\Interns\Tables\InternsTable;
use App\Models\Intern;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InternResource extends Resource
{
    protected static ?string $model = Intern::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Intern';

    protected static ?string $modelLabel = 'Intern';

    protected static ?string $pluralModelLabel = 'Intern';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return InternForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInterns::route('/'),
            'create' => CreateIntern::route('/create'),
            'edit' => EditIntern::route('/{record}/edit'),
        ];
    }
}
