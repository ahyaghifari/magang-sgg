<?php

namespace App\Filament\Resources\Interns\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Interns\InternResource;
use App\Models\Intern;
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $aspekFields = [...array_keys(Intern::CRITERIA), 'catatan_penilaian'];

        if (collect($aspekFields)->contains(fn ($field) => ! empty($data[$field] ?? null))) {
            $data['dinilai_oleh'] = auth()->id();
            $data['dinilai_pada'] = now();
        }

        return $data;
    }
}
