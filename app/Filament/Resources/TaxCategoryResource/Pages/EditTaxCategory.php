<?php

namespace App\Filament\Resources\TaxCategoryResource\Pages;

use App\Filament\Resources\TaxCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTaxCategory extends EditRecord
{
    protected static string $resource = TaxCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
