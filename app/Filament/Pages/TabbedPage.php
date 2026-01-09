<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Livewire\Attributes\Url;

/**
 * Base class for pages with tab-based navigation and dynamic widgets.
 *
 * This provides a simplified interface similar to Filament's Dashboard,
 * allowing subclasses to define tabs and their associated widgets/relation managers.
 *
 * Usage:
 * - Override getTabs() to define available tabs
 * - Override getWidgets() to return widgets for the current tab
 * - Optionally override getRelationManagers() for relation managers
 */
abstract class TabbedPage extends Page
{
    protected static string $view = 'filament.pages.tabbed-page';

    #[Url]
    public string $activeTab = '';

    public function mount(): void
    {
        // Set default tab to first tab if not specified
        if ($this->activeTab === '') {
            $tabs = $this->getTabs();
            $this->activeTab = array_key_first($tabs) ?? '';
        }
    }

    /**
     * Get the tabs configuration for this page.
     *
     * Override this method to define tabs. Return an empty array for no tabs.
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public function getTabs(): array
    {
        return [];
    }

    /**
     * Get the widgets to display for the current tab.
     *
     * Override this method to return different widgets based on $this->activeTab.
     *
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [];
    }

    /**
     * Get the relation managers to display for the current tab.
     *
     * Override this method to return relation managers for the current tab.
     *
     * @return array<class-string>
     */
    public function getRelationManagers(): array
    {
        return [];
    }

    /**
     * Get the stats/overview widgets to display in the header.
     *
     * Override this method to show stats widgets above the tabs.
     *
     * @return array<class-string>
     */
    public function getStatsWidgets(): array
    {
        return [];
    }

    /**
     * Set the active tab (called from the view via wire:click).
     */
    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Get the number of columns for the widget grid.
     */
    public function getColumns(): int|string|array
    {
        return 1;
    }

    /**
     * Map getTabs() to getViewData() for the Blade template.
     */
    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'tabs' => $this->getTabs(),
            'widgets' => $this->getWidgets(),
            'relationManagers' => $this->getRelationManagers(),
            'statsWidgets' => $this->getStatsWidgets(),
            'columns' => $this->getColumns(),
        ];
    }

    /**
     * Header widgets are handled by statsWidgets in the view.
     * This is kept for Filament compatibility but typically returns empty.
     */
    protected function getHeaderWidgets(): array
    {
        return $this->getStatsWidgets();
    }

    /**
     * Footer widgets are handled by getWidgets() in the view.
     * This is kept for Filament compatibility.
     */
    protected function getFooterWidgets(): array
    {
        return $this->getWidgets();
    }

    /**
     * Get footer widget columns - delegates to getColumns().
     */
    public function getFooterWidgetsColumns(): int|array
    {
        $columns = $this->getColumns();

        return is_array($columns) ? $columns : (int) $columns;
    }
}
