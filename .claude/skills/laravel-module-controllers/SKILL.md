---
name: laravel-module-controllers
description: "Guide for creating Controllers in the modular Laravel architecture. Activates when creating or modifying GetController, PostController, PutController, or UpdateStatusController inside any module's Controllers/ folder."
license: MIT
metadata:
  author: project
---

# Laravel Module — Controllers

Controllers are thin. They **never contain business logic**. Their only job is to receive the HTTP request, delegate to a Service, and return a response.

## Location

```
app/Modules/{ModuleName}/Controllers/
├── {ModuleName}GetController.php          — index, create, show, edit
├── {ModuleName}PostController.php         — store (invokable)
├── {ModuleName}PutController.php          — update (invokable)
└── {ModuleName}UpdateStatusController.php — updateStatus (invokable)
```

## Rules

1. **Never** put business logic in a controller.
2. Always extend `App\Http\Controllers\Controller`.
3. `PostController`, `PutController`, and `UpdateStatusController` are **invokable** (`__invoke`).
4. Inject Services via constructor (never inject the Repository directly).
5. Always build the Command inside the controller before passing it to the Service.
6. Use `Inertia::render()` for page responses.
7. Use `redirect()->route()` for redirects after mutations.
8. **Any controller that modifies the database** (`PostController`, `PutController`, `UpdateStatusController`) **must wrap its service call in `DB::transaction()` and a `try/catch`**. On exception, redirect back with an error message.
9. **Every module is mounted under the `{company}` route prefix.** Any controller method that receives a route id **must declare `string $company` as the first scalar parameter, before `string $id`** (see the warning below). This is mandatory and easy to get wrong.
10. **Every action must be permission-protected.** Each `GetController` method guards its view with `abort_unless($request->user()?->hasPermission('{module}.{action}') ?? false, 403)`. Each mutation Request authorizes the same way in `authorize()` (see the `laravel-module-requests` skill). See the warning below.

---

## ⚠️ CRITICAL: The `{company}` prefix and route parameter order

All module routes live under a `{company}` prefix:

```php
Route::middleware(['web', 'auth', 'verified', 'company.access'])
    ->prefix('{company}')
    ->where(['company' => $uuid])
    ->group(function () use ($uuid) {
        Route::prefix('{module-name}s')->group(function () use ($uuid) {
            Route::get('/{id}/edit', [{ModuleName}GetController::class, 'edit'])->where('id', $uuid)->name('{module-name}s.edit');
            Route::put('/{id}', {ModuleName}PutController::class)->where('id', $uuid)->name('{module-name}s.update');
        });
    });
```

This means **every** request carries **two** route parameters in this order: `company` first, then `id`.

**Laravel injects scalar (`string`) route parameters into controller methods by POSITION, not by name** (`ControllerDispatcher::dispatch` calls `$controller->{$method}(...array_values($parameters))`). Route–model binding matches by name; plain strings do **not**.

So if you write `edit(string $id)`, `$id` receives the **first** positional value — the **company UUID**, not the id you wanted. This is a silent, hard-to-spot bug.

✅ **Correct — declare `$company` first so `$id` lands correctly:**

```php
public function edit(string $company, string $id): Response { /* ... */ }

public function __invoke(Update{ModuleName}Request $request, string $company, string $id): RedirectResponse { /* ... */ }
```

❌ **Wrong — `$id` will hold the company UUID:**

```php
public function edit(string $id): Response { /* ... */ }
```

Rules of thumb:
- Class-typed params (`Request`, route-model-bound models) are resolved first; declare them before the scalars (e.g. `__invoke($request, $company, $id)`).
- In redirects, always pass both: `redirect()->route('{module-name}s.show', ['company' => $company, 'id' => $id])`.
- `PostController` / `index` / `create` have no `{id}`, so they don't need `$id`; build redirects with the company from `$request->route('company')`.
- For **company-scoped listings**, declare `index(Request $request, string $company)` and pass `companyId: $company` into the `Search{ModuleName}Command` — the Repository scopes from the Command, never from `session()`. Same for helpers that need scoping (e.g. `getActive(?string $companyId = null)`): thread `$company` in. See the `laravel-module-repositories` and `laravel-module-commands` skills.
- **Always add a feature test asserting the bound id is the entity's id, not the company's** (see the `pest-testing` skill).

---

## ⚠️ CRITICAL: Permission checks (every action)

Access is controlled by permission **action strings** of the form `{module}.{action}`, where `{module}` is the plural, lowercase module slug used in routes (`users`, `roles`, `companies`). The user's role is resolved per company and checked with `App\Modules\User\Models\User::hasPermission()`. A role with `permission_type = 'all'` (e.g. `Administrador`) passes every check automatically.

**Each route must enforce exactly one permission**, mapped as follows:

| Route / method | HTTP | Permission action |
|---|---|---|
| `index` (listar) | GET | `{module}.list` |
| `create` (vista crear) | GET | `{module}.create` |
| `store` (guardar) | POST | `{module}.create` |
| `show` (ver) | GET | `{module}.show` |
| `edit` (vista editar) | GET | `{module}.update` |
| `update` (actualizar) | PUT | `{module}.update` |
| `updateStatus` (cambiar estado) | PUT | `{module}.update-status` |

- **GET views** are guarded in the controller, as the **first statement** of each method (before any service call):

  ```php
  abort_unless($request->user()?->hasPermission('{module}.list') ?? false, 403);
  ```

  Methods without a `Request` parameter (`show`, `edit`) use the `request()` helper:

  ```php
  abort_unless(request()->user()?->hasPermission('{module}.show') ?? false, 403);
  ```

- **Mutations** (`store`, `update`, `updateStatus`) are guarded in their Form Request's `authorize()` — never re-check them in the controller (see the `laravel-module-requests` skill).

- **Do NOT build a friendly response yourself.** `abort(403)` is correct: a global handler in `bootstrap/app.php` converts any 403 on an Inertia request into a redirect-back with a flash `error`, which the frontend `Toaster` shows as a toast. Plain `abort_unless(..., 403)` is all a controller needs.

- **Seed the permission rows.** A new module's actions (`{module}.list`, `.create`, `.show`, `.update`, `.update-status`) and its modules row must be inserted alongside the module (see `database/sql/seed_initial_modules.sql`), and the sidebar menu entry uses `{module}.list` as its `permission`. Without the seeded rows, only `permission_type = 'all'` roles can reach the module.

- **Always add feature tests** asserting that a user lacking the permission gets `403` on each GET view and each mutation, and that granting it allows access (see the `pest-testing` skill).

---

## {ModuleName}GetController

Handles all GET requests: list, create form, show, edit form. Every method begins with its permission check.

```php
<?php

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\Search{ModuleName}Command;
use App\Modules\{ModuleName}\Services\{ModuleName}FindService;
use App\Modules\{ModuleName}\Services\{ModuleName}SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class {ModuleName}GetController extends Controller
{
    public function __construct(
        private readonly {ModuleName}SearchService $searchService,
        private readonly {ModuleName}FindService $findService,
    ) {}

    public function index(Request $request, string $company): Response
    {
        abort_unless($request->user()?->hasPermission('{module}.list') ?? false, 403);

        $command = new Search{ModuleName}Command(
            filters: $request->only(['search', 'status']),
            limit: $request->integer('limit', 20),
            offset: $request->integer('offset', 0),
            companyId: $company,
        );

        $result = $this->searchService->execute($command);

        return Inertia::render('{ModuleName}s/index', [
            'items'   => $result['data'],
            'total'   => $result['total'],
            'filters' => $request->only(['search', 'status', 'limit', 'offset']),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('{module}.create') ?? false, 403);

        return Inertia::render('{ModuleName}s/create');
    }

    public function show(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('{module}.show') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('{ModuleName}s/show', [
            'item' => $model,
        ]);
    }

    public function edit(string $company, string $id): Response
    {
        abort_unless(request()->user()?->hasPermission('{module}.update') ?? false, 403);

        $model = $this->findService->execute($id);

        return Inertia::render('{ModuleName}s/edit', [
            'item' => $model,
        ]);
    }
}
```

> `$company` is the first route parameter (from the `{company}` prefix) and **must** be declared before `$id`, even when unused. See the warning above.
>
> The `create` view is guarded by `{module}.create` (same permission as `store`); the `edit` view is guarded by `{module}.update` (same permission as `update`). There are no separate `.edit` / `.view` permissions.

---

## {ModuleName}PostController (Crear — invokable)

```php
<?php

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\Create{ModuleName}Command;
use App\Modules\{ModuleName}\Requests\Create{ModuleName}Request;
use App\Modules\{ModuleName}\Services\{ModuleName}CreateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class {ModuleName}PostController extends Controller
{
    public function __construct(
        private readonly {ModuleName}CreateService $createService,
    ) {}

    public function __invoke(Create{ModuleName}Request $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request): void {
                $this->createService->execute(
                    Create{ModuleName}Command::fromRequest($request)
                );
            });
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('{module-name}s.index', ['company' => $request->route('company')]);
    }
}
```

---

## {ModuleName}PutController (Actualizar — invokable)

```php
<?php

namespace App\Modules\{ModuleName}\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\{ModuleName}\Commands\Update{ModuleName}Command;
use App\Modules\{ModuleName}\Requests\Update{ModuleName}Request;
use App\Modules\{ModuleName}\Services\{ModuleName}UpdateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class {ModuleName}PutController extends Controller
{
    public function __construct(
        private readonly {ModuleName}UpdateService $updateService,
    ) {}

    public function __invoke(Update{ModuleName}Request $request, string $company, string $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $id): void {
                $this->updateService->execute(
                    $id,
                    Update{ModuleName}Command::fromRequest($request)
                );
            });
        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('{module-name}s.show', ['company' => $company, 'id' => $id]);
    }
}
```

---

## {ModuleName}UpdateStatusController (Actualizar Estado — invokable)

The permission check is handled by `UpdateStatus{ModuleName}Request::authorize()` — **do not** add `abort_unless` in the controller body.

After a successful status change, redirect to the **index** (not `show`) and include a `success` flash message. The frontend reads this flash and shows a toast confirmation.

```php
<?php

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

        return redirect()->route('{module-name}s.index', ['company' => $company])
            ->with('success', 'Estado del {module} actualizado correctamente.');
    }
}
```

> Note: `UpdateStatusController` does **not** wrap in `DB::transaction()` — the service call is a single atomic update. Only use `DB::transaction()` when multiple writes must be atomic (e.g. `PostController`, `PutController` that touch several tables).

---

## Inertia Page Naming Convention

The Inertia component path mirrors the module name in plural, lowercase kebab-case:

| Module | Inertia path |
|---|---|
| `Product` | `Products/index`, `Products/show`, etc. |
| `SaleOrder` | `SaleOrders/index`, `SaleOrders/show`, etc. |