<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Journals\JournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJournal extends EditRecord
{
    use HasBackAction;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * Jaga-jaga: peserta tidak bisa memindahkan jurnal ke intern lain.
     * (Field intern_id juga sudah disembunyikan dari form untuk peserta.)
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            $data['intern_id'] = $this->record->intern_id;
        }

        return $data;
    }
}
