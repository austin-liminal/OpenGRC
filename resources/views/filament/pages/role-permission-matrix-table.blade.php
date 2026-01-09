<div class="overflow-auto max-h-[70vh] bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
    <table class="role-permission-matrix w-full">
        <thead class="sticky top-0 z-10">
            <tr class="bg-gray-50 dark:bg-gray-800">
                <th class="px-4 py-2 text-left bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">Permissions</th>
                @foreach($getRoles() as $role)
                    <th class="px-4 py-2 text-center bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-gray-100">{{ $role->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($getGroupedPermissions() as $category => $permissions)
                <tr class="border-t border-gray-200 dark:border-gray-700 bg-gray-200 dark:bg-gray-600">
                    <td class="px-4 py-2 text-left font-bold text-gray-900 dark:text-gray-100" colspan="{{ count($getRoles()) + 1 }}">
                        {{ $category ? \Illuminate\Support\Str::headline($category) : 'Uncategorized' }}
                    </td>
                </tr>
                @foreach($permissions as $permission)
                    <tr class="border-t border-gray-100 dark:border-gray-700">
                        <td class="px-4 py-2 text-left pl-8 text-gray-700 dark:text-gray-300">{{ $permission->name }}</td>
                        @foreach($getRoles() as $role)
                            <td class="px-4 py-2 text-center">
                                <label class="inline-flex items-center justify-center">
                                    <input
                                        type="checkbox"
                                        class="text-green-600 dark:text-green-400 transition duration-75 rounded shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 disabled:opacity-70 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:checked:bg-green-800"
                                        @if($role->hasPermissionTo($permission)) checked @endif
                                        wire:click="togglePermission({{ $role->id }}, {{ $permission->id }})"
                                    >
                                </label>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
