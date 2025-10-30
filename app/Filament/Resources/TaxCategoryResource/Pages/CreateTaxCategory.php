<?php

namespace App\Filament\Resources\TaxCategoryResource\Pages;

use App\Filament\Resources\TaxCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxCategory extends CreateRecord
{
    protected static string $resource = TaxCategoryResource::class;
}
