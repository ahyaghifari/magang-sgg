<?php

namespace App\Filament\Resources\Journals\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Journals\JournalResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = JournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }

    /**
     * Untuk peserta: paksa intern_id ke data intern miliknya sendiri.
     * Admin tetap memakai nilai dari form (select intern).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if (! $user->isAdmin()) {
            $internId = $user->intern?->id;

            if (! $internId) {
                Notification::make()
                    ->title('Akun Anda belum terhubung dengan data Intern.')
                    ->body('Hubungi admin untuk membuatkan data Intern terlebih dahulu.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['intern_id'] = $internId;
        }

        return $data;
    }
}
