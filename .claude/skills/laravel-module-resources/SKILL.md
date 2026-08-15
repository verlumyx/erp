---
name: laravel-module-resources
description: "Guide for creating Eloquent API Resource classes in the modular Laravel architecture. Activates when creating or modifying Resource classes inside any module's Resources/ folder."
license: MIT
metadata:
  author: project
---

# Laravel Module — Resources

Resources transform Eloquent models into structured array/JSON output. **Never expose the raw model** to the frontend or API consumer.

## Location

```
app/Modules/{ModuleName}/Resources/
└── {ModuleName}Resource.php
```

## Rules

1. Always extend `Illuminate\Http\Resources\Json\JsonResource`.
2. Define `toArray()` explicitly — never rely on model's `$visible`/`$hidden`.
3. Never expose internal implementation details (e.g., raw pivot data, internal flags).
4. Always format dates using `->format('Y-m-d H:i:s')`.
5. Use `$this->whenLoaded()` for relationship data to avoid N+1 issues.
6. Resources are used in Controllers when passing data to Inertia or returning JSON.

---

## {ModuleName}Resource

```php
<?php

namespace App\Modules\{ModuleName}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {ModuleName}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'status'     => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

---

## With Relationships

Use `$this->whenLoaded()` — only includes the relation if it was eagerly loaded:

```php
public function toArray(Request $request): array
{
    return [
        'id'       => $this->id,
        'name'     => $this->name,
        'status'   => $this->status,
        'category' => new CategoryResource($this->whenLoaded('category')),
        'tags'     => TagResource::collection($this->whenLoaded('tags')),
        'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
}
```

---

## Usage in Controllers

```php
// Single resource — Inertia
return Inertia::render('{ModuleName}s/show', [
    'item' => new {ModuleName}Resource($model),
]);

// Collection — Inertia
return Inertia::render('{ModuleName}s/index', [
    'items' => {ModuleName}Resource::collection($result['data']),
    'total' => $result['total'],
]);

// JSON API response
return {ModuleName}Resource::collection($result['data'])
    ->additional(['total' => $result['total']]);
```

---

## Enum Values

When the model uses PHP Enums, cast them explicitly:

```php
'status' => $this->status->value,  // if status is a backed Enum
```

---

## Do NOT

```php
// ❌ Never return the model directly
return Inertia::render('...', ['item' => $model]);

// ❌ Never use toJson() or toArray() on the model
return response()->json($model->toArray());
```
