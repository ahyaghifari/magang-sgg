<?php

namespace App\Filament\Resources\CompanyFixedSchedules\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\CompanyFixedSchedules\CompanyFixedScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyFixedSchedule extends EditRecord
{
    use HasBackAction;

    protected static string $resource = CompanyFixedScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
            DeleteAction::make(),
        ];
    }
}
