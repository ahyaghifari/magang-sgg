<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use HasBackAction;

    protected static string $resource = PostResource::class;

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
