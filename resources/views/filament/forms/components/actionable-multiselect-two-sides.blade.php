<x-dynamic-component
    :component="$getFieldWrapperView()"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()"
    :hint="$getHint()"
    :hint-actions="$getHintActions()"
    :hint-color="$getHintColor()"
    :hint-icon="$getHintIcon()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>
    <div
        class="flex flex-col w-full transition duration-75 text-sm"
        x-data="{
            options: @js($getOptionsForJs()),
            openDropdown: null,
            init(){
                let selectableOptionsSearchInput = document.getElementById('{{str($getStatePath())->remove('.')}}_ms-two-sides_selectableOptionsSearchInput')
                let selectedOptionsSearchInput = document.getElementById('{{str($getStatePath())->remove('.')}}_ms-two-sides_selectedOptionsSearchInput')
                if(selectableOptionsSearchInput && selectedOptionsSearchInput){
                    selectableOptionsSearchInput.value = ''
                    selectedOptionsSearchInput.value = ''
                }
            },
            searchSelectedOptions(elementID, value){
                let liList = document.querySelectorAll(`#${elementID} li`)
                liList.forEach(li => {
                    if(li.innerHTML.toLowerCase().includes(value.toLowerCase())){
                        li.style.display = 'block'
                    }else{
                        li.style.display = 'none'
                    }
                })
            },
            toggleDropdown(name) {
                this.openDropdown = this.openDropdown === name ? null : name
            },
            closeDropdowns() {
                this.openDropdown = null
            }
        }"
        @click.away="closeDropdowns()"
    >
        {{-- Header Actions Bar --}}
        @if(count($getActions()) > 0 || count($getDropdownActions()) > 0)
            <div class="flex flex-wrap items-center gap-2 mb-3 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                {{-- Simple Action Buttons --}}
                @foreach($getActions() as $actionName => $action)
                    <button
                        type="button"
                        wire:click="dispatchFormEvent('ms-two-sides::executeAction', '{{ $getStatePath() }}', '{{ $actionName }}', {{ $action['count'] }})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition
                            bg-white dark:bg-gray-700
                            border border-gray-300 dark:border-gray-600
                            text-gray-700 dark:text-gray-200
                            hover:bg-gray-100 dark:hover:bg-gray-600
                            focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    >
                        @if($action['icon'])
                            <x-dynamic-component :component="$action['icon']" class="w-4 h-4" />
                        @endif
                        <span>{{ $action['label'] }}</span>
                    </button>
                @endforeach

                {{-- Dropdown Action Buttons --}}
                @foreach($getDropdownActions() as $actionName => $action)
                    <div class="relative" x-data>
                        <button
                            type="button"
                            @click="toggleDropdown('{{ $actionName }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition
                                bg-white dark:bg-gray-700
                                border border-gray-300 dark:border-gray-600
                                text-gray-700 dark:text-gray-200
                                hover:bg-gray-100 dark:hover:bg-gray-600
                                focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                        >
                            @if($action['icon'])
                                <x-dynamic-component :component="$action['icon']" class="w-4 h-4" />
                            @endif
                            <span>{{ $action['label'] }}</span>
                            <x-heroicon-m-chevron-down class="w-3 h-3 transition-transform" ::class="{ 'rotate-180': openDropdown === '{{ $actionName }}' }" />
                        </button>

                        {{-- Dropdown Menu --}}
                        <div
                            x-show="openDropdown === '{{ $actionName }}'"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 z-50 mt-1 w-48 origin-top-left rounded-md bg-white dark:bg-gray-700 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                            style="display: none;"
                        >
                            <div class="py-1">
                                @foreach($action['options'] as $option)
                                    <button
                                        type="button"
                                        wire:click="dispatchFormEvent('ms-two-sides::executeAction', '{{ $getStatePath() }}', '{{ $actionName }}', {{ $option['count'] ?? 0 }})"
                                        @click="closeDropdowns()"
                                        class="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                                    >
                                        {{ $option['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Two-Sides Selector --}}
        <div class="flex w-full">
            {{-- Selectable Options --}}
            <div class="flex-1 border overflow-hidden rounded-lg shadow-sm bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-600">
                {{-- Title --}}
                <p class="text-center w-full py-4 bg-gray-300 dark:bg-gray-600">
                    {{$getSelectableLabel()}}
                </p>
                <div class="p-2">
                    {{-- Search Input --}}
                    @if($isSearchable())
                        <input
                            id="{{str($getStatePath())->remove('.')}}_ms-two-sides_selectableOptionsSearchInput"
                            placeholder="Search..."
                            class="w-full border-gray-300 border py-2 px-1 mb-2 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-100 dark:bg-gray-600 dark:border-gray-500"
                            @keyup="searchSelectedOptions('{{str($getStatePath())->remove('.')}}_ms-two-sides_selectableOptions',$event.target.value)"
                        />
                    @endif
                    <ul class="h-48 overflow-y-auto"
                        id="{{str($getStatePath())->remove('.')}}_ms-two-sides_selectableOptions">
                        @foreach($getSelectableOptions() as $value => $label)
                            <li
                                class="cursor-pointer p-1 hover:bg-primary-500 hover:text-white transition"
                                wire:click="dispatchFormEvent('ms-two-sides::selectOption', '{{ $getStatePath() }}', '{{ $value }}')"
                            >
                                {{$label}}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Arrow Actions --}}
            <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
                <p
                    wire:click="dispatchFormEvent('ms-two-sides::selectAllOptions', '{{ $getStatePath() }}')"
                    class="cursor-pointer p-1 hover:bg-primary-500 group rounded"
                    title="Select All"
                >
                    <x-heroicon-o-chevron-double-right class="w-5 h-5 text-primary-500 group-hover:text-white"/>
                </p>
                <p
                    wire:click="dispatchFormEvent('ms-two-sides::unselectAllOptions', '{{ $getStatePath() }}')"
                    class="cursor-pointer p-1 hover:bg-primary-500 group rounded"
                    title="Unselect All"
                >
                    <x-heroicon-o-chevron-double-left class="w-5 h-5 text-primary-500 group-hover:text-white"/>
                </p>
            </div>

            {{-- Selected Options --}}
            <div class="flex-1 border overflow-hidden rounded-lg shadow-sm"
                 :class="{
                        'bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-600': ! (@js($getStatePath()) in $wire.__instance.serverMemo.errors),
                        'bg-white border-danger-600 dark:bg-gray-700 dark:border-danger-400': (@js($getStatePath()) in $wire.__instance.serverMemo.errors),
                    }"
            >
                {{-- Title --}}
                <p class='text-center w-full py-4 rounded-t-lg bg-gray-300 dark:bg-gray-600'>
                    {{$getSelectedLabel()}}
                </p>
                <div class="p-2">
                    {{-- Search Input --}}
                    @if($isSearchable())
                        <input
                            id="{{str($getStatePath())->remove('.')}}_ms-two-sides_selectedOptionsSearchInput"
                            placeholder="Search..."
                            class="w-full border-gray-300 border py-2 px-1 mb-2 rounded focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-100 dark:bg-gray-600 dark:border-gray-500"
                            @keyup="searchSelectedOptions('{{str($getStatePath())->remove('.')}}_ms-two-sides_selectedOptions',$event.target.value)"
                        />
                    @endif
                    {{-- Options List --}}
                    <ul class="h-48 overflow-y-auto"
                        id="{{str($getStatePath())->remove('.')}}_ms-two-sides_selectedOptions">
                        @foreach($getSelectedOptions() as $value => $label)
                            <li
                                class="cursor-pointer p-1 hover:bg-primary-500 hover:text-white transition"
                                wire:click="dispatchFormEvent('ms-two-sides::unselectOption', '{{ $getStatePath() }}', '{{ $value }}')"
                            >
                                {{$label}}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
