<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Posts\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = PostResource::class;

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
