{{-- USAGE EXAMPLES --}}

{{-- 1. BASIC USAGE --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    search-field="name"
    label-field="name"
    value-field="id"
    placeholder="Select a user..."
/>

{{-- 2. SEARCH MULTIPLE FIELDS --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    :search-field="['name', 'email']"
    label-field="name"
    value-field="id"
    placeholder="Search by name or email..."
/>

{{-- 3. WITH WHERE CONDITIONS --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    search-field="name"
    :where-conditions="[
        ['status', '=', 'active'],
        ['role', '!=', 'admin']
    ]"
    placeholder="Select active user..."
/>

{{-- 4. WITH WHEREIN CONDITIONS --}}
<livewire:components.custom-select 
    wire:model="productId"
    model="App\Models\Product"
    search-field="name"
    :where-in-conditions="[
        ['category_id', [1, 2, 3]]
    ]"
    placeholder="Select product..."
/>

{{-- 5. WITH CUSTOM LABEL (Closure) --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    search-field="name"
    :label-field="fn($user) => $user->name . ' - ' . $user->email"
    value-field="id"
    placeholder="Select user..."
/>

{{-- 6. WITH RELATIONSHIPS --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    search-field="name"
    label-field="profile.full_name"
    :with="['profile']"
    placeholder="Select user..."
/>

{{-- 7. WITH ORDERING --}}
<livewire:components.custom-select 
    wire:model="productId"
    model="App\Models\Product"
    search-field="name"
    :order-by="['name' => 'asc']"
    placeholder="Select product..."
/>

{{-- 8. WITH CUSTOM QUERY CALLBACK --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    search-field="name"
    :query-callback="function($query) {
        return $query->where('created_at', '>=', now()->subDays(30))
                     ->whereHas('orders');
    }"
    placeholder="Select recent user with orders..."
/>

{{-- 9. COMPLEX EXAMPLE --}}
<livewire:components.custom-select 
    wire:model="userId"
    model="App\Models\User"
    :search-field="['name', 'email']"
    :label-field="fn($user) => $user->name . ' (' . $user->email . ')'"
    value-field="id"
    placeholder="Search users..."
    :limit="20"
    :where-conditions="[
        ['status', '=', 'active'],
        ['email_verified_at', '!=', null]
    ]"
    :where-in-conditions="[
        ['role_id', [1, 2, 3]]
    ]"
    :with="['profile']"
    :order-by="['name' => 'asc']"
/>

{{-- PARENT COMPONENT EXAMPLE --}}
{{-- 
In your parent Livewire component:

<?php

namespace App\Livewire;

use Livewire\Component;

class UserForm extends Component
{
    public $userId;
    
    protected $listeners = ['option-selected' => 'handleUserSelected'];
    
    public function handleUserSelected($value, $label)
    {
        $this->userId = $value;
        
        // Do something with the selected value
        session()->flash('message', "Selected: {$label}");
    }
    
    public function render()
    {
        return view('livewire.user-form');
    }
}
--}}

{{-- IN YOUR BLADE VIEW --}}
<div>
    <form wire:submit="save">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Select User
            </label>
            
            <livewire:components.custom-select 
                wire:model="userId"
                model="App\Models\User"
                search-field="name"
                label-field="name"
                value-field="id"
                placeholder="Select a user..."
            />
            
            @error('userId')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        
        @if($userId)
            <p class="text-green-600">Selected User ID: {{ $userId }}</p>
        @endif
        
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
            Save
        </button>
    </form>
</div>