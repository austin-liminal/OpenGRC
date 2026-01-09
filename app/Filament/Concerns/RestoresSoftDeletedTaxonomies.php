<?php

namespace App\Filament\Concerns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait RestoresSoftDeletedTaxonomies
{
    /**
     * Create a taxonomy or restore a soft-deleted one with the same slug.
     *
     * If a soft-deleted taxonomy exists with the same slug and type,
     * it will be restored and updated instead of creating a duplicate.
     */
    protected function createOrRestoreTaxonomy(array $data, ?int $parentId = null): Model
    {
        $slug = Str::slug($data['name']);
        $type = $data['type'] ?? strtolower(str_replace(' ', '_', $data['name']));

        // Build query to find soft-deleted taxonomy with same slug and type
        $query = Taxonomy::onlyTrashed()
            ->where('slug', $slug)
            ->where('type', $type);

        // If parent_id is specified, match that too
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        $trashedTaxonomy = $query->first();

        if ($trashedTaxonomy) {
            // Restore the soft-deleted record and update it
            $trashedTaxonomy->restore();
            $trashedTaxonomy->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            // Also restore any soft-deleted children
            self::restoreChildrenRecursively($trashedTaxonomy);

            return $trashedTaxonomy;
        }

        // No soft-deleted record found, create new
        return Taxonomy::create(array_merge($data, [
            'parent_id' => $parentId,
            'type' => $type,
        ]));
    }

    /**
     * Delete a taxonomy and cascade soft-delete to all its children first.
     *
     * This must delete children BEFORE the parent to prevent the vendor package
     * from promoting children to the parent level.
     */
    public static function deleteTaxonomyWithChildren(Taxonomy $taxonomy): void
    {
        // Recursively delete all children first
        $taxonomy->children()->each(function (Taxonomy $child) {
            self::deleteTaxonomyWithChildren($child);
        });

        // Now delete the parent (children count will be 0)
        $taxonomy->delete();
    }

    /**
     * Recursively restore all soft-deleted children of a taxonomy.
     */
    protected static function restoreChildrenRecursively(Taxonomy $taxonomy): void
    {
        // Find and restore soft-deleted children
        Taxonomy::onlyTrashed()
            ->where('parent_id', $taxonomy->id)
            ->each(function (Taxonomy $child) {
                $child->restore();
                // Recursively restore this child's children
                self::restoreChildrenRecursively($child);
            });
    }
}
