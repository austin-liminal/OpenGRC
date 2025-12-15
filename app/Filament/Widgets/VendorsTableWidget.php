<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Mail;

class VendorsTableWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Vendor::query()->with(['vendorManager']))
            ->heading(__('Vendors'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->badge()
                    ->color(fn ($record) => $record->risk_rating->getColor()),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label(__('Risk Score'))
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 20 => 'success',
                        $state <= 40 => 'info',
                        $state <= 60 => 'warning',
                        $state <= 80 => 'orange',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->options(User::all()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\Action::make('send_survey')
                        ->label(__('Send Survey'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('survey_template_id')
                                ->label(__('Survey Template'))
                                ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('respondent_email')
                                ->label(__('Respondent Email'))
                                ->email()
                                ->required()
                                ->helperText(__('The email address to send the survey to')),
                            Forms\Components\TextInput::make('respondent_name')
                                ->label(__('Respondent Name'))
                                ->helperText(__('Name of the person completing the survey')),
                            Forms\Components\DatePicker::make('due_date')
                                ->label(__('Due Date'))
                                ->native(false),
                        ])
                        ->action(function (Vendor $record, array $data) {
                            $survey = Survey::create([
                                'survey_template_id' => $data['survey_template_id'],
                                'vendor_id' => $record->id,
                                'respondent_email' => $data['respondent_email'],
                                'respondent_name' => $data['respondent_name'] ?? null,
                                'due_date' => $data['due_date'] ?? null,
                                'status' => SurveyStatus::SENT,
                                'created_by_id' => auth()->id(),
                            ]);

                            try {
                                Mail::send(new SurveyInvitationMail($survey));

                                Notification::make()
                                    ->title(__('Survey Sent'))
                                    ->body(__('Survey invitation sent to :email', ['email' => $data['respondent_email']]))
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('Survey Created'))
                                    ->body(__('Survey created but email notification failed: ').$e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Create Vendor')
                    ->icon('heroicon-o-plus')
                    ->url(VendorResource::getUrl('create')),
            ])
            ->emptyStateHeading(__('No vendors'))
            ->emptyStateDescription(__('Get started by creating your first vendor.'))
            ->defaultSort('name', 'asc')
            ->recordUrl(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record]));
    }
}
