<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    use HasBackAction;

    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }
}
