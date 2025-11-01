<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Modelable;

class CustomSelect extends Component
{
    #[Modelable]
    public $value;
    
    // Core Properties
    public $model;                      // Model class (e.g., 'App\Models\User')
    public $searchField = 'name';       // Field(s) to search: 'name' or ['name', 'email']
    public $labelField = 'name';        // Field to display in dropdown
    public $labelFields = [];           // Multiple fields for label: ['name', 'email']
    public $labelSeparator = ' - ';     // Separator for multiple label fields
    public $labelTemplate = '';         // Template: '{name} - {email}'
    public $valueField = 'id';          // Field to use as value
    public $placeholder = 'Search...';  // Placeholder text
    public $limit = 10;                 // Max results to show
    public $preloadOptions = true;      // Preload options on mount
    
    // State Properties
    public $search = '';
    public $options = [];
    public $preloadedOptions = [];      // Store preloaded options
    public $selectedLabel = '';
    public $open = false;
    
    // Query Conditions
    public $whereConditions = [];       // [['column', 'operator', 'value'], ...]
    public $whereInConditions = [];     // [['column', [values]], ...]
    public $whereNotInConditions = [];  // [['column', [values]], ...]
    public $orWhereConditions = [];     // [['column', 'operator', 'value'], ...]
    public $whereBetweenConditions = []; // [['column', [start, end]], ...]
    public $whereHasConditions = [];    // [['relation', callback], ...]
    public $queryCallback = null;       // Custom query callback
    public $orderBy = [];               // ['column' => 'direction', ...]
    public $with = [];                  // Relationships to eager load

    public function mount()
    {
        $this->parseArrayFields();
        
        if ($this->value) {
            $this->loadSelectedValue();
        }

        // Preload initial options
        if ($this->preloadOptions) {
            $this->loadInitialOptions();
            $this->preloadedOptions = $this->options;
        }
    }

    protected function parseArrayFields()
    {
        // Parse search field if it's a string array
        if (is_string($this->searchField) && str_starts_with($this->searchField, '[')) {
            $this->searchField = json_decode(str_replace("'", '"', $this->searchField), true) ?? $this->searchField;
        }
        
        // Parse label fields if it's a string array
        if (is_string($this->labelFields) && str_starts_with($this->labelFields, '[')) {
            $this->labelFields = json_decode(str_replace("'", '"', $this->labelFields), true) ?? [];
        }
    }

    public function loadSelectedValue()
    {
        if (!class_exists($this->model) || !$this->value) {
            return;
        }

        $modelClass = $this->model;
        $query = $modelClass::query();
        
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        
        $item = $query->find($this->value);

        if ($item) {
            $this->selectedLabel = $this->getFieldValue($item, $this->labelField);
        }
    }

    public function updatedSearch()
    {
        if (empty($this->search)) {
            // Restore preloaded options when search is cleared
            $this->options = $this->preloadedOptions;
        } else {
            // Load filtered options
            $this->loadOptions();
        }
    }

    public function loadOptions()
    {
        if (!class_exists($this->model)) {
            return;
        }

        $modelClass = $this->model;
        $query = $modelClass::query();

        // Eager loading
        if (!empty($this->with)) {
            $query->with($this->with);
        }

        // Search conditions
        if (!empty($this->search)) {
            $query->where(function (Builder $q) {
                $searchFields = is_array($this->searchField) ? $this->searchField : [$this->searchField];
                
                foreach ($searchFields as $index => $field) {
                    if (str_contains($field, '.')) {
                        // Handle relationship fields
                        $parts = explode('.', $field);
                        $relation = $parts[0];
                        $column = $parts[1];
                        
                        if ($index === 0) {
                            $q->whereHas($relation, function($query) use ($column) {
                                $query->where($column, 'like', "%{$this->search}%");
                            });
                        } else {
                            $q->orWhereHas($relation, function($query) use ($column) {
                                $query->where($column, 'like', "%{$this->search}%");
                            });
                        }
                    } else {
                        // Regular fields
                        if ($index === 0) {
                            $q->where($field, 'like', "%{$this->search}%");
                        } else {
                            $q->orWhere($field, 'like', "%{$this->search}%");
                        }
                    }
                }
            });
        }

        // Apply where conditions
        foreach ($this->whereConditions as $condition) {
            if (is_callable($condition)) {
                $query->where($condition);
            } elseif (count($condition) === 2) {
                $query->where($condition[0], $condition[1]);
            } elseif (count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            }
        }

        // Apply whereIn conditions
        foreach ($this->whereInConditions as $condition) {
            $query->whereIn($condition[0], $condition[1]);
        }

        // Apply whereNotIn conditions
        foreach ($this->whereNotInConditions as $condition) {
            $query->whereNotIn($condition[0], $condition[1]);
        }

        // Apply orWhere conditions
        foreach ($this->orWhereConditions as $condition) {
            if (count($condition) === 2) {
                $query->orWhere($condition[0], $condition[1]);
            } elseif (count($condition) === 3) {
                $query->orWhere($condition[0], $condition[1], $condition[2]);
            }
        }

        // Apply whereBetween conditions
        foreach ($this->whereBetweenConditions as $condition) {
            $query->whereBetween($condition[0], $condition[1]);
        }

        // Apply whereHas conditions
        foreach ($this->whereHasConditions as $condition) {
            $query->whereHas($condition[0], $condition[1]);
        }

        // Apply custom query callback
        if (is_callable($this->queryCallback)) {
            $query = call_user_func($this->queryCallback, $query);
        }

        // Apply ordering
        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        // Get results
        $results = $query->limit($this->limit)->get();

        // Map results
        $this->options = $results->map(function ($item) {
            return [
                'value' => $this->getFieldValue($item, $this->valueField),
                'label' => $this->getFieldValue($item, $this->labelField),
            ];
        })->toArray();
    }

    protected function getFieldValue($item, $field)
    {
        // Handle label template
        if (!empty($this->labelTemplate) && $field === $this->labelField) {
            $template = $this->labelTemplate;
            preg_match_all('/{([^}]+)}/', $template, $matches);
            
            foreach ($matches[1] as $fieldName) {
                $value = str_contains($fieldName, '.') 
                    ? data_get($item, $fieldName) 
                    : ($item->{$fieldName} ?? '');
                $template = str_replace('{' . $fieldName . '}', $value, $template);
            }
            
            return $template;
        }

        // Handle multiple label fields
        if ($field === $this->labelField && !empty($this->labelFields)) {
            $parts = [];
            foreach ($this->labelFields as $labelField) {
                if (str_contains($labelField, '.')) {
                    $parts[] = data_get($item, $labelField);
                } else {
                    $parts[] = $item->{$labelField} ?? null;
                }
            }
            return implode($this->labelSeparator, array_filter($parts));
        }

        // Handle callable
        if (is_callable($field)) {
            return $field($item);
        }

        // Handle dot notation
        if (str_contains($field, '.')) {
            return data_get($item, $field);
        }

        // Regular field
        return $item->{$field} ?? null;
    }

    public function selectOption($value, $label)
    {
        $this->selectedLabel = $label;
        $this->value = $value;
        $this->search = '';
        $this->options = $this->preloadedOptions; // Restore preloaded options
        $this->open = false;

        $this->dispatch('option-selected', value: $value, label: $label);
    }

    public function clear()
    {
        $this->selectedLabel = '';
        $this->value = null;
        $this->search = '';
        $this->options = $this->preloadedOptions; // Restore preloaded options
        $this->open = false;
        
        $this->dispatch('option-cleared');
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->options = $this->preloadedOptions; // Restore preloaded options
    }

    public function toggleDropdown()
    {
        $this->open = !$this->open;
        
        if ($this->open && empty($this->options)) {
            $this->options = $this->preloadedOptions;
        }
    }

    public function loadInitialOptions()
    {
        if (!class_exists($this->model)) {
            return;
        }

        $modelClass = $this->model;
        $query = $modelClass::query();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        // Apply all conditions
        foreach ($this->whereConditions as $condition) {
            if (is_callable($condition)) {
                $query->where($condition);
            } elseif (count($condition) === 2) {
                $query->where($condition[0], $condition[1]);
            } elseif (count($condition) === 3) {
                $query->where($condition[0], $condition[1], $condition[2]);
            }
        }

        foreach ($this->whereInConditions as $condition) {
            $query->whereIn($condition[0], $condition[1]);
        }

        foreach ($this->whereNotInConditions as $condition) {
            $query->whereNotIn($condition[0], $condition[1]);
        }

        foreach ($this->whereBetweenConditions as $condition) {
            $query->whereBetween($condition[0], $condition[1]);
        }

        foreach ($this->whereHasConditions as $condition) {
            $query->whereHas($condition[0], $condition[1]);
        }

        if (is_callable($this->queryCallback)) {
            $query = call_user_func($this->queryCallback, $query);
        }

        if (!empty($this->orderBy)) {
            foreach ($this->orderBy as $column => $direction) {
                $query->orderBy($column, $direction);
            }
        }

        $results = $query->limit($this->limit)->get();

        $this->options = $results->map(function ($item) {
            return [
                'value' => $this->getFieldValue($item, $this->valueField),
                'label' => $this->getFieldValue($item, $this->labelField),
            ];
        })->toArray();
    }

    public function closeDropdown()
    {
        $this->open = false;
        if (empty($this->search)) {
            $this->options = $this->preloadedOptions;
        }
    }
    
    public function render()
    {
        return <<<'HTML'
        <div x-data="{ open: @entangle('open') }" class="relative w-full">
            <!-- Select Button -->
            <button 
                @click="$wire.toggleDropdown()"
                type="button"
                class="w-full px-4 py-2.5 text-left bg-white border border-gray-300 rounded-lg shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            >
                <span class="block truncate" :class="$wire.selectedLabel ? 'text-gray-900' : 'text-gray-500'">
                    {{ $selectedLabel ?: $placeholder }}
                </span>
                
                <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                    @if($value)
                        <svg 
                            wire:click.stop="clear"
                            class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    @else
                        <svg 
                            class="w-5 h-5 text-gray-400 transition-transform"
                            :class="open ? 'rotate-180' : ''"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    @endif
                </span>
            </button>

            <!-- Dropdown -->
            <div 
                x-show="open"
                @click.away="$wire.closeDropdown()"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg"
                style="display: none;"
            >
                <!-- Search Input -->
                <div class="p-2 border-b border-gray-200">
                    <div class="relative">
                        <input 
                            wire:model.live.debounce.300ms="search"
                            type="text"
                            placeholder="{{ $placeholder }}"
                            class="w-full px-3 py-2 pr-20 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @click.stop
                        >
                        
                        <!-- Clear Search Button -->
                        @if(!empty($search))
                            <button
                                wire:click="clearSearch"
                                type="button"
                                class="absolute inset-y-0 right-8 flex items-center pr-2 hover:text-gray-600 cursor-pointer"
                                @click.stop
                            >
                                <svg class="w-4 h-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                        
                        <!-- Search Icon -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Options List -->
                <div class="max-h-60 overflow-y-auto py-1 relative">
                    @if(count($options) > 0)
                        @foreach($options as $option)
                            <div 
                                wire:click="selectOption('{{ $option['value'] }}', '{{ addslashes($option['label']) }}')"
                                class="px-4 py-2.5 cursor-pointer transition-colors flex items-center justify-between {{ $value == $option['value'] ? 'bg-blue-50 text-blue-700' : 'text-gray-900 hover:bg-gray-100' }}"
                            >
                                <span class="truncate">{{ $option['label'] }}</span>
                                @if($value == $option['value'])
                                    <svg class="w-5 h-5 text-blue-600 flex-shrink-0 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                        @endforeach
                    @elseif(!empty($search))
                        <div class="px-4 py-8 text-sm text-gray-500 text-center">
                            No results found
                        </div>
                    @else
                        <div class="px-4 py-8 text-sm text-gray-500 text-center">
                            Type to search...
                        </div>
                    @endif

                    <!-- Loading Spinner -->
                    <div wire:loading wire:target="search" class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-b-lg">
                        <div class="flex flex-col items-center">
                            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="mt-2 text-sm text-gray-600">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
