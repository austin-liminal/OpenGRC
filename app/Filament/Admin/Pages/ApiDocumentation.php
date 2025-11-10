<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Route;
use Parsedown;

class ApiDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static string $view = 'filament.admin.pages.api-documentation';

    protected static ?string $navigationLabel = 'API Documentation';

    protected static ?string $title = 'API Documentation';

    protected static ?int $navigationSort = 900;

    protected static ?string $navigationGroup = 'System';

    public string $documentationHtml = '';

    public int $totalRoutes = 0;

    public array $routesByResource = [];

    public function mount(): void
    {
        // Get total API routes
        $this->totalRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->count();

        // Group routes by resource
        $this->routesByResource = $this->groupRoutesByResource();

        // Parse markdown documentation
        $this->documentationHtml = $this->parseMarkdownDocumentation();
    }

    protected function groupRoutesByResource(): array
    {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/') && $route->uri() !== 'api/user')
            ->groupBy(function ($route) {
                // Extract resource name from URI
                $parts = explode('/', $route->uri());

                return $parts[1] ?? 'other';
            })
            ->map(function ($routes) {
                return $routes->map(function ($route) {
                    return [
                        'method' => implode('|', $route->methods()),
                        'uri' => $route->uri(),
                        'name' => $route->getName(),
                    ];
                })->toArray();
            })
            ->toArray();

        ksort($routes);

        return $routes;
    }

    protected function parseMarkdownDocumentation(): string
    {
        $markdownPath = base_path('API_DOCUMENTATION.md');

        if (! file_exists($markdownPath)) {
            return '<p class="text-gray-500">Documentation file not found.</p>';
        }

        $markdown = file_get_contents($markdownPath);
        $parsedown = new Parsedown;

        return $parsedown->text($markdown);
    }

    public static function canAccess(): bool
    {
        // Allow any authenticated admin user to view API docs
        return true;
    }
}
