<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Filament\Exports\AssetExporter;
use App\Filament\Resources\AssetResource\Pages;
use App\Filament\Resources\AssetResource\RelationManagers;
use App\Models\Asset;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $navigationLabel = 'Assets';

    protected static ?string $navigationGroup = 'Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Core Identification Section
                Forms\Components\Section::make('Core Identification')
                    ->schema([
                        Forms\Components\TextInput::make('asset_tag')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Asset Tag')
                            ->placeholder('e.g., AST-12345'),

                        Forms\Components\TextInput::make('serial_number')
                            ->maxLength(255)
                            ->label('Serial Number'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Asset Name'),

                        Forms\Components\Select::make('asset_type_id')
                            ->label('Asset Type')
                            ->options(fn () => Taxonomy::where('slug', 'asset-type')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('status_id')
                            ->label('Status')
                            ->options(fn () => Taxonomy::where('slug', 'asset-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),

                // Hardware Specifications Section
                Forms\Components\Section::make('Hardware Specifications')
                    ->schema([
                        Forms\Components\TextInput::make('manufacturer')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('model')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('processor')
                            ->maxLength(255)
                            ->label('Processor/CPU'),

                        Forms\Components\TextInput::make('ram_gb')
                            ->numeric()
                            ->suffix('GB')
                            ->label('RAM'),

                        Forms\Components\TextInput::make('storage_type')
                            ->maxLength(255)
                            ->placeholder('HDD, SSD, NVMe')
                            ->label('Storage Type'),

                        Forms\Components\TextInput::make('storage_capacity_gb')
                            ->numeric()
                            ->suffix('GB')
                            ->label('Storage Capacity'),

                        Forms\Components\TextInput::make('graphics_card')
                            ->maxLength(255)
                            ->label('Graphics Card'),

                        Forms\Components\TextInput::make('screen_size')
                            ->numeric()
                            ->suffix('"')
                            ->step(0.1)
                            ->label('Screen Size'),

                        Forms\Components\TextInput::make('mac_address')
                            ->maxLength(255)
                            ->label('MAC Address')
                            ->placeholder('XX:XX:XX:XX:XX:XX'),

                        Forms\Components\TextInput::make('ip_address')
                            ->maxLength(255)
                            ->label('IP Address')
                            ->placeholder('192.168.1.100'),

                        Forms\Components\TextInput::make('hostname')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('operating_system')
                            ->maxLength(255)
                            ->label('Operating System'),

                        Forms\Components\TextInput::make('os_version')
                            ->maxLength(255)
                            ->label('OS Version'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Assignment & Location Section
                Forms\Components\Section::make('Assignment & Location')
                    ->schema([
                        Forms\Components\Select::make('assigned_to_user_id')
                            ->label('Assigned To')
                            ->options(User::pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\DatePicker::make('assigned_at')
                            ->label('Assigned Date'),

                        Forms\Components\TextInput::make('building')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('floor')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('room')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Financial Information Section
                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->label('Purchase Date'),

                        Forms\Components\TextInput::make('purchase_price')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Purchase Price'),

                        Forms\Components\TextInput::make('purchase_order_number')
                            ->maxLength(255)
                            ->label('PO Number'),

                        Forms\Components\TextInput::make('invoice_number')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('current_value')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Current Value'),

                        Forms\Components\TextInput::make('depreciation_method')
                            ->maxLength(255)
                            ->placeholder('Straight-line, Declining balance'),

                        Forms\Components\TextInput::make('depreciation_rate')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->label('Depreciation Rate'),

                        Forms\Components\TextInput::make('residual_value')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->label('Residual Value'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Warranty & Support Section
                Forms\Components\Section::make('Warranty & Support')
                    ->schema([
                        Forms\Components\DatePicker::make('warranty_start_date')
                            ->label('Warranty Start'),

                        Forms\Components\DatePicker::make('warranty_end_date')
                            ->label('Warranty End')
                            ->after('warranty_start_date'),

                        Forms\Components\TextInput::make('warranty_type')
                            ->maxLength(255)
                            ->placeholder('Manufacturer, Extended'),

                        Forms\Components\TextInput::make('warranty_provider')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('support_contract_number')
                            ->maxLength(255)
                            ->label('Support Contract #'),

                        Forms\Components\DatePicker::make('support_expiry_date')
                            ->label('Support Expiry'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Lifecycle Management Section
                Forms\Components\Section::make('Lifecycle Management')
                    ->schema([
                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date'),

                        Forms\Components\DatePicker::make('deployment_date')
                            ->label('Deployment Date'),

                        Forms\Components\DatePicker::make('last_audit_date')
                            ->label('Last Audit'),

                        Forms\Components\DatePicker::make('next_audit_date')
                            ->label('Next Audit'),

                        Forms\Components\DatePicker::make('retirement_date')
                            ->label('Retirement Date'),

                        Forms\Components\DatePicker::make('disposal_date')
                            ->label('Disposal Date'),

                        Forms\Components\TextInput::make('disposal_method')
                            ->maxLength(255)
                            ->placeholder('Recycled, Donated, Destroyed'),

                        Forms\Components\TextInput::make('expected_life_years')
                            ->numeric()
                            ->suffix('years')
                            ->label('Expected Life'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Maintenance & Service Section
                Forms\Components\Section::make('Maintenance & Service')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_maintenance_date')
                            ->label('Last Maintenance'),

                        Forms\Components\DateTimePicker::make('next_maintenance_date')
                            ->label('Next Maintenance'),

                        Forms\Components\Select::make('condition_id')
                            ->label('Condition')
                            ->options(fn () => Taxonomy::where('slug', 'asset-condition')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Forms\Components\Textarea::make('maintenance_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Software & Licensing Section
                Forms\Components\Section::make('Software & Licensing')
                    ->schema([
                        Forms\Components\TextInput::make('license_key')
                            ->label('License Key')
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('license_type')
                            ->maxLength(255)
                            ->placeholder('Per-device, Per-user, Enterprise'),

                        Forms\Components\TextInput::make('license_seats')
                            ->numeric()
                            ->label('Number of Seats'),

                        Forms\Components\DatePicker::make('license_expiry_date')
                            ->label('License Expiry'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visible(fn (Get $get) => Taxonomy::find($get('asset_type_id'))?->name === 'Software License'),

                // Security & Compliance Section
                Forms\Components\Section::make('Security & Compliance')
                    ->schema([
                        Forms\Components\Toggle::make('encryption_enabled')
                            ->label('Encryption Enabled'),

                        Forms\Components\Toggle::make('antivirus_installed')
                            ->label('Antivirus Installed'),

                        Forms\Components\DateTimePicker::make('last_security_scan')
                            ->label('Last Security Scan'),

                        Forms\Components\Select::make('compliance_status_id')
                            ->label('Compliance Status')
                            ->options(fn () => Taxonomy::where('slug', 'compliance-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),

                        Forms\Components\Select::make('data_classification_id')
                            ->label('Data Classification')
                            ->options(fn () => Taxonomy::where('slug', 'data-classification')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Additional Metadata Section
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Select::make('parent_asset_id')
                            ->label('Parent Asset')
                            ->options(fn () => Asset::pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_url')
                            ->image()
                            ->label('Asset Image')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_tag')
                    ->searchable()
                    ->sortable()
                    ->label('Asset Tag'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('assetType.name')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Available' => 'success',
                        'In Use' => 'info',
                        'In Repair' => 'warning',
                        'Retired', 'Lost', 'Stolen', 'Disposed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('manufacturer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('purchase_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('current_value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Hardware Specifications
                Tables\Columns\TextColumn::make('processor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ram_gb')
                    ->label('RAM (GB)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('storage_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('storage_capacity_gb')
                    ->label('Storage (GB)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('graphics_card')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('screen_size')
                    ->label('Screen Size')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mac_address')
                    ->label('MAC Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('hostname')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('operating_system')
                    ->label('OS')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('os_version')
                    ->label('OS Version')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Location Details
                Tables\Columns\TextColumn::make('building')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('floor')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('room')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Assigned Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Financial Information
                Tables\Columns\TextColumn::make('purchase_price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('purchase_order_number')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('depreciation_method')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('depreciation_rate')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('residual_value')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Warranty & Support
                Tables\Columns\TextColumn::make('warranty_start_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('warranty_end_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('warranty_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('warranty_provider')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('support_contract_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('support_expiry_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Lifecycle Management
                Tables\Columns\TextColumn::make('received_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deployment_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_audit_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('next_audit_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('retirement_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('disposal_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('disposal_method')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expected_life_years')
                    ->suffix(' years')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Maintenance & Service
                Tables\Columns\TextColumn::make('condition.name')
                    ->label('Condition')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_maintenance_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('next_maintenance_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Software & Licensing
                Tables\Columns\TextColumn::make('license_type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('license_seats')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('license_expiry_date')
                    ->date('m/d/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Security & Compliance
                Tables\Columns\IconColumn::make('encryption_enabled')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('antivirus_installed')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_security_scan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('complianceStatus.name')
                    ->label('Compliance')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('dataClassification.name')
                    ->label('Data Classification')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Relationships
                Tables\Columns\TextColumn::make('parentAsset.name')
                    ->label('Parent Asset')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Metadata
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('asset_type_id')
                    ->label('Asset Type')
                    ->options(fn () => Taxonomy::where('slug', 'asset-type')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn () => Taxonomy::where('slug', 'asset-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('assigned_to_user_id')
                    ->label('Assigned To')
                    ->options(fn () => User::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All assets')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\Filter::make('manufacturer')
                    ->form([
                        Forms\Components\TextInput::make('manufacturer'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['manufacturer'],
                        fn (Builder $query, $manufacturer): Builder => $query->where('manufacturer', 'like', "%{$manufacturer}%")
                    )
                    ),

                Tables\Filters\Filter::make('model')
                    ->form([
                        Forms\Components\TextInput::make('model'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['model'],
                        fn (Builder $query, $model): Builder => $query->where('model', 'like', "%{$model}%")
                    )
                    ),

                Tables\Filters\Filter::make('serial_number')
                    ->form([
                        Forms\Components\TextInput::make('serial_number'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['serial_number'],
                        fn (Builder $query, $serial): Builder => $query->where('serial_number', 'like', "%{$serial}%")
                    )
                    ),

                Tables\Filters\Filter::make('purchase_date')
                    ->form([
                        Forms\Components\DatePicker::make('purchased_from')
                            ->label('Purchased From'),
                        Forms\Components\DatePicker::make('purchased_until')
                            ->label('Purchased Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['purchased_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date),
                        )
                        ->when(
                            $data['purchased_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date),
                        )
                    ),

                Tables\Filters\SelectFilter::make('condition_id')
                    ->label('Condition')
                    ->options(fn () => Taxonomy::where('slug', 'asset-condition')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('compliance_status_id')
                    ->label('Compliance Status')
                    ->options(fn () => Taxonomy::where('slug', 'compliance-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\SelectFilter::make('data_classification_id')
                    ->label('Data Classification')
                    ->options(fn () => Taxonomy::where('slug', 'data-classification')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('encryption_enabled')
                    ->label('Encryption')
                    ->placeholder('All assets')
                    ->trueLabel('Encrypted')
                    ->falseLabel('Not encrypted'),

                Tables\Filters\TernaryFilter::make('antivirus_installed')
                    ->label('Antivirus')
                    ->placeholder('All assets')
                    ->trueLabel('Installed')
                    ->falseLabel('Not installed'),

                Tables\Filters\Filter::make('warranty_end_date')
                    ->form([
                        Forms\Components\DatePicker::make('warranty_from')
                            ->label('Warranty Ends From'),
                        Forms\Components\DatePicker::make('warranty_until')
                            ->label('Warranty Ends Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['warranty_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('warranty_end_date', '>=', $date),
                        )
                        ->when(
                            $data['warranty_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('warranty_end_date', '<=', $date),
                        )
                    ),

                Tables\Filters\Filter::make('next_audit_date')
                    ->form([
                        Forms\Components\DatePicker::make('audit_from')
                            ->label('Next Audit From'),
                        Forms\Components\DatePicker::make('audit_until')
                            ->label('Next Audit Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['audit_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('next_audit_date', '>=', $date),
                        )
                        ->when(
                            $data['audit_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('next_audit_date', '<=', $date),
                        )
                    ),

                Tables\Filters\SelectFilter::make('parent_asset_id')
                    ->label('Parent Asset')
                    ->options(fn () => Asset::pluck('name', 'id'))
                    ->searchable(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AssetExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(AssetExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header Section with Key Information
                Infolists\Components\Section::make('Asset Overview')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('asset_tag')
                                            ->label('Asset Tag')
                                            ->badge()
                                            ->color('primary')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Asset Name')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                            ->weight('bold'),
                                    ]),

                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('assetType.name')
                                            ->label('Type')
                                            ->badge()
                                            ->color('info'),

                                        Infolists\Components\TextEntry::make('status.name')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Available' => 'success',
                                                'In Use' => 'info',
                                                'In Repair' => 'warning',
                                                'Retired', 'Lost', 'Stolen', 'Disposed' => 'danger',
                                                default => 'gray',
                                            }),
                                    ]),
                                ]),

                            Infolists\Components\Group::make([
                                Infolists\Components\ImageEntry::make('image_url')
                                    ->label('')
                                    ->height(150)
                                    ->grow(false)
                                    ->visible(fn ($state) => ! empty($state)),

                                Infolists\Components\IconEntry::make('assetType.name')
                                    ->label('')
                                    ->size(Infolists\Components\IconEntry\IconEntrySize::ExtraLarge)
                                    ->icon(fn (string $state): string => match ($state) {
                                        'Laptop' => 'heroicon-o-computer-desktop',
                                        'Desktop' => 'heroicon-o-computer-desktop',
                                        'Server' => 'heroicon-o-server',
                                        'Monitor' => 'heroicon-o-tv',
                                        'Phone' => 'heroicon-o-device-phone-mobile',
                                        'Tablet' => 'heroicon-o-device-tablet',
                                        'Network Equipment' => 'heroicon-o-signal',
                                        'Peripheral' => 'heroicon-o-printer',
                                        'Software License' => 'heroicon-o-key',
                                        default => 'heroicon-o-cube',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'Laptop', 'Desktop' => 'info',
                                        'Server' => 'danger',
                                        'Monitor', 'Phone', 'Tablet' => 'success',
                                        'Network Equipment' => 'warning',
                                        'Software License' => 'primary',
                                        default => 'gray',
                                    })
                                    ->visible(fn ($record) => empty($record->image_url)),
                            ])
                                ->grow(false),
                        ])->from('md'),
                    ])
                    ->collapsible(),

                // Core Identification
                Infolists\Components\Section::make('Identification Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->placeholder('Not provided'),

                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Active Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),

                        Infolists\Components\TextEntry::make('parentAsset.name')
                            ->label('Parent Asset')
                            ->placeholder('None'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Hardware Specifications
                Infolists\Components\Section::make('Hardware Specifications')
                    ->schema([
                        Infolists\Components\TextEntry::make('manufacturer')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('model')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('processor')
                            ->label('Processor/CPU')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('ram_gb')
                            ->label('RAM')
                            ->suffix(' GB')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('storage_type')
                            ->label('Storage Type')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('storage_capacity_gb')
                            ->label('Storage Capacity')
                            ->suffix(' GB')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('graphics_card')
                            ->label('Graphics Card')
                            ->placeholder('Not specified')
                            ->columnSpan(2),

                        Infolists\Components\TextEntry::make('screen_size')
                            ->label('Screen Size')
                            ->suffix('"')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('operating_system')
                            ->label('Operating System')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('os_version')
                            ->label('OS Version')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('mac_address')
                            ->label('MAC Address')
                            ->copyable()
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->copyable()
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('hostname')
                            ->label('Hostname')
                            ->copyable()
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Assignment & Location
                Infolists\Components\Section::make('Assignment & Location')
                    ->schema([
                        Infolists\Components\TextEntry::make('assignedToUser.name')
                            ->label('Assigned To')
                            ->placeholder('Not assigned')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('assigned_at')
                            ->label('Assigned Date')
                            ->dateTime()
                            ->placeholder('Not assigned'),

                        Infolists\Components\TextEntry::make('building')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('floor')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('room')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Financial Information
                Infolists\Components\Section::make('Financial Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('purchase_date')
                            ->label('Purchase Date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('purchase_price')
                            ->label('Purchase Price')
                            ->money('USD')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('current_value')
                            ->label('Current Value')
                            ->money('USD')
                            ->placeholder('Not calculated'),

                        Infolists\Components\TextEntry::make('depreciation_method')
                            ->label('Depreciation Method')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('depreciation_rate')
                            ->label('Depreciation Rate')
                            ->suffix('%')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('residual_value')
                            ->label('Residual Value')
                            ->money('USD')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('purchase_order_number')
                            ->label('PO Number')
                            ->copyable()
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('invoice_number')
                            ->label('Invoice Number')
                            ->copyable()
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Warranty & Support
                Infolists\Components\Section::make('Warranty & Support')
                    ->schema([
                        Infolists\Components\TextEntry::make('warranty_start_date')
                            ->label('Warranty Start')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('warranty_end_date')
                            ->label('Warranty End')
                            ->date('M d, Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isFuture() ? 'success' : 'warning')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('warranty_type')
                            ->label('Warranty Type')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('warranty_provider')
                            ->label('Warranty Provider')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('support_contract_number')
                            ->label('Support Contract')
                            ->copyable()
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('support_expiry_date')
                            ->label('Support Expiry')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Lifecycle Management
                Infolists\Components\Section::make('Lifecycle Management')
                    ->schema([
                        Infolists\Components\TextEntry::make('received_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('deployment_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('last_audit_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('next_audit_date')
                            ->date('M d, Y')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                            ->placeholder('Not scheduled'),

                        Infolists\Components\TextEntry::make('expected_life_years')
                            ->suffix(' years')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('retirement_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('disposal_date')
                            ->date('M d, Y')
                            ->placeholder('Not specified'),

                        Infolists\Components\TextEntry::make('disposal_method')
                            ->placeholder('Not specified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Maintenance & Service
                Infolists\Components\Section::make('Maintenance & Service')
                    ->schema([
                        Infolists\Components\TextEntry::make('condition.name')
                            ->label('Condition')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Excellent' => 'success',
                                'Good' => 'info',
                                'Fair' => 'warning',
                                'Poor', 'Damaged' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Not assessed'),

                        Infolists\Components\TextEntry::make('last_maintenance_date')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not recorded'),

                        Infolists\Components\TextEntry::make('next_maintenance_date')
                            ->dateTime('M d, Y H:i')
                            ->badge()
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                            ->placeholder('Not scheduled'),

                        Infolists\Components\TextEntry::make('maintenance_notes')
                            ->columnSpanFull()
                            ->placeholder('No maintenance notes'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Security & Compliance
                Infolists\Components\Section::make('Security & Compliance')
                    ->schema([
                        Infolists\Components\TextEntry::make('encryption_enabled')
                            ->label('Encryption')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Enabled' : 'Disabled')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),

                        Infolists\Components\TextEntry::make('antivirus_installed')
                            ->label('Antivirus')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Installed' : 'Not Installed')
                            ->color(fn ($state) => $state ? 'success' : 'warning'),

                        Infolists\Components\TextEntry::make('last_security_scan')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Never scanned'),

                        Infolists\Components\TextEntry::make('complianceStatus.name')
                            ->label('Compliance Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Compliant' => 'success',
                                'Non-Compliant' => 'danger',
                                'Exempt' => 'info',
                                'Pending' => 'warning',
                                default => 'gray',
                            })
                            ->placeholder('Not assessed'),

                        Infolists\Components\TextEntry::make('dataClassification.name')
                            ->label('Data Classification')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Public' => 'success',
                                'Internal' => 'info',
                                'Confidential' => 'warning',
                                'Restricted' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('Not classified'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                // Additional Information
                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->placeholder('No additional notes')
                            ->html(),

                        Infolists\Components\TextEntry::make('tags')
                            ->badge()
                            ->separator(',')
                            ->placeholder('No tags'),

                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Created By'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y H:i'),

                        Infolists\Components\TextEntry::make('updater.name')
                            ->label('Last Updated By'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->since(),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImplementationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'view' => Pages\ViewAsset::route('/{record}'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name.' ('.$record->asset_tag.')';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['asset_tag', 'name', 'serial_number', 'manufacturer', 'model'];
    }
}
