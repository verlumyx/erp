---
name: laravel-modular-architecture
description: Modular architecture guide for Laravel projects. Use this skill whenever the user asks to create a module, a service, a repository, a Command, a controller, or any class within a Laravel project with modular architecture. Also use it when the user says "create the X module", "add the X CRUD", "implement the X logic", or when asking how to organize files in Laravel.
---

# Laravel Modular Architecture

This skill defines the standard modular architecture for Laravel projects. Every new class must follow this structure without exception.

---

## Folder Structure

Every module includes by default the following use cases: **Crear, Actualizar, Actualizar Estado, Ver, Editar y Listar**.

```
app/
└── Modules/
    └── {ModuleName}/
        ├── Controllers/
        │   ├── {ModuleName}GetController.php          (index, create, show, edit)
        │   ├── {ModuleName}PostController.php         (store — invokable)
        │   ├── {ModuleName}PutController.php          (update — invokable)
        │   └── {ModuleName}UpdateStatusController.php (updateStatus — invokable)
        ├── Requests/
        │   ├── Create{ModuleName}Request.php
        │   ├── Update{ModuleName}Request.php
        │   └── UpdateStatus{ModuleName}Request.php
        ├── Resources/
        │   └── {ModuleName}Resource.php
        ├── Services/
        │   ├── {ModuleName}CreateService.php
        │   ├── {ModuleName}UpdateService.php
        │   ├── {ModuleName}UpdateStatusService.php
        │   ├── {ModuleName}FindService.php
        │   └── {ModuleName}SearchService.php
        ├── Repositories/
        │   ├── Contracts/
        │   │   └── {ModuleName}RepositoryInterface.php
        │   ├── {ModuleName}Filters.php
        │   └── {ModuleName}Repository.php
        ├── Commands/
        │   ├── Create{ModuleName}Command.php
        │   ├── Update{ModuleName}Command.php
        │   ├── UpdateStatus{ModuleName}Command.php
        │   └── Search{ModuleName}Command.php
        ├── Models/
        │   └── {ModuleName}.php           (UUID v7 primary key)
        ├── Exceptions/
        │   └── {ModuleName}NotFoundException.php
        ├── Providers/
        │   └── {ModuleName}ServiceProvider.php
        └── routes.php
```

---

## Layer Responsibilities

| Layer | Responsibility |
|---|---|
| **GetController** | Handles all GET requests: index (Listar), create (form Crear), show (Ver), edit (form Editar). |
| **PostController** | Handles store (Crear). Invokable. |
| **PutController** | Handles update (Actualizar). Invokable. |
| **UpdateStatusController** | Handles updateStatus (Actualizar Estado). Invokable. |
| **Request** | Input validation and authorization. |
| **Service** | Business logic. One Service per action. Receives repositories via constructor. |
| **Repository** | Single point of database access. Implements an Interface. |
| **Command** | Typed object for transporting data between layers. No logic. Properties are `readonly`. |
| **Resource** | Transforms the model into the JSON response format. Never expose the model directly. |
| **Model** | Only relationships, scopes, and casts. No business logic. |
| **Exception** | Custom exceptions for the module. |
| **Provider** | Registers the Interface → Implementation bindings and loads routes for the module. |

---

## Default Use Cases

| Use Case | Method | Route | Controller method | Service |
|---|---|---|---|---|
| **Listar** | GET | `/{module}` | `index` | `{Module}SearchService` |
| **Crear (form)** | GET | `/{module}/create` | `create` | — |
| **Crear (action)** | POST | `/{module}` | `__invoke` | `{Module}CreateService` |
| **Ver** | GET | `/{module}/{id}` | `show` | `{Module}FindService` |
| **Editar (form)** | GET | `/{module}/{id}/edit` | `edit` | `{Module}FindService` |
| **Actualizar (action)** | PUT | `/{module}/{id}` | `__invoke` | `{Module}UpdateService` |
| **Actualizar Estado** | PUT | `/{module}/{id}/status` | `__invoke` | `{Module}UpdateStatusService` |

---

## Namespaces

Always follow this pattern:

```php
namespace App\Modules\{ModuleName}\Controllers;
namespace App\Modules\{ModuleName}\Services;
namespace App\Modules\{ModuleName}\Repositories;
namespace App\Modules\{ModuleName}\Repositories\Contracts;
namespace App\Modules\{ModuleName}\Commands;
namespace App\Modules\{ModuleName}\Models;
namespace App\Modules\{ModuleName}\Requests;
namespace App\Modules\{ModuleName}\Resources;
namespace App\Modules\{ModuleName}\Exceptions;
namespace App\Modules\{ModuleName}\Providers;
```

---

## Layer-Specific Skills

Each layer has a dedicated skill with full templates and rules. Activate the relevant skill when working on that layer:

| Layer | Skill |
|---|---|
| Controllers | `laravel-module-controllers` |
| Requests | `laravel-module-requests` |
| Resources | `laravel-module-resources` |
| Services | `laravel-module-services` |
| Repositories | `laravel-module-repositories` |
| Commands | `laravel-module-commands` |
| Models | `laravel-module-models` |
| Exceptions | `laravel-module-exceptions` |

---

## Code Templates

### Command

The `id` is always the **first property** in Create commands. It is sent by the client in the request body — never generated on the backend.

```php
<?php

namespace App\Modules\{ModuleName}\Commands;

use App\Modules\{ModuleName}\Requests\Create{ModuleName}Request;

class Create{ModuleName}Command
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        // add properties as needed
    ) {}

    public static function fromRequest(Create{ModuleName}Request $request): self
    {
        return new self(
            id: $request->string('id')->toString(),
            name: $request->string('name')->toString(),
        );
    }
}
```

```php
<?php

namespace App\Modules\{ModuleName}\Commands;

use App\Modules\{ModuleName}\Requests\Update{ModuleName}Request;

class Update{ModuleName}Command
{
    public function __construct(
        public readonly string $name,
        // add properties as needed
    ) {}

    public static function fromRequest(Update{ModuleName}Request $request): self
    {
        return new self(
            name: $request->name,
        );
    }
}
```

```php
<?php

namespace App\Modules\{ModuleName}\Commands;

use App\Modules\{ModuleName}\Requests\UpdateStatus{ModuleName}Request;

class UpdateStatus{ModuleName}Command
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatus{ModuleName}Request $request): self
    {
        return new self(
            status: $request->status,
        );
    }
}
```

```php
<?php

namespace App\Modules\{ModuleName}\Commands;

class Search{ModuleName}Command
{
    /**
     * @param array<string, mixed> $filters  Criteria array (e.g. ['status' => 'active', 'search' => 'foo'])
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly int $limit = 20,
        public readonly int $offset = 0,
    ) {}
}
```

---

### Repository Interface

```php
<?php

namespace App\Modules\{ModuleName}\Repositories\Contracts;

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};

interface {ModuleName}RepositoryInterface
{
    public function create(Create{ModuleName}Command $command): void;
    public function findById(string $id): ?{ModuleName};
    public function findOrFail(string $id): {ModuleName};
    public function update({ModuleName} $model, Update{ModuleName}Command $command): void;
    public function updateStatus({ModuleName} $model, UpdateStatus{ModuleName}Command $command): void;

    /** @return array{ data: {ModuleName}[], total: int } */
    public function search(Search{ModuleName}Command $command): array;
}
```

---

### Repository

```php
<?php

namespace App\Modules\{ModuleName}\Repositories;

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}Repository implements {ModuleName}RepositoryInterface
{
    public function create(Create{ModuleName}Command $command): void
    {
        {ModuleName}::create([
            'id'   => $command->id,
            'name' => $command->name,
        ]);
    }

    public function findById(string $id): ?{ModuleName}
    {
        return {ModuleName}::find($id);
    }

    public function findOrFail(string $id): {ModuleName}
    {
        return {ModuleName}::findOrFail($id);
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

    /** @return array{ data: {ModuleName}[], total: int } */
    public function search(Search{ModuleName}Command $command): array
    {
        $query = {ModuleName}::query();

        if ($command->search !== null) {
            $query->where('name', 'like', "%{$command->search}%");
        }

        if ($command->status !== null) {
            $query->where('status', $command->status);
        }

        $total = $query->count();

        $data = $query->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data, 'total' => $total];
    }
}
```

---

### Services

```php
<?php

// {ModuleName}CreateService.php

namespace App\Modules\{ModuleName}\Services;

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}CreateService
{
    public function __construct(
        private readonly {ModuleName}RepositoryInterface $repository,
    ) {}

    public function execute(Create{ModuleName}Command $command): {ModuleName}
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
```

```php
<?php

// {ModuleName}UpdateService.php

namespace App\Modules\{ModuleName}\Services;

use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Exceptions\{ModuleName}NotFoundException;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}UpdateService
{
    public function __construct(
        private readonly {ModuleName}RepositoryInterface $repository,
    ) {}

    public function execute(string $id, Update{ModuleName}Command $command): {ModuleName}
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new {ModuleName}NotFoundException();
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id);
    }
}
```

```php
<?php

// {ModuleName}UpdateStatusService.php

namespace App\Modules\{ModuleName}\Services;

use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Exceptions\{ModuleName}NotFoundException;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}UpdateStatusService
{
    public function __construct(
        private readonly {ModuleName}RepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatus{ModuleName}Command $command): {ModuleName}
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new {ModuleName}NotFoundException();
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id);
    }
}
```

```php
<?php

// {ModuleName}FindService.php

namespace App\Modules\{ModuleName}\Services;

use App\Modules\{ModuleName}\Exceptions\{ModuleName}NotFoundException;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}FindService
{
    public function __construct(
        private readonly {ModuleName}RepositoryInterface $repository,
    ) {}

    public function execute(string $id): {ModuleName}
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new {ModuleName}NotFoundException();
        }

        return $model;
    }
}
```

```php
<?php

// {ModuleName}SearchService.php

namespace App\Modules\{ModuleName}\Services;

use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;

class {ModuleName}SearchService
{
    public function __construct(
        private readonly {ModuleName}RepositoryInterface $repository,
    ) {}

    /** @return array{ data: mixed[], total: int } */
    public function execute(Search{ModuleName}Command $command): array
    {
        return $this->repository->search($command);
    }
}
```

---

### Controllers

```php
<?php

// {ModuleName}GetController.php — Listar, Ver, form Crear, form Editar

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Services\{ModuleName}FindService;
use App\Modules\{ModuleName}\Services\{ModuleName}SearchService;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class {ModuleName}GetController extends Controller
{
    public function __construct(
        private readonly {ModuleName}SearchService $searchService,
        private readonly {ModuleName}FindService $findService,
    ) {}

    public function index(Request $request): Response
    {
        $command = new Search{ModuleName}Command(
            filters: $request->only(['search', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('{ModuleName}s/index', [
            'items'   => $result['data'],
            'total'   => $result['total'],
            'filters' => $request->only(['search', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('{ModuleName}s/create');
    }

    // NOTE: $company (the {company} route prefix) MUST come before $id.
    // Laravel binds scalar route params by position, not by name.
    public function show(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('{ModuleName}s/show', [
            'item' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        $model = $this->findService->execute($id);

        return Inertia::render('{ModuleName}s/edit', [
            'item' => $model,
        ]);
    }
}
```

```php
<?php

// {ModuleName}PostController.php — Crear

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Requests\Create{ModuleName}Request;
use App\Modules\{ModuleName}\Services\{ModuleName}CreateService;
use Illuminate\Http\RedirectResponse;

class {ModuleName}PostController extends Controller
{
    public function __construct(
        private readonly {ModuleName}CreateService $createService,
    ) {}

    public function __invoke(Create{ModuleName}Request $request): RedirectResponse
    {
        $this->createService->execute(
            Create{ModuleName}Command::fromRequest($request)
        );

        // The {company} prefix means 'index' needs the company param.
        return redirect()->route('{module-name}s.index', ['company' => $request->route('company')]);
    }
}
```

```php
<?php

// {ModuleName}PutController.php — Actualizar

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Requests\Update{ModuleName}Request;
use App\Modules\{ModuleName}\Services\{ModuleName}UpdateService;
use Illuminate\Http\RedirectResponse;

class {ModuleName}PutController extends Controller
{
    public function __construct(
        private readonly {ModuleName}UpdateService $updateService,
    ) {}

    public function __invoke(Update{ModuleName}Request $request, string $company, string $id): RedirectResponse
    {
        $this->updateService->execute(
            $id,
            Update{ModuleName}Command::fromRequest($request)
        );

        return redirect()->route('{module-name}s.show', ['company' => $company, 'id' => $id]);
    }
}
```

```php
<?php

// {ModuleName}UpdateStatusController.php — Actualizar Estado

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\UpdateStatus{ModuleName}Command;
use App\Modules\{ModuleName}\Requests\UpdateStatus{ModuleName}Request;
use App\Modules\{ModuleName}\Services\{ModuleName}UpdateStatusService;
use Illuminate\Http\RedirectResponse;

class {ModuleName}UpdateStatusController extends Controller
{
    public function __construct(
        private readonly {ModuleName}UpdateStatusService $updateStatusService,
    ) {}

    public function __invoke(UpdateStatus{ModuleName}Request $request, string $company, string $id): RedirectResponse
    {
        $this->updateStatusService->execute(
            $id,
            UpdateStatus{ModuleName}Command::fromRequest($request)
        );

        return redirect()->route('{module-name}s.show', ['company' => $company, 'id' => $id]);
    }
}
```

---

### Resource

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

### Exception

```php
<?php

namespace App\Modules\{ModuleName}\Exceptions;

use Exception;

class {ModuleName}NotFoundException extends Exception
{
    protected $message = '{ModuleName} not found.';
}
```

---

### ServiceProvider

```php
<?php

namespace App\Modules\{ModuleName}\Providers;

use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;
use App\Modules\{ModuleName}\Repositories\{ModuleName}Repository;
use Illuminate\Support\ServiceProvider;

class {ModuleName}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            {ModuleName}RepositoryInterface::class,
            {ModuleName}Repository::class,
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');
    }
}
```

---

### Routes

```php
<?php

declare(strict_types=1);

use App\Modules\{ModuleName}\Controllers\{ModuleName}GetController;
use App\Modules\{ModuleName}\Controllers\{ModuleName}PostController;
use App\Modules\{ModuleName}\Controllers\{ModuleName}PutController;
use App\Modules\{ModuleName}\Controllers\{ModuleName}UpdateStatusController;
use Illuminate\Support\Facades\Route;

// Flexible UUID pattern — models use UUID v7, so do NOT use the strict v4 regex.
$uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

// Every module is multi-tenant: routes are nested under the {company} prefix
// and protected by the company.access middleware. This adds `company` as the
// FIRST route parameter — see the controller note below about parameter order.
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('{module-name}s')->group(function () use ($uuid) {
            // Listar
            Route::get('/', [{ModuleName}GetController::class, 'index'])->name('{module-name}s.index');

            // Crear (form)
            Route::get('/create', [{ModuleName}GetController::class, 'create'])->name('{module-name}s.create');

            // Crear (action)
            Route::post('/', {ModuleName}PostController::class)->name('{module-name}s.store');

            // Ver
            Route::get('/{id}', [{ModuleName}GetController::class, 'show'])
                ->where('id', $uuid)
                ->name('{module-name}s.show');

            // Editar (form)
            Route::get('/{id}/edit', [{ModuleName}GetController::class, 'edit'])
                ->where('id', $uuid)
                ->name('{module-name}s.edit');

            // Actualizar
            Route::put('/{id}', {ModuleName}PutController::class)
                ->where('id', $uuid)
                ->name('{module-name}s.update');

            // Actualizar Estado
            Route::put('/{id}/status', {ModuleName}UpdateStatusController::class)
                ->where('id', $uuid)
                ->name('{module-name}s.update-status');
        });
    });
```

> ⚠️ Because of the `{company}` prefix, **every** id-bearing controller method must declare `string $company` **before** `string $id`. Laravel binds scalar route params by position, not by name — see the `laravel-module-controllers` skill for the full explanation. Getting this wrong silently passes the company UUID where the id is expected.

---

## Registering the Module

### 1. Service Provider

In `bootstrap/providers.php` (Laravel 11+):

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Modules\{ModuleName}\Providers\{ModuleName}ServiceProvider::class,
];
```

### 2. Module + permissions seed

Register the module and its permissions in `database/sql/seed_initial_modules.sql`
(raw PostgreSQL, idempotent with `ON CONFLICT DO NOTHING` — NOT a migration).

### 3. Sidebar menu (ALWAYS add the URL)

**Every new module MUST add its entry to `database/seeders/MenuSeeder.php`** — the
single source of truth for the sidebar. Without it the module exists but has no
entry in the sidebar, so users can't reach it. Add an item to the `$menus` array
with the module's `url` (e.g. `/{module-name}s`) and its `.list` permission:

```php
[
    'parent_id' => null,
    'title' => '{Label}',
    'icon' => '{Icon}',
    'url' => '/{module-name}s',
    'permission' => '{module-name}s.list',
    'order' => {order},
    'is_active' => true,
    'section' => 'main',
],
```

- `url` must match the module's index route (without the `/{company}` prefix —
  `HandleInertiaRequests` prepends the company id at runtime).
- `permission` is the module's `.list` action; the menu is hidden if the user
  lacks it.
- A child goes in the parent's `children` array; the seeder resolves `parent_id`.
  A parent group with children must use `section = 'main'` (the footer nav does
  not render children). A group with `url = null` stays hidden until it has at
  least one visible child.
- Never set `id`: `Menu` uses `HasUuids` and generates a uuid7 on insert. Passing
  an `id` makes `updateOrCreate` rewrite the PK on every run and breaks `parent_id`.
- The seeder is idempotent (matches on `title` + `section`), so it can be re-run.

> Apply it with:
> `docker compose exec -T app php artisan db:seed --class=MenuSeeder --force`

---

## Test Structure

```
tests/
├── Unit/
│   └── Modules/
│       └── {ModuleName}/
│           ├── {ModuleName}CreateServiceTest.php
│           ├── {ModuleName}UpdateServiceTest.php
│           ├── {ModuleName}UpdateStatusServiceTest.php
│           ├── {ModuleName}FindServiceTest.php
│           └── {ModuleName}SearchServiceTest.php
└── Feature/
    └── Modules/
        └── {ModuleName}/
            ├── {ModuleName}ListTest.php
            ├── {ModuleName}CreateTest.php
            ├── {ModuleName}ShowTest.php
            ├── {ModuleName}UpdateTest.php
            └── {ModuleName}UpdateStatusTest.php
```

### Unit Test Template (Service)

```php
<?php

namespace Tests\Unit\Modules\{ModuleName};

use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Models\{ModuleName};
use App\Modules\{ModuleName}\Repositories\Contracts\{ModuleName}RepositoryInterface;
use App\Modules\{ModuleName}\Services\{ModuleName}CreateService;
use Mockery;
use Tests\TestCase;

class {ModuleName}CreateServiceTest extends TestCase
{
    public function test_creates_successfully(): void
    {
        $command = new Create{ModuleName}Command(name: 'Test');
        $repo    = Mockery::mock({ModuleName}RepositoryInterface::class);

        $repo->expects('create')
             ->with($command)
             ->andReturn(new {ModuleName}(['name' => 'Test']));

        $service = new {ModuleName}CreateService($repo);
        $result  = $service->execute($command);

        $this->assertInstanceOf({ModuleName}::class, $result);
    }
}
```

### Feature Test Template

```php
<?php

namespace Tests\Feature\Modules\{ModuleName};

use App\Modules\{ModuleName}\Models\{ModuleName};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {ModuleName}CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create(): void
    {
        $this->actingAs($this->createUser());

        $response = $this->post('/{module-name}s', [
            'name' => 'Test',
        ]);

        $response->assertRedirect('/{module-name}s');
        $this->assertDatabaseHas('{module_names}', ['name' => 'Test']);
    }
}
```

```php
<?php

namespace Tests\Feature\Modules\{ModuleName};

use App\Modules\{ModuleName}\Models\{ModuleName};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {ModuleName}UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_status(): void
    {
        $this->actingAs($this->createUser());

        $model = {ModuleName}::factory()->create(['status' => 'active']);

        $response = $this->put("/{module-name}s/{$model->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertRedirect("/{module-name}s/{$model->id}");
        $this->assertDatabaseHas('{module_names}', ['id' => $model->id, 'status' => 'inactive']);
    }
}
```

---

## Mandatory Rules

1. **Never** put business logic in the Controller.
2. **Never** use Eloquent directly in the Service — always go through the Repository.
3. **Always** inject the Repository Interface into the Service, never the concrete implementation.
4. **Always** one Service per action: `CreateService`, `UpdateService`, `UpdateStatusService`, `FindService`, `SearchService`.
5. **Always** type Command properties as `readonly`.
6. **Never** physically delete records — use `UpdateStatusService` to deactivate. Activate `no-delete-policy` skill.
7. Each module has its own `ServiceProvider` with its own bindings and its own routes.
8. Unit tests mock the Repository — they never touch the database.
9. Feature tests use `RefreshDatabase` and test the full HTTP flow.
10. IDs are UUIDs — always validate with the UUID regex in routes.
11. **Always** add the new module's URL to `database/seeders/MenuSeeder.php`, the
    single source of truth for the sidebar. A module without this entry has no
    sidebar entry and is unreachable. See *Registering the Module → Sidebar menu*.
