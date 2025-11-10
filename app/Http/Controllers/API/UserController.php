<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends BaseApiController
{
    protected string $modelClass = User::class;

    protected string $resourceName = 'Users';

    protected array $indexRelations = ['roles'];

    protected array $showRelations = ['roles', 'permissions', 'managedPrograms'];

    protected array $searchableFields = ['name', 'email'];

    protected array $sortableFields = ['id', 'name', 'email', 'created_at', 'updated_at', 'last_activity'];

    protected function validateStore(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::default()->mixedCase()->uncompromised(3)->min(12)],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);
    }

    protected function validateUpdate(Request $request, Model $resource): array
    {
        return $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($resource->id)],
            'password' => ['nullable', 'confirmed', Password::default()->mixedCase()->uncompromised(3)->min(12)],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);
    }

    /**
     * Override the store method to handle password hashing and role assignment
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('Create '.$this->resourceName);

        $validatedData = $this->validateStore($request);

        // Extract roles before creating user
        $roles = $validatedData['roles'] ?? [];
        unset($validatedData['roles']);

        $resource = $this->modelClass::create($validatedData);

        // Assign roles if provided
        if (! empty($roles)) {
            $resource->assignRole($roles);
        }

        // Load relationships
        if (! empty($this->showRelations)) {
            $resource->load($this->showRelations);
        }

        return response()->json([
            'message' => $this->resourceName.' created successfully',
            'data' => $resource,
        ], 201);
    }

    /**
     * Override the update method to handle password hashing and role assignment
     */
    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $this->authorize('Update '.$this->resourceName);

        $resource = $this->modelClass::findOrFail($id);

        $validatedData = $this->validateUpdate($request, $resource);

        // Extract roles before updating user
        $roles = $validatedData['roles'] ?? null;
        unset($validatedData['roles']);

        // Remove password if not provided
        if (empty($validatedData['password'])) {
            unset($validatedData['password']);
        }

        $resource->update($validatedData);

        // Sync roles if provided
        if ($roles !== null) {
            $resource->syncRoles($roles);
        }

        // Refresh and load relationships
        $resource->refresh();
        if (! empty($this->showRelations)) {
            $resource->load($this->showRelations);
        }

        return response()->json([
            'message' => $this->resourceName.' updated successfully',
            'data' => $resource,
        ], 200);
    }
}
