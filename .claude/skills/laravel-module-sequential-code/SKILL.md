---
name: laravel-module-sequential-code
description: "Guide for adding an auto-generated, human-readable sequential code (e.g. CLI000001) to a module. The code has a module prefix + zero-padded counter, is generated on the backend at creation time, and is unique PER COMPANY (each company has its own sequence). Activates when adding a code/folio/correlative field, a per-company sequential identifier, or a prefixed counter to any module."
license: MIT
metadata:
  author: project
---

# Laravel Module — Sequential Code (per company)

Some entities need a short, human-readable identifier alongside their UUID — e.g. `CLI000001`, `CLI000002`. This skill describes how to add an **auto-generated, per-company sequential `code`** to a module.

Reference implementation: the **Client** module (`app/Modules/Client`), prefix `CLI`.

## Rules (read first)

1. **Format:** `{PREFIX}` + the counter zero-padded to **6 digits** → `CLI000001`. The prefix is a 3-letter module abbreviation defined as a model constant.
2. **Server-generated.** The `code` is NEVER sent by the client and NEVER validated in the Request. It is created in the Repository. (This is the one exception to the rule "the client always sends the id".)
3. **Unique per company, not global.** Two companies each have their own `CLI000001`. The sequence is scoped by `company_id`. Uniqueness is enforced with a **composite** unique index `(company_id, code)`.
4. **Atomic generation.** Generate inside a `DB::transaction` with `lockForUpdate()` so concurrent creates don't collide.
5. The module must already be company-scoped (`company_id` column + `companyId` flowing Controller → Command → Repository). If it isn't, add that first — see `laravel-module-repositories` (Company Scoping section).

---

## 1. Migration

Add the column nullable, **backfill existing rows**, then add the composite unique. If the table also has a globally-unique column that should now be per-company (e.g. `email`), convert it in the same migration.

```php
public function up(): void
{
    Schema::table('{module_names}', function (Blueprint $table) {
        // company_id first if the module wasn't company-scoped yet
        $table->uuid('company_id')->nullable()->after('id');
        $table->string('code', 12)->nullable()->after('company_id');

        $table->foreign('company_id')->references('id')->on('app_companies')->nullOnDelete();
        $table->index('company_id');
    });

    $this->backfill();

    Schema::table('{module_names}', function (Blueprint $table) {
        $table->dropUnique(['email']);            // only if converting a global unique
        $table->unique(['company_id', 'email']);  // ditto
        $table->unique(['company_id', 'code']);
    });
}

public function down(): void
{
    Schema::table('{module_names}', function (Blueprint $table) {
        $table->dropUnique(['company_id', 'code']);
        $table->dropUnique(['company_id', 'email']); // if applicable
        $table->unique('email');                     // if applicable
        $table->dropForeign(['company_id']);
        $table->dropIndex(['company_id']);
        $table->dropColumn(['company_id', 'code']);
    });
}
```

Backfill: assign each existing row a company (derived from its creator), then number rows sequentially **within each company**, ordered deterministically:

```php
private function backfill(): void
{
    $fallbackCompanyId = DB::table('app_companies')->orderBy('created_at')->value('id');
    if ($fallbackCompanyId === null) {
        return; // fresh install, nothing to backfill
    }

    $byCreator = [];
    foreach (DB::table('{module_names}')->orderBy('created_at')->get(['id', 'created_by']) as $row) {
        $companyId = $this->resolveCompanyId($row->created_by, $byCreator, $fallbackCompanyId);
        DB::table('{module_names}')->where('id', $row->id)->update(['company_id' => $companyId]);
    }

    foreach (DB::table('{module_names}')->distinct()->pluck('company_id') as $companyId) {
        $sequence = 0;
        $rows = DB::table('{module_names}')->where('company_id', $companyId)
            ->orderBy('created_at')->orderBy('id')->get(['id']);
        foreach ($rows as $row) {
            $code = '{PREFIX}'.str_pad((string) ++$sequence, 6, '0', STR_PAD_LEFT);
            DB::table('{module_names}')->where('id', $row->id)->update(['code' => $code]);
        }
    }
}

/** @param array<string, string> $cache */
private function resolveCompanyId(?string $creatorId, array &$cache, string $fallback): string
{
    if ($creatorId === null) {
        return $fallback;
    }
    if (isset($cache[$creatorId])) {
        return $cache[$creatorId];
    }
    $companyId = DB::table('user_company')->where('user_id', $creatorId)
        ->orderByDesc('is_default')->orderBy('created_at')->value('company_id');

    return $cache[$creatorId] = $companyId ?? $fallback;
}
```

> The migration is the only place `DB::` is allowed; the rest of the app uses `Model::query()`.

---

## 2. Model

```php
class {ModuleName} extends Model
{
    public const CODE_PREFIX = '{PREFIX}';

    protected $fillable = [
        'id',
        'company_id',
        'code',
        // ...
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
```

---

## 3. Repository — generation

Generate the code atomically inside `create()`. Lexicographic order equals numeric order because the width is fixed (6 digits).

```php
use Illuminate\Support\Facades\DB;

public function create(Create{ModuleName}Command $command): void
{
    DB::transaction(function () use ($command): void {
        {ModuleName}::create([
            'id'         => $command->id,
            'company_id' => $command->companyId,
            'code'       => $this->generateNextCode($command->companyId),
            // ... other fields, 'status' => 'active'
        ]);
    });
}

private function generateNextCode(string $companyId): string
{
    $last = {ModuleName}::query()
        ->where('company_id', $companyId)
        ->where('code', 'like', {ModuleName}::CODE_PREFIX.'%')
        ->lockForUpdate()
        ->orderByDesc('code')
        ->value('code');

    $next = $last !== null
        ? ((int) substr($last, strlen({ModuleName}::CODE_PREFIX))) + 1
        : 1;

    return {ModuleName}::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
}
```

Make `code` searchable by adding a `code()` filter method to `{ModuleName}Filters`:

```php
public function code(string $value): Builder
{
    return $this->builder->where('code', 'like', "%{$value}%");
}
```

---

## 4. Command — carry companyId, NOT code

The `code` is not part of the command. The `companyId` is resolved in the controller from `session('current_company_id')` (set by the `company.access` middleware) and passed in:

```php
// Command
public function __construct(
    public readonly string $id,
    public readonly string $companyId,
    public readonly string $name,
    // ...
) {}

public static function fromRequest(Create{ModuleName}Request $request, ?string $companyId = null): self
{
    return new self(
        id: $request->string('id')->toString(),
        companyId: $companyId ?? $request->route('company'),
        // ...
    );
}
```

```php
// PostController
$this->createService->execute(
    Create{ModuleName}Command::fromRequest($request, session('current_company_id'))
);
```

---

## 5. Resource — expose it

```php
return [
    'id'         => $this->id,
    'company_id' => $this->company_id,
    'code'       => $this->code,
    // ...
];
```

---

## 6. Factory

The factory bypasses the repository, so it must produce a unique `code` itself. Use a static counter (any company-unique value works, since uniqueness is per company):

```php
private static int $sequence = 0;

public function definition(): array
{
    return [
        'company_id' => Company::factory(),
        'code'       => '{PREFIX}'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT),
        // ...
    ];
}
```

> If `Company::factory()` reports "CompanyFactory not found", the Company model needs a factory. Create `database/factories/CompanyFactory.php` (flat namespace) and add a `newFactory()` override to the model — see `database/factories/ClientFactory.php` / `CompanyFactory.php` for the pattern.

---

## 7. Frontend

- **Type** (`types/{Module}.ts`): add `code: string;` (and `company_id: string | null;`).
- **Filters interface**: add `code?: string;`.
- **List table**: add a "Código" column. Keep it `hidden lg:block` and adjust the `lg:grid-cols-[...]` template on BOTH the header and row. Render with `tabular-nums font-semibold`.
- Add a `code` filter input if the module exposes per-field filters.

---

## 8. Tests

```php
// Sequence starts at 1 and increments, per company
expect({Module}::find($first)->code)->toBe('{PREFIX}000001');
expect({Module}::find($second)->code)->toBe('{PREFIX}000002');

// Each company has an independent sequence
// (create one row in company A and one in company B → both are {PREFIX}000001)

// Can be searched by code
->get(route('{module}s.index', ['company' => $company->id, 'code' => '{PREFIX}000042']))
```

Factory rows used by feature tests must belong to the acting company: `{Module}::factory()->create(['company_id' => $company->id])` — otherwise per-company scoping hides them.

Unit service tests: `findById`/`findOrFail` now take a second `?string $companyId` arg — update Mockery expectations to `->with($id, null)`.

---

## Checklist

- [ ] Migration: `company_id` (if missing) + `code`, backfill, composite `unique(['company_id', 'code')`.
- [ ] Model: `CODE_PREFIX` const + `code`/`company_id` in `$fillable` + `company()` relation.
- [ ] Repository: `generateNextCode()` inside `DB::transaction` + `lockForUpdate`, scoped by company; `code` filter.
- [ ] Command carries `companyId`; controller passes `session('current_company_id')`. No `code` in Request.
- [ ] Resource exposes `code`.
- [ ] Factory generates a unique `code` (+ Company has a factory).
- [ ] Frontend type/filters/column.
- [ ] Tests: sequence, per-company isolation, search-by-code; factories scoped to the acting company.
