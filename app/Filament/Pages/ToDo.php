<?php

namespace App\Filament\Pages;

use App\Enums\ResponseStatus;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ToDo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected string $view = 'filament.pages.to-do';

    public static function getNavigationLabel(): string
    {
        return __('navigation.menu.todo');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        // Only show badge for regular users with openTodos method
        if (! $user || ! method_exists($user, 'openTodos')) {
            return null;
        }

        $count = $user->openTodos()->count();

        if ($count > 99) {
            return '99+';
        } elseif ($count > 0) {
            return $count;
        }

        return null;
    }

    protected function getTableQuery(): Builder
    {
        return DataRequestResponse::query()->where('requestee_id', auth()->id());
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable()->searchable(),
            TextColumn::make('dataRequest.code')->label('Request Code')->searchable(),
            TextColumn::make('dataRequest.audit.title')->label('Audit')->searchable(),
            TextColumn::make('dataRequest.details')->label('Requested Information')->html()->limit(100)->wrap(),
            TextColumn::make('due_at')->label('Due At')->searchable(),
            TextColumn::make('status')->label('Status')->searchable()->badge(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('status')
                ->label('Show Responded')
                ->multiple()
                ->options(ResponseStatus::class)
                ->default(['Pending', 'Rejected']),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('Respond')
                ->url(fn (DataRequestResponse $record): string => route('filament.app.resources.data-request-responses.edit', $record)),
        ];
    }
}
