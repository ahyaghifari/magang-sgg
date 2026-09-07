<?php

namespace App\Filament\Resources\Interns\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Interns\InternResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntern extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = InternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
