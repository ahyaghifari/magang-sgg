<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Companies\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
