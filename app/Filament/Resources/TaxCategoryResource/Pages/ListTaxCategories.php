<?php

namespace App\Filament\Resources\TaxCategoryResource\Pages;

use App\Filament\Resources\TaxCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxCategories extends ListRecords
{
    protected static string $resource = TaxCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
