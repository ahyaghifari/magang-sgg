<?php

namespace App\Filament\Resources\Shifts\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Shifts\ShiftResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShift extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
