<?php

namespace App\Filament\Admin\Resources\RoleResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Permission to this Role')
                    ->after(function ($record) {
                        Cache::forget('spatie.permission.cache');
                    })
                    ->preloadRecordSelect(),

            ])
            ->recordActions([
                DetachAction::make()
                    ->after(function ($record) {
                        Cache::forget('spatie.permission.cache');
                    })
                    ->label('Detach from Role'),
            ]);
    }

    protected function saved(): void
    {
        // Clear the permissions cache
        Cache::forget('spatie.permission.cache');
    }
}
