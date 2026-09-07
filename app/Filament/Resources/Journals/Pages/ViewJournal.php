<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Journals\JournalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJournal extends ViewRecord
{
    use HasBackAction;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            EditAction::make(),
        ];
    }
}
