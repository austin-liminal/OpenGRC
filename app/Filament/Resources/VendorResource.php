<?php

namespace App\Filament\Resources;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource\Pages;
use App\Filament\Resources\VendorResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\SurveysRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\VendorDocumentsRelationManager;
use App\Filament\Resources\VendorResource\RelationManagers\VendorUsersRelationManager;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    // Hide from navigation - access via Vendor Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Vendors');
    }

    public static function getNavigationGroup(): string
    {
        return __('Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Vendor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Vendors');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(65535),
                Forms\Components\TextInput::make('url')
                    ->label(__('URL'))
                    ->maxLength(512),
                Forms\Components\Select::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->relationship('vendorManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->enum(VendorStatus::class)
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\Select::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->enum(VendorRiskRating::class)
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535),
                Forms\Components\FileUpload::make('logo')
                    ->label(__('Logo'))
                    ->disk(config('filesystems.default'))
                    ->directory('vendor-logos')
                    ->storeFileNamesIn('logo')
                    ->visibility('private')
                    ->maxSize(1024) // 1MB
                    ->deletable()
                    ->deleteUploadedFileUsing(function ($state) {
                        if ($state) {
                            \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($state);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable(),
                Tables\Columns\TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)                    
                    ,
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()->color(fn ($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('risk_rating')->label(__('Risk Rating'))->badge()->color(fn ($record) => $record->risk_rating->getColor()),
                Tables\Columns\TextColumn::make('url')
                    ->label(__('URL'))
                    ->url(fn ($record) => $record->url, true)
                    ->wrap()
                    ->sortable()
                    ->searchable()
                    ->hidden()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ,
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ,
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
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
                            'status' => SurveyStatus::DRAFT,
                            'created_by_id' => auth()->id(),
                        ]);

                        try {
                            Mail::send(new SurveyInvitationMail($survey));
                            $survey->update(['status' => SurveyStatus::SENT]);

                            Notification::make()
                                ->title(__('Survey Sent'))
                                ->body(__('Survey invitation sent to :email', ['email' => $data['respondent_email']]))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Failed to Send Survey'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
            SurveysRelationManager::class,
            VendorUsersRelationManager::class,
            VendorDocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}
