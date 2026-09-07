<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
