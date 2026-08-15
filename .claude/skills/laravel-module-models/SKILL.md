---
name: laravel-module-models
description: "Guide for creating Eloquent Model classes in the modular Laravel architecture. Activates when creating or modifying Model classes inside any module's Models/ folder. Models use UUID v7 as primary key."
license: MIT
metadata:
  author: project
---

# Laravel Module — Models

Models define relationships, scopes, and casts. They contain **no business logic**.

## Location

```
app/Modules/{ModuleName}/Models/
└── {ModuleName}.php
```

## UUID v7 — Primary Key

All models use **UUID v7** as the primary key. UUID v7 is time-ordered, which means records sort correctly by ID without needing an additional `created_at` sort. This is critical for performance and predictability.

### Why UUID v7 over UUID v4?
- UUID v4 is random — index fragmentation, poor sort order.
- UUID v7 is time-ordered — monotonically increasing, works as a natural sort key.
- UUID v7 over ULID: broader standard support, valid UUID format.

### Implementation

Use the `HasUuids` trait and override `newUniqueId()` to generate UUID v7:

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class {ModuleName} extends Model
{
    use HasUuids;

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }
}
```

The `HasUuids` trait automatically sets:
- `$keyType = 'string'`
- `$incrementing = false`

---

## Full Model Template

```php
<?php

namespace App\Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class {ModuleName} extends Model
{
    use HasFactory, HasUuids;

    protected $table = '{module_names}';

    protected $fillable = [
        'name',
        'status',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
```

---

## Migration — UUID v7 Column

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{module_names}', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{module_names}');
    }
};
```

---

## Factory

```php
<?php

namespace Database\Factories\Modules\{ModuleName};

use App\Modules\{ModuleName}\Models\{ModuleName};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<{ModuleName}>
 */
class {ModuleName}Factory extends Factory
{
    protected $model = {ModuleName}::class;

    public function definition(): array
    {
        return [
            'name'   => $this->faker->words(3, true),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
```

---

## Relationships

Add relationship methods with explicit return types:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

public function items(): HasMany
{
    return $this->hasMany(Item::class);
}
```

---

## Rules

1. **No business logic** in the model — only relationships, scopes, casts.
2. Always use `HasUuids` + `newUniqueId()` returning `Str::uuid7()`.
3. Always use `HasFactory`.
4. Define casts in `casts()` method (not `$casts` property).
5. Use explicit return types on all relationship methods.
6. Table name must be explicit in `$table` using snake_case plural.
