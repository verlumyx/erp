---
name: laravel-module-exceptions
description: "Guide for creating Exception classes in the modular Laravel architecture. Activates when creating or modifying Exception classes inside any module's Exceptions/ folder."
license: MIT
metadata:
  author: project
---

# Laravel Module — Exceptions

Custom Exceptions represent domain-specific error conditions. They are thrown by Services and caught by Laravel's exception handler or by the caller.

## Location

```
app/Modules/{ModuleName}/Exceptions/
└── {ModuleName}NotFoundException.php
```

## Rules

1. Always extend `Exception` (or a more specific base like `RuntimeException`).
2. Set a clear default `$message` in Spanish or English — consistent with the rest of the project.
3. Do not add logic to exceptions — they are signal objects only.
4. Name them after what went wrong: `NotFoundException`, `AlreadyExistsException`, `InvalidStatusException`, etc.

---

## {ModuleName}NotFoundException

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

## Common Exception Types

Add only the exceptions that the module actually needs:

```php
// Record not found
class {ModuleName}NotFoundException extends Exception
{
    protected $message = '{ModuleName} not found.';
}

// Duplicate record
class {ModuleName}AlreadyExistsException extends Exception
{
    protected $message = '{ModuleName} already exists.';
}

// Invalid state transition
class {ModuleName}InvalidStatusException extends Exception
{
    protected $message = 'Invalid status transition for {ModuleName}.';
}
```

---

## Where Exceptions Are Thrown

Exceptions are thrown in Services, not in Controllers or Repositories:

```php
// ✅ In Service
public function execute(string $id): {ModuleName}
{
    $model = $this->repository->findById($id);

    if ($model === null) {
        throw new {ModuleName}NotFoundException();
    }

    return $model;
}
```

---

## Global Exception Handling

Register the exception handler in `bootstrap/app.php` to return appropriate HTTP responses:

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function ({ModuleName}NotFoundException $e, Request $request) {
        if ($request->wantsJson()) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        abort(404, $e->getMessage());
    });
})
```
