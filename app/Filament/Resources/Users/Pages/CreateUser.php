<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
