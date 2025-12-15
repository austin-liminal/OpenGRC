<?php

namespace App\Filament\Vendor\Resources;

use App\Enums\SurveyStatus;
use App\Filament\Vendor\Resources\SurveyResource\Pages;
use App\Models\Survey;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Surveys';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Survey';

    protected static ?string $pluralModelLabel = 'Surveys';

    public static function getEloquentQuery(): Builder
    {
        $vendorUser = Auth::guard('vendor')->user();

        return parent::getEloquentQuery()
            ->where('vendor_id', $vendorUser?->vendor_id)
            ->where('respondent_email', $vendorUser?->email)
            ->whereIn('status', [
                SurveyStatus::SENT,
                SurveyStatus::IN_PROGRESS,
                SurveyStatus::COMPLETED,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('template.title')
                    ->label('Survey')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn (?Survey $record) => $record?->due_date?->isPast() && $record?->status !== SurveyStatus::COMPLETED ? 'danger' : null),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->placeholder('-')
                    ->visible(fn (?Survey $record) => $record?->status === SurveyStatus::COMPLETED),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SurveyStatus::SENT->value => 'Pending',
                        SurveyStatus::IN_PROGRESS->value => 'In Progress',
                        SurveyStatus::COMPLETED->value => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('respond')
                    ->label('Respond')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (Survey $record): string => Pages\RespondToSurvey::getUrl(['record' => $record]))
                    ->visible(fn (Survey $record): bool => in_array($record->status, [
                        SurveyStatus::SENT,
                        SurveyStatus::IN_PROGRESS,
                    ], true)),
            ])
            ->defaultSort('due_date', 'asc')
            ->emptyStateHeading('No surveys')
            ->emptyStateDescription('You have no surveys assigned to you.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Survey Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('template.title')
                            ->label('Survey Title')
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date()
                            ->color(fn (?Survey $record) => $record?->due_date?->isPast() ? 'danger' : null),
                        TextEntry::make('progress')
                            ->label('Progress')
                            ->suffix('%'),
                        TextEntry::make('template.description')
                            ->label('Description')
                            ->html()
                            ->columnSpanFull()
                            ->visible(fn (?Survey $record) => ! empty($record?->template?->description)),
                    ]),

                Section::make('Results')
                    ->visible(fn (?Survey $record) => $record?->status === SurveyStatus::COMPLETED)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('completed_at')
                            ->label('Completed')
                            ->dateTime(),
                        TextEntry::make('risk_score')
                            ->label('Risk Score')
                            ->suffix('/100')
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state <= 20 => 'success',
                                $state <= 40 => 'info',
                                $state <= 60 => 'warning',
                                $state <= 80 => 'danger',
                                default => 'danger',
                            }),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSurveys::route('/'),
            'view' => Pages\ViewSurvey::route('/{record}'),
            'respond' => Pages\RespondToSurvey::route('/{record}/respond'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
