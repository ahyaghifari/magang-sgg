<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Units\UnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    use HasBackAction;

    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }
}
