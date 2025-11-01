# CustomSelect Component - Complete Documentation

A fully-featured, reusable searchable select component for Laravel Livewire with advanced query support.

## 📋 Table of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Label Configuration](#label-configuration)
- [Search Configuration](#search-configuration)
- [Query Conditions](#query-conditions)
- [Advanced Examples](#advanced-examples)
- [Events](#events)
- [Styling](#styling)
- [Troubleshooting](#troubleshooting)

---

## 🚀 Installation

### Step 1: Create the Component

```bash
php artisan make:livewire Components/CustomSelect
```

### Step 2: Copy the Component Code

Replace the content of `app/Livewire/Components/CustomSelect.php` with the provided component code.

### Step 3: Ensure Dependencies

Make sure your layout includes Livewire and Alpine.js:

```html
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
```

---

## 📦 Basic Usage

### Simple Example

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    search-field="name"
    label-field="name"
    value-field="id" 
    placeholder="Select a user..." 
/>
```

### With Parent Component

```php
<?php
namespace App\Livewire;

use Livewire\Component;

class UserForm extends Component
{
    public $userId;
    
    public function save()
    {
        $this->validate(['userId' => 'required']);
        // Save logic
    }
    
    public function render()
    {
        return view('livewire.user-form');
    }
}
```

---

## 🏷️ Label Configuration

### Method 1: Single Field (Simplest)

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    label-field="name"
    value-field="id" 
/>
```

### Method 2: Multiple Fields Array

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :label-fields="['name', 'email']"
    label-separator=" - "
    value-field="id" 
/>
```

Output: `John Doe - john@example.com`

### Method 3: Label Template (Most Flexible)

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    label-template="{name} ({email})"
    value-field="id" 
/>
```

Output: `John Doe (john@example.com)`

### Method 4: Model Accessor (Recommended)

**In your Model:**
```php
// app/Models/User.php
public function getFullLabelAttribute()
{
    return $this->name . ' - ' . $this->email;
}
```

**In Blade:**
```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    label-field="full_label"
    value-field="id" 
/>
```

### Method 5: Relationship Fields

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    label-template="{name} - {profile.company}"
    :with="['profile']"
    value-field="id" 
/>
```

---

## 🔍 Search Configuration

### Single Field Search

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    search-field="name"
/>
```

### Multiple Fields Search

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :search-field="['name', 'email', 'phone']"
/>
```

### Search on Relationship Fields

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :search-field="['name', 'profile.company']"
    :with="['profile']"
/>
```

---

## 🎯 Query Conditions

### Where Conditions

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :where-conditions="[
        ['status', '=', 'active'],
        ['email_verified_at', '!=', null],
        ['created_at', '>=', now()->subDays(30)]
    ]"
/>
```

### WhereIn Conditions

```blade
<livewire:components.custom-select 
    wire:model="productId" 
    model="App\Models\Product" 
    :where-in-conditions="[
        ['category_id', [1, 2, 3, 4]],
        ['status', ['active', 'featured']]
    ]"
/>
```

### WhereNotIn Conditions

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :where-not-in-conditions="[
        ['id', [5, 10, 15]],
        ['role', ['banned', 'suspended']]
    ]"
/>
```

### WhereBetween Conditions

```blade
<livewire:components.custom-select 
    wire:model="productId" 
    model="App\Models\Product" 
    :where-between-conditions="[
        ['price', [100, 1000]],
        ['created_at', [now()->subDays(30), now()]]
    ]"
/>
```

### WhereHas Conditions (Relationships)

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :where-has-conditions="[
        ['orders', function($query) {
            $query->where('status', 'completed')
                  ->where('total', '>', 1000);
        }],
        ['posts', function($query) {
            $query->where('published', true);
        }]
    ]"
/>
```

### Custom Query Callback

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :query-callback="function($query) {
        return $query->where('status', 'active')
                     ->whereHas('orders', function($q) {
                         $q->where('created_at', '>=', now()->subDays(30));
                     })
                     ->withCount('orders')
                     ->having('orders_count', '>', 5);
    }"
/>
```

### Ordering Results

```blade
<livewire:components.custom-select 
    wire:model="productId" 
    model="App\Models\Product" 
    :order-by="[
        'name' => 'asc',
        'created_at' => 'desc'
    ]"
/>
```

### Eager Loading Relationships

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :with="['profile', 'roles', 'company']"
/>
```

---

## 🔥 Advanced Examples

### E-commerce Product Selector

```blade
<livewire:components.custom-select 
    wire:model="productId" 
    model="App\Models\Product" 
    :search-field="['name', 'sku', 'description']"
    label-template="{name} - ${price} (SKU: {sku})"
    value-field="id"
    placeholder="Search products..."
    :limit="20"
    :where-conditions="[
        ['status', '=', 'active'],
        ['stock', '>', 0]
    ]"
    :where-in-conditions="[
        ['category_id', [1, 2, 3]]
    ]"
    :order-by="['name' => 'asc']"
/>
```

### User Selector with Roles

```blade
<livewire:components.custom-select 
    wire:model="userId" 
    model="App\Models\User" 
    :search-field="['name', 'email', 'username']"
    :label-fields="['name', 'email']"
    label-separator=" | "
    value-field="id"
    placeholder="Search users..."
    :where-conditions="[
        ['status', 'active'],
        ['email_verified_at', '!=', null]
    ]"
    :where-has-conditions="[
        ['roles', function($query) {
            $query->whereIn('name', ['admin', 'editor']);
        }]
    ]"
    :with="['roles']"
    :order-by="['name' => 'asc']"
/>
```

### Recent Active Customers

```blade
<livewire:components.custom-select 
    wire:model="customerId" 
    model="App\Models\Customer" 
    :search-field="['name', 'email', 'phone']"
    label-template="{name} - {email} ({company.name})"
    value-field="id"
    placeholder="Search customers..."
    :query-callback="function($query) {
        return $query->where('created_at', '>=', now()->subDays(90))
                     ->whereHas('orders', function($q) {
                         $q->where('status', 'completed');
                     })
                     ->withCount('orders')
                     ->orderBy('orders_count', 'desc');
    }"
    :with="['company']"
    :limit="15"
/>
```

### Location-Based Search

```blade
<livewire:components.custom-select 
    wire:model="storeId" 
    model="App\Models\Store" 
    :search-field="['name', 'city', 'address']"
    label-template="{name} - {city}, {state}"
    value-field="id"
    placeholder="Search stores..."
    :where-conditions="[
        ['is_active', true]
    ]"
    :where-in-conditions="[
        ['state', ['CA', 'NY', 'TX']]
    ]"
    :order-by="['city' => 'asc', 'name' => 'asc']"
/>
```

---

## 📡 Events

### Listening to Selection

```php
// In parent component
protected $listeners = ['option-selected' => 'handleSelection'];

public function handleSelection($value, $label)
{
    $this->selectedId = $value;
    $this->selectedName = $label;
    
    session()->flash('message', "Selected: {$label}");
}
```

### Listening to Clear

```php
protected $listeners = ['option-cleared' => 'handleClear'];

public function handleClear()
{
    $this->selectedId = null;
    session()->flash('message', 'Selection cleared');
}
```

---

## 🎨 Styling

### Custom Width

```blade
<div class="w-96">
    <livewire:components.custom-select 
        wire:model="userId" 
        model="App\Models\User" 
    />
</div>
```

### With Label and Error

```blade
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Select User *
    </label>
    
    <livewire:components.custom-select 
        wire:model="userId" 
        model="App\Models\User" 
        search-field="name"
        placeholder="Choose a user..."
    />
    
    @error('userId')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
```

### In a Form Grid

```blade
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-2">User</label>
        <livewire:components.custom-select 
            wire:model="userId" 
            model="App\Models\User" 
        />
    </div>
    
    <div>
        <label class="block text-sm font-medium mb-2">Role</label>
        <livewire:components.custom-select 
            wire:model="roleId" 
            model="App\Models\Role" 
        />
    </div>
</div>
```

---

## 🔧 Troubleshooting

### Issue: "Column not found" Error

**Problem:** Using array syntax as string
```blade
<!-- Wrong -->
search-field="['name', 'email']"
```

**Solution:** Use proper array binding
```blade
<!-- Correct -->
:search-field="['name', 'email']"
```

### Issue: Closure/Callback Not Working

**Problem:** Passing closure directly in Blade

**Solution:** Use label-template or label-fields instead
```blade
<!-- Use this -->
label-template="{name} - {email}"

<!-- Not this -->
:label-field="fn($user) => $user->name"
```

### Issue: No Results Showing

**Check:**
1. Model exists and has data
2. Search field names are correct
3. Where conditions aren't too restrictive
4. Database connection is working

**Debug:**
```php
// In your component, add:
public function testQuery()
{
    $modelClass = $this->model;
    dd($modelClass::all());
}
```

### Issue: Dropdown Not Opening

**Check:**
1. Alpine.js is loaded
2. Livewire is loaded
3. No JavaScript console errors
4. Browser cache cleared

---

## 📚 Component Properties Reference

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `model` | string | required | Model class path |
| `search-field` | string/array | 'name' | Field(s) to search |
| `label-field` | string | 'name' | Field to display |
| `label-fields` | array | [] | Multiple fields for label |
| `label-separator` | string | ' - ' | Separator for label fields |
| `label-template` | string | '' | Template for label |
| `value-field` | string | 'id' | Field to use as value |
| `placeholder` | string | 'Search...' | Input placeholder |
| `limit` | int | 10 | Max results to show |
| `where-conditions` | array | [] | Where clauses |
| `where-in-conditions` | array | [] | WhereIn clauses |
| `where-not-in-conditions` | array | [] | WhereNotIn clauses |
| `or-where-conditions` | array | [] | OrWhere clauses |
| `where-between-conditions` | array | [] | WhereBetween clauses |
| `where-has-conditions` | array | [] | WhereHas clauses |
| `query-callback` | callable | null | Custom query modifier |
| `order-by` | array | [] | Ordering |
| `with` | array | [] | Eager load relations |

---

## 📝 License

This component is open-source and free to use in your projects.

## 🤝 Contributing

Feel free to modify and enhance this component for your needs!

---

**Created with ❤️ for Laravel Livewire**