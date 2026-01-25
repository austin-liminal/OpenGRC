<?php

namespace App\Filament\Forms\Components;

use BackedEnum;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;

/**
 * A two-sided multiselect component with customizable action buttons.
 *
 * Displays available options on the left and selected options on the right,
 * with support for search, bulk actions, and custom filtering selectors.
 */
class ActionableMultiselectTwoSides extends Select
{
    protected string $view = 'filament.forms.components.actionable-multiselect-two-sides';

    /**
     * Label for the selectable (left) panel
     */
    public ?string $selectableLabel = null;

    /**
     * Label for the selected (right) panel
     */
    public ?string $selectedLabel = null;

    /**
     * Whether search is enabled
     */
    public bool $searchEnabled = false;

    /**
     * Stores full model data for filtering
     *
     * @var array<string|int, array<string, mixed>>|Closure
     */
    protected array|Closure $optionsMetadata = [];

    /**
     * Registered simple action buttons
     *
     * @var array<string, array{label: string, callback: Closure, icon: string|null, color: string, count: int}>
     */
    protected array $actions = [];

    /**
     * Registered dropdown actions
     *
     * @var array<string, array{label: string, callback: Closure, icon: string|null, color: string, options: array}>
     */
    protected array $dropdownActions = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Enable multiple selection
        $this->multiple();

        // Set default labels
        $this->selectableLabel = 'Available';
        $this->selectedLabel = 'Selected';

        // Register Livewire listeners for all interactions
        $this->registerListeners([
            // Single item selection
            'ms-two-sides::selectOption' => [
                fn (Component $component, string $statePath, string $value) => $statePath === $component->getStatePath()
                    ? $this->selectOption($value)
                    : null,
            ],
            // Single item deselection
            'ms-two-sides::unselectOption' => [
                fn (Component $component, string $statePath, string $value) => $statePath === $component->getStatePath()
                    ? $this->unselectOption($value)
                    : null,
            ],
            // Select all items
            'ms-two-sides::selectAllOptions' => [
                fn (Component $component, string $statePath) => $statePath === $component->getStatePath()
                    ? $this->selectAll()
                    : null,
            ],
            // Unselect all items
            'ms-two-sides::unselectAllOptions' => [
                fn (Component $component, string $statePath) => $statePath === $component->getStatePath()
                    ? $this->unselectAll()
                    : null,
            ],
            // Execute custom action
            'ms-two-sides::executeAction' => [
                fn (Component $component, string $statePath, string $actionName, int $count = 10) => $statePath === $component->getStatePath()
                    ? $this->executeAction($actionName, $count)
                    : null,
            ],
        ]);
    }

    // =========================================================================
    // LABEL CONFIGURATION
    // =========================================================================

    /**
     * Set the label for the selectable (left) panel
     */
    public function selectableLabel(string $label): static
    {
        $this->selectableLabel = $label;

        return $this;
    }

    /**
     * Get the label for the selectable panel
     */
    public function getSelectableLabel(): string
    {
        return $this->selectableLabel ?? 'Available';
    }

    /**
     * Set the label for the selected (right) panel
     */
    public function selectedLabel(string $label): static
    {
        $this->selectedLabel = $label;

        return $this;
    }

    /**
     * Get the label for the selected panel
     */
    public function getSelectedLabel(): string
    {
        return $this->selectedLabel ?? 'Selected';
    }

    // =========================================================================
    // SEARCH CONFIGURATION
    // =========================================================================

    /**
     * Enable search functionality
     */
    public function enableSearch(): static
    {
        $this->searchEnabled = true;

        return $this;
    }

    /**
     * Check if search is enabled
     */
    public function isSearchable(): bool
    {
        return $this->searchEnabled;
    }

    // =========================================================================
    // OPTIONS MANAGEMENT
    // =========================================================================

    /**
     * Get options that are available for selection (not yet selected)
     *
     * @return array<string|int, string>
     */
    public function getSelectableOptions(): array
    {
        return collect($this->getOptions())
            ->diff($this->getSelectedOptions())
            ->toArray();
    }

    /**
     * Get currently selected options
     *
     * @return array<string|int, string>
     */
    public function getSelectedOptions(): array
    {
        $state = $this->getState() ?? [];

        return collect($this->getOptions())
            ->filter(fn (string $label, string|int $value) => in_array($value, $state))
            ->toArray();
    }

    /**
     * Select a single option by value
     */
    public function selectOption(string $value): void
    {
        $state = $this->getState() ?? [];
        $state = array_unique(array_merge($state, [$value]));
        $this->state($state);
    }

    /**
     * Unselect a single option by value
     */
    public function unselectOption(string $value): void
    {
        $state = $this->getState() ?? [];
        $key = array_search($value, $state);
        if ($key !== false) {
            unset($state[$key]);
        }
        $this->state(array_values($state));
    }

    /**
     * Select all available options
     */
    public function selectAll(): void
    {
        $this->state(array_keys($this->getOptions()));
    }

    /**
     * Unselect all options
     */
    public function unselectAll(): void
    {
        $this->state([]);
    }

    // =========================================================================
    // METADATA FOR FILTERING
    // =========================================================================

    /**
     * Set metadata for options (full model data for filtering)
     *
     * @param  array<string|int, array<string, mixed>>|Closure  $metadata
     */
    public function optionsMetadata(array|Closure $metadata): static
    {
        $this->optionsMetadata = $metadata;

        return $this;
    }

    /**
     * Get metadata for all options
     *
     * @return array<string|int, array<string, mixed>>
     */
    public function getOptionsMetadata(): array
    {
        return $this->evaluate($this->optionsMetadata);
    }

    // =========================================================================
    // ACTION BUTTONS
    // =========================================================================

    /**
     * Add a simple action button
     *
     * @param  string  $name  Unique identifier for the action
     * @param  string  $label  Display label for the button
     * @param  Closure  $callback  Function that receives (array $selectableIds, array $metadata, int $count) and returns array of IDs to select
     * @param  string|null  $icon  Heroicon name (e.g., 'heroicon-o-sparkles')
     * @param  string  $color  Tailwind color (e.g., 'primary', 'gray', 'danger')
     * @param  int  $count  Default count parameter passed to callback
     */
    public function addAction(
        string $name,
        string $label,
        Closure $callback,
        ?string $icon = null,
        string $color = 'gray',
        int $count = 10
    ): static {
        $this->actions[$name] = [
            'label' => $label,
            'callback' => $callback,
            'icon' => $icon,
            'color' => $color,
            'count' => $count,
        ];

        return $this;
    }

    /**
     * Add a dropdown action with sub-options
     *
     * @param  string  $name  Unique identifier for the action
     * @param  string  $label  Display label for the dropdown button
     * @param  Closure  $callback  Function that receives (array $selectableIds, array $metadata, int $count) and returns array of IDs to select
     * @param  array  $options  Array of dropdown options, each with 'label' and 'count' keys
     * @param  string|null  $icon  Heroicon name
     * @param  string  $color  Tailwind color
     */
    public function addDropdownAction(
        string $name,
        string $label,
        Closure $callback,
        array $options,
        ?string $icon = null,
        string $color = 'gray'
    ): static {
        $this->dropdownActions[$name] = [
            'label' => $label,
            'callback' => $callback,
            'icon' => $icon,
            'color' => $color,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Get all registered simple actions
     *
     * @return array<string, array{label: string, callback: Closure, icon: string|null, color: string, count: int}>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get all registered dropdown actions
     *
     * @return array<string, array{label: string, callback: Closure, icon: string|null, color: string, options: array}>
     */
    public function getDropdownActions(): array
    {
        return $this->dropdownActions;
    }

    /**
     * Execute a registered action
     */
    public function executeAction(string $actionName, int $count = 10): void
    {
        // Check simple actions first, then dropdown actions
        if (isset($this->actions[$actionName])) {
            $action = $this->actions[$actionName];
        } elseif (isset($this->dropdownActions[$actionName])) {
            $action = $this->dropdownActions[$actionName];
        } else {
            return;
        }

        $callback = $action['callback'];

        // Get currently selectable options (not yet selected)
        $selectableOptions = $this->getSelectableOptions();
        $selectableIds = array_keys($selectableOptions);

        // Get metadata for selectable options only
        $allMetadata = $this->getOptionsMetadata();
        $selectableMetadata = array_intersect_key($allMetadata, array_flip($selectableIds));

        // Execute the callback to get IDs to select
        $idsToSelect = $this->evaluate($callback, [
            'selectableIds' => $selectableIds,
            'metadata' => $selectableMetadata,
            'count' => $count,
        ]);

        // Add selected IDs to current state
        if (is_array($idsToSelect) && count($idsToSelect) > 0) {
            $currentState = $this->getState() ?? [];
            // Convert to strings for consistency
            $idsToSelect = array_map('strval', $idsToSelect);
            $newState = array_unique(array_merge($currentState, $idsToSelect));
            $this->state($newState);
        }
    }

    // =========================================================================
    // BUILT-IN SELECTORS
    // =========================================================================

    /**
     * Helper: Select random items from available options
     */
    public static function randomSelector(): Closure
    {
        return function (array $selectableIds, array $metadata, int $count): array {
            if (empty($selectableIds)) {
                return [];
            }

            $count = min($count, count($selectableIds));
            if ($count <= 0) {
                return [];
            }

            $randomKeys = array_rand(array_flip($selectableIds), $count);

            return is_array($randomKeys) ? $randomKeys : [$randomKeys];
        };
    }

    /**
     * Helper: Select random items where effectiveness is UNKNOWN/Not Assessed
     */
    public static function randomUnassessedSelector(): Closure
    {
        return function (array $selectableIds, array $metadata, int $count): array {
            // Filter to only items with UNKNOWN effectiveness
            $unassessed = array_filter($selectableIds, function ($id) use ($metadata) {
                if (! isset($metadata[$id]['effectiveness'])) {
                    return true; // Include if no effectiveness data (treat as unknown)
                }
                $effectiveness = $metadata[$id]['effectiveness'];
                // Handle both enum objects and string values
                if ($effectiveness instanceof BackedEnum) {
                    $effectivenessValue = $effectiveness->value;
                } elseif (is_object($effectiveness) && property_exists($effectiveness, 'value')) {
                    $effectivenessValue = $effectiveness->value;
                } else {
                    $effectivenessValue = $effectiveness;
                }

                return in_array($effectivenessValue, ['Not Assessed', 'UNKNOWN', 'Unknown']);
            });

            if (empty($unassessed)) {
                return [];
            }

            // If count is 0 or negative, return all unassessed
            if ($count <= 0) {
                return array_values($unassessed);
            }

            $count = min($count, count($unassessed));
            $randomKeys = array_rand(array_flip($unassessed), $count);

            return is_array($randomKeys) ? $randomKeys : [$randomKeys];
        };
    }

    /**
     * Helper: Select all items matching a filter
     */
    public static function filterSelector(string $field, mixed $value): Closure
    {
        return function (array $selectableIds, array $metadata, int $count) use ($field, $value): array {
            $filtered = array_filter($selectableIds, function ($id) use ($metadata, $field, $value) {
                if (! isset($metadata[$id][$field])) {
                    return false;
                }

                $fieldValue = $metadata[$id][$field];

                // Handle enum objects
                if ($fieldValue instanceof BackedEnum) {
                    $fieldValue = $fieldValue->value;
                } elseif (is_object($fieldValue) && property_exists($fieldValue, 'value')) {
                    $fieldValue = $fieldValue->value;
                }

                // Handle array of values (like taxonomy_ids)
                if (is_array($fieldValue)) {
                    return in_array($value, $fieldValue);
                }

                return $fieldValue == $value;
            });

            $filtered = array_values($filtered);

            // If count is 0 or negative, return all filtered
            if ($count <= 0) {
                return $filtered;
            }

            // Return random selection from filtered
            if (empty($filtered)) {
                return [];
            }

            $count = min($count, count($filtered));
            $randomKeys = array_rand(array_flip($filtered), $count);

            return is_array($randomKeys) ? $randomKeys : [$randomKeys];
        };
    }

    /**
     * Helper: Select items by owner
     */
    public static function ownerSelector(int $ownerId): Closure
    {
        return function (array $selectableIds, array $metadata, int $count) use ($ownerId): array {
            $filtered = array_filter($selectableIds, function ($id) use ($metadata, $ownerId) {
                $ownerKey = $metadata[$id]['control_owner_id']
                    ?? $metadata[$id]['implementation_owner_id']
                    ?? null;

                return $ownerKey == $ownerId;
            });

            return array_values($filtered);
        };
    }

    /**
     * Helper: Select items sorted by oldest assessed date (oldest first)
     * Items never assessed are prioritized, then sorted by last_assessed_at ascending
     */
    public static function oldestAssessedSelector(): Closure
    {
        return function (array $selectableIds, array $metadata, int $count): array {
            if (empty($selectableIds)) {
                return [];
            }

            // Separate never assessed from assessed
            $neverAssessed = [];
            $assessed = [];

            foreach ($selectableIds as $id) {
                $lastAssessed = $metadata[$id]['last_assessed_at'] ?? null;

                if ($lastAssessed === null) {
                    $neverAssessed[] = $id;
                } else {
                    $assessed[$id] = $lastAssessed;
                }
            }

            // Sort assessed by date ascending (oldest first)
            asort($assessed);

            // Combine: never assessed first, then oldest assessed
            $sorted = array_merge($neverAssessed, array_keys($assessed));

            // If count is 0 or negative, return all sorted
            if ($count <= 0) {
                return $sorted;
            }

            return array_slice($sorted, 0, $count);
        };
    }

    /**
     * Helper: Select items that were assessed (have a last_assessed_at date),
     * sorted by oldest assessed date first
     */
    public static function oldestPreviouslyAssessedSelector(): Closure
    {
        return function (array $selectableIds, array $metadata, int $count): array {
            if (empty($selectableIds)) {
                return [];
            }

            // Filter to only items that have been assessed
            $assessed = [];
            foreach ($selectableIds as $id) {
                $lastAssessed = $metadata[$id]['last_assessed_at'] ?? null;
                if ($lastAssessed !== null) {
                    $assessed[$id] = $lastAssessed;
                }
            }

            if (empty($assessed)) {
                return [];
            }

            // Sort by date ascending (oldest first)
            asort($assessed);
            $sorted = array_keys($assessed);

            // If count is 0 or negative, return all sorted
            if ($count <= 0) {
                return $sorted;
            }

            return array_slice($sorted, 0, $count);
        };
    }
}
