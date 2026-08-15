---
name: no-delete-policy
description: "No physical deletion policy. Activates when implementing delete functionality, creating deactivation use cases, or when a user asks to delete records. Records are NEVER physically deleted — always use soft delete or deactivation."
license: MIT
metadata:
  author: project
---

# No Delete Policy

## When to Apply

Activate this skill when:

- Someone asks to implement a delete button or delete endpoint
- Creating a Use Case that would remove a record
- Defining routes that would handle deletion
- Any mention of deleting, removing, or destroying a record

## Fundamental Rule

**Records are NEVER physically deleted from the system.**

Instead, use deactivation (status field) or soft delete (deleted_at timestamp).

## Why

1. **Referential Integrity**: Records may have relations; physical deletion creates orphaned data
2. **Audit Trail**: Maintain complete operation history for compliance
3. **Data Recovery**: Allow restoring accidentally deactivated records
4. **Security**: Prevent accidental or malicious loss of critical data

## Correct Alternatives

### Option 1: Status Field (preferred)
```php
// Migration
$table->string('status', 50)->default('active'); // active | inactive | archived

// Deactivation
$entity->deactivate(); // sets status = 'inactive' + updatedAt
$repository->save($entity);
```

### Option 2: Laravel Soft Delete
```php
// Migration
$table->softDeletes(); // adds deleted_at column

// Eloquent Model
use SoftDeletes;

// Usage
$model->delete(); // sets deleted_at, does NOT physically delete
```

## Do NOT Implement

```php
// ❌ NEVER
Route::delete('/[modules]/{id}', [Module]DeleteController::class);

// ❌ NEVER
class Delete[Module]UseCase { ... }

// ❌ NEVER
$this->repository->delete($id);
$model->forceDelete();
```

## DO Implement

```php
// ✅ Route
Route::patch('/[modules]/{id}/deactivate', [Module]DeactivateController::class);
Route::patch('/[modules]/{id}/activate', [Module]ActivateController::class);
```

```php
// ✅ Use Case
final class Deactivate[Module]UseCase
{
    public function __construct(private [Module]Repository $repository) {}

    public function __invoke(Deactivate[Module]Request $request): void
    {
        $entity = $this->repository->findById(new [Module]Id($request->id));
        if (!$entity) {
            throw new [Module]NotFoundException($request->id);
        }
        $entity->deactivate();
        $this->repository->save($entity);
    }
}
```

```php
// ✅ Domain Entity method
public function deactivate(): void
{
    $this->status = new [Module]Status('inactive');
    $this->updatedAt = new DateTimeImmutable();
}
```

```php
// ✅ Eloquent scopes
class [Module]EloquentModel extends Model
{
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeInactive($query) { return $query->where('status', 'inactive'); }
}
```

## Frontend

```tsx
// ❌ NEVER
<Button onClick={() => deleteModule(id)} variant="destructive">
    <Trash2 /> Eliminar
</Button>

// ✅ ALWAYS
<Button onClick={() => deactivateModule(id)} variant="outline">
    <Archive /> Desactivar
</Button>
```

## Tests

```php
// ✅ Test deactivation, not deletion
it('can deactivate a [module]', function () {
    $[module] = [Module]EloquentModel::factory()->create(['status' => 'active']);

    $useCase = app(Deactivate[Module]UseCase::class);
    ($useCase)(new Deactivate[Module]Request($[module]->id));

    expect($[module]->fresh()->status)->toBe('inactive');
});

it('does not show inactive [modules] in listing', function () {
    [Module]EloquentModel::factory()->create(['status' => 'active', 'name' => 'Active']);
    [Module]EloquentModel::factory()->create(['status' => 'inactive', 'name' => 'Inactive']);

    $response = $this->get(route('[modules].index'));

    $response->assertInertia(fn ($page) =>
        $page->where('[modules]', fn ($[modules]) => count($[modules]) === 1)
    );
});
```

## Exceptions (Physical Deletion IS Allowed)

Only when explicitly documented and approved:

1. **Session tokens** — temporary data with no historical value
2. **Test/seed data** — clearly marked as disposable
3. **Explicit business requirement** — documented with justification and approval

If implementing an exception:
```php
/**
 * EXCEPTION: Physical deletion allowed.
 * Justification: Session tokens — temporary, no historical value.
 * Approved by: [Name] - [Date]
 */
class SessionToken extends Model
{
    // Physical deletion permitted here
}
```
