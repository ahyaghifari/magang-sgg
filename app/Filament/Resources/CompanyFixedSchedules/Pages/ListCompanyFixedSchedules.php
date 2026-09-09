<?php

namespace App\Filament\Resources\CompanyFixedSchedules\Pages;

use App\Filament\Resources\CompanyFixedSchedules\CompanyFixedScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanyFixedSchedules extends ListRecords
{
    protected static string $resource = CompanyFixedScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
