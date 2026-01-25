<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Filament\Resources\ImplementationResource;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    public function form(Schema $schema): Schema
    {
        return ImplementationResource::getForm($schema);
    }

    public function table(Table $table): Table
    {
        $table = ImplementationResource::getTable($table);
        $table->recordActions([
            ViewAction::make()->hidden(),
        ]);

        return $table;
    }
}
