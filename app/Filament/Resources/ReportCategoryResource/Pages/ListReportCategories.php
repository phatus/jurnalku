<?php

namespace App\Filament\Resources\ReportCategoryResource\Pages;

use App\Filament\Resources\ReportCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportCategories extends ListRecords
{
    protected static string $resource = ReportCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
