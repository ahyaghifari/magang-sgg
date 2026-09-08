<?php

namespace App\Filament\Resources\Interns\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Interns\InternResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntern extends EditRecord
{
    use HasBackAction;

    protected static string $resource = InternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }
}
