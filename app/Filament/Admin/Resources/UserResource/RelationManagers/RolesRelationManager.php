<?php

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Assign Role to User')
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove Role from User'),
            ]);
    }
}
