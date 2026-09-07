<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    use HasBackAction;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
