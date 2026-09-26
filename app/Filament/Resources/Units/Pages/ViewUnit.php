<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUnit extends ViewRecord
{
    use HasBackAction;

    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            EditAction::make(),
        ];
    }
}
