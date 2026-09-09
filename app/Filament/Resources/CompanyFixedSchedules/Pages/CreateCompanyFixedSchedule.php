<?php

namespace App\Filament\Resources\CompanyFixedSchedules\Pages;

use App\Filament\Concerns\HasBackAction;
use App\Filament\Resources\CompanyFixedSchedules\CompanyFixedScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyFixedSchedule extends CreateRecord
{
    use HasBackAction;

    protected static string $resource = CompanyFixedScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->backAction(),
        ];
    }
}
