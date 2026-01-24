<?php

namespace App\Filament\Resources\PolicyResource\RelationManagers;

use App\Enums\PolicyExceptionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    protected static ?string $title = 'Policy Exceptions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Exception Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Exception Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('justification')
                            ->label('Business Justification')
                            ->helperText('Explain why this exception is necessary')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Risk & Mitigation')
                    ->schema([
                        Forms\Components\Textarea::make('risk_assessment')
                            ->label('Risk Assessment')
                            ->helperText('Describe the risks associated with granting this exception')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('compensating_controls')
                            ->label('Compensating Controls')
                            ->helperText('Describe any mitigating controls in place')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status & Dates')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(PolicyExceptionStatus::class)
                            ->default(PolicyExceptionStatus::Pending)
                            ->required(),
                        Forms\Components\Select::make('requested_by')
                            ->label('Requested By')
                            ->relationship('requester', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('approved_by')
                            ->label('Approved By')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), [
                                PolicyExceptionStatus::Approved->value,
                                PolicyExceptionStatus::Approved,
                            ])),
                        Forms\Components\DatePicker::make('requested_date')
                            ->label('Requested Date')
                            ->default(now()),
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Effective Date'),
                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->helperText('Leave blank for no expiration'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Exception')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('requested_date')
                    ->label('Requested')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('No expiration'),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PolicyExceptionStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Exception'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hiddenLabel(),
                Tables\Actions\EditAction::make()
                    ->hiddenLabel(),
                Tables\Actions\DeleteAction::make()
                    ->hiddenLabel(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
