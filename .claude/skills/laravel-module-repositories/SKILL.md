---
name: laravel-module-repositories
description: "Guide for creating Repository Interface and Repository classes in the modular Laravel architecture. Activates when creating or modifying files inside any module's Repositories/ or Repositories/Contracts/ folder."
license: MIT
metadata:
  author: project
---

# Laravel Module — Repositories

The Repository is the **single point of access to the database**. Services never use Eloquent directly — they always go through the Repository.

## Location

```
app/Modules/{ModuleName}/Repositories/
├── Contracts/
│   └── {ModuleName}RepositoryInterface.php
├── {ModuleName}Filters.php          ← filter methods (criteria pattern)
└── {ModuleName}Repository.php       ← extends {ModuleName}Filters
```

## Rules

1. The Interface defines the contract — Services depend on the Interface, never on the concrete class.
2. The concrete Repository extends `{ModuleName}Filters` and implements the Interface.
3. Never put business logic in a Repository — only data access.
4. Always use `Model::query()` instead of `DB::` for queries.
5. `search()` calls `$this->apply($query, $command->filters)` — never iterate filters manually.
6. Filter method names **must match** the keys in `$command->filters` exactly.
7. The binding between Interface and Implementation is registered in `{ModuleName}ServiceProvider`.
8. **Company scoping comes from the Command, NEVER from `session()`.** A Repository must not read `session('current_company_id')` (nor any request/session state). The active company is resolved in the Controller (from the `{company}` route parameter) and passed in through the Command — see below.

---

## Company Scoping (multi-tenant)

Every module lives under the `{company}` route prefix, so most listings must be scoped to the active company. The company id flows **Controller → Command → Repository**. The Repository stays pure and testable — it receives the id, it never fetches it.

❌ **Wrong — Repository reaches into the session:**

```php
public function search(Search{ModuleName}Command $command): array
{
    $companyId = session('current_company_id'); // ← never do this in a Repository
    $query = {ModuleName}::query()
        ->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    // ...
}
```

✅ **Correct — the Command carries `companyId`:**

```php
// Search{ModuleName}Command — add a nullable companyId
public function __construct(
    public readonly array $filters = [],
    public readonly int $limit = 20,
    public readonly int $offset = 0,
    public readonly ?string $companyId = null,
) {}
```

```php
// Repository — read it from the command
public function search(Search{ModuleName}Command $command): array
{
    $query = {ModuleName}::query()
        ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));
    // ... filters, count, paginate
}
```

```php
// Controller::index — pass the {company} route param into the command
public function index(Request $request, string $company): Response
{
    $command = new Search{ModuleName}Command(
        filters: $request->only([/* ... */]),
        limit: $request->integer('limit', 20),
        offset: $request->integer('offset', 0),
        companyId: $company,
    );
    // ...
}
```

**Many-to-many ownership** (e.g. users belong to companies through a pivot, no `company_id` column): scope with `whereHas` instead of a direct `where`:

```php
$query = User::query()
    ->when($command->companyId, fn ($q) => $q->whereHas(
        'companies',
        fn ($c) => $c->where('company_id', $command->companyId),
    ));
```

**Non-search methods that also need scoping** (e.g. `getActive()`): accept the company id as an explicit nullable parameter — `getActive(?string $companyId = null)` — and have the Controller pass it. Same rule: no `session()` inside the Repository.

---

## Base Class: EloquentQueryFilters

Located at `app/Modules/Shared/Infrastructure/EloquentQueryFilters.php`.

The `apply()` method iterates the filters array, skips empty values (`array_filter`), and dynamically calls the method whose name matches each key.

```php
<?php

namespace App\Modules\Shared\Infrastructure;

use Illuminate\Database\Eloquent\Builder;

class EloquentQueryFilters
{
    protected Builder $builder;

    public function apply(Builder $builder, array $filters): Builder
    {
        $this->builder = $builder;

        foreach (array_filter($filters, fn ($v) => $v !== null && $v !== '') as $key => $value) {
            if (method_exists($this, $key)) {
                call_user_func([$this, $key], $value);
            }
        }

        return $this->builder;
    }
}
```

---

## {ModuleName}Filters

One method per filterable field. The method name **must match** the key used in the `filters` array.

```php
<?php

namespace App\Modules\{ModuleName}\Repositories;

use App\Modules\Shared\Infrastructure\EloquentQueryFilters;
use Illuminate\Database\Eloquent\Builder;

class {ModuleName}Filters extends EloquentQueryFilters
{
    public function search(string $value): Builder
    {
        return $this->builder->where('name', 'like', "%{$value}%");
    }

    public function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }
}
```

---

## {ModuleName}RepositoryInterface

`create()`, `update()`, and `updateStatus()` always return `void`. The caller (Service) is responsible for fetching the record afterwards via `findOrFail()`.

```php
<?php

namespace App\Modules\{ModuleName}\Repositories\Contracts;

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};

interface {ModuleName}RepositoryInterface
{
    public function create(Create{ModuleName}Command $command): void;

    public function findById(string $id): ?{ModuleName};

    public function findOrFail(string $id): {ModuleName};

    public function update({ModuleName} $model, Update{ModuleName}Command $command): void;

    public function updateStatus({ModuleName} $model, UpdateStatus{ModuleName}Command $command): void;

    /** @return array{ data: \Illuminate\Support\Collection<int, {ModuleName}>, total: int } */
    public function search(Search{ModuleName}Command $command): array;
}
```

---

## {ModuleName}Repository

Extends `{ModuleName}Filters` (which extends `EloquentQueryFilters`) and implements the Interface.

```php
<?php

namespace App\Modules\{ModuleName}\Repositories;

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}Repository extends {ModuleName}Filters implements {ModuleName}RepositoryInterface
{
    public function create(Create{ModuleName}Command $command): void
    {
        {ModuleName}::create([
            'id'     => $command->id,
            'name'   => $command->name,
            'status' => 'active',
        ]);
    }

    public function findById(string $id): ?{ModuleName}
    {
        return {ModuleName}::query()->find($id);
    }

    public function findOrFail(string $id): {ModuleName}
    {
        return {ModuleName}::query()->findOrFail($id);
    }

    public function update({ModuleName} $model, Update{ModuleName}Command $command): void
    {
        $model->update([
            'name' => $command->name,
        ]);
    }

    public function updateStatus({ModuleName} $model, UpdateStatus{ModuleName}Command $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /** @return array{ data: \Illuminate\Support\Collection<int, {ModuleName}>, total: int } */
    public function search(Search{ModuleName}Command $command): array
    {
        $query = {ModuleName}::query();

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query
            ->latest()
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data, 'total' => $total];
    }
}
```

---

## How the Criteria Pattern Works

```
Controller                 Command                 Repository
──────────                 ───────                 ──────────
$request->only([           filters: [              apply($query, $filters)
  'search',       ──────►    'search' => 'foo',  ──────►  calls search('foo')
  'status',                  'status' => 'active'          calls status('active')
])                         ]
```

Keys in `$command->filters` → must match method names in `{ModuleName}Filters`.
Empty/null values are automatically skipped by `array_filter` inside `apply()`.

---

## Adding a New Filter

1. Add key to `$request->only([...])` in the Controller.
2. Add method to `{ModuleName}Filters`:

```php
public function companyId(string $value): Builder
{
    return $this->builder->where('company_id', $value);
}
```

No changes needed in the Command, Service, or Repository `search()` method.

---

## Adding Extra Non-Filter Methods

Add to the Interface first, then implement in the Repository:

```php
// In the Interface
public function existsByName(string $name): bool;

// In the Repository
public function existsByName(string $name): bool
{
    return {ModuleName}::query()->where('name', $name)->exists();
}
```

---

## ServiceProvider Binding

```php
$this->app->bind(
    {ModuleName}RepositoryInterface::class,
    {ModuleName}Repository::class,
);
```
