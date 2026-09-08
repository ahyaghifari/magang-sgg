<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    use HasBackAction;

    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }
}
