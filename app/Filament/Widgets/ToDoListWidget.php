<?php

namespace App\Filament\Widgets;

use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class ToDoListWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = '2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DataRequestResponse::query()
                    ->with(['dataRequest.audit'])
                    ->where('requestee_id', auth()->id())
                    ->whereIn('status', ['Pending', 'in_progress'])
                    ->orderBy('due_at', 'asc')
                    ->limit(5)
            )
            ->heading(trans('widgets.todo.heading'))
            ->emptyStateHeading(new HtmlString(trans('widgets.todo.empty_heading')))
            ->emptyStateDescription(trans('widgets.todo.empty_description'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('dataRequest.audit.title')
                    ->label(trans('widgets.todo.audit'))
                    ->wrap(),
                TextColumn::make('dataRequest.details')
                    ->label(trans('widgets.todo.request_details'))
                    ->url(fn (DataRequestResponse $record) => route('filament.app.resources.data-request-responses.edit', $record))
                    ->limit(120)
                    ->html()
                    ->wrap(),
                TextColumn::make('status')
                    ->label(trans('widgets.todo.status'))
                    ->badge()
                    ->wrap(),
                TextColumn::make('due_at')
                    ->label(trans('widgets.todo.due_date')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(trans('widgets.todo.view'))
                    ->url(fn (DataRequestResponse $record): string => route('filament.app.resources.data-request-responses.edit', $record)),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(trans('widgets.todo.view_all'))
                    ->url(route('filament.app.pages.to-do'))
                    ->color('primary')
                    ->size('xs'),
            ])
            ->paginated(false);
    }
}
