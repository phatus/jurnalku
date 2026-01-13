<?php

namespace App\Filament\Resources\ImplementationBasisResource\Pages;

use App\Filament\Resources\ImplementationBasisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImplementationBases extends ListRecords
{
    protected static string $resource = ImplementationBasisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
