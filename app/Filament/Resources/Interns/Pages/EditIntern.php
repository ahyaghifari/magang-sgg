<?php

namespace App\Filament\Resources\Interns\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\Interns\InternResource;
use App\Models\Intern;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntern extends EditRecord
{
    use HasBackAction;

    protected static string $resource = InternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * Catat siapa & kapan penilaian terakhir diisi/diubah — dipakai a.l. di
     * sertifikat PKL sebagai tanda tangan penilai.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $aspekFields = [...array_keys(Intern::CRITERIA), 'catatan_penilaian'];

        foreach ($aspekFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== $this->record->{$field}) {
                $data['dinilai_oleh'] = auth()->id();
                $data['dinilai_pada'] = now();

                break;
            }
        }

        return $data;
    }
}
