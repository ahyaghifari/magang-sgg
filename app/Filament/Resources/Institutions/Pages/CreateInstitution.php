<?php

namespace App\Filament\Resources\Institutions\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Institutions\InstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitution extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
