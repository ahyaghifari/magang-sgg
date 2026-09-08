<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnit extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
