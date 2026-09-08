<?php

namespace App\Filament\Resources\Journals;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\EditJournal;
use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Filament\Resources\Journals\Pages\ViewJournal;
use App\Filament\Resources\Journals\Schemas\JournalForm;
use App\Filament\Resources\Journals\Schemas\JournalInfolist;
use App\Filament\Resources\Journals\Tables\JournalsTable;
use App\Models\Journal;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'activity';

    protected static ?string $navigationLabel = 'Jurnal';

    protected static ?string $modelLabel = 'Jurnal';

    protected static ?string $pluralModelLabel = 'Jurnal';

    protected static ?int $navigationSort = 11;

    /**
     * Batasi data:
     * - admin / super_admin  -> semua jurnal
     * - selain itu (peserta) -> hanya jurnal milik intern yang terhubung ke user login
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->whereHas('intern', fn (Builder $q) => $q->where('user_id', $user->id));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return JournalForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JournalInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalsTable::configure($table);
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
            'index' => ListJournals::route('/'),
            'create' => CreateJournal::route('/create'),
            'view' => ViewJournal::route('/{record}'),
            'edit' => EditJournal::route('/{record}/edit'),
        ];
    }
}
