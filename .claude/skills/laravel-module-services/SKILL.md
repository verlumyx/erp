---
name: laravel-module-services
description: "Guide for creating Service classes in the modular Laravel architecture. Activates when creating or modifying CreateService, UpdateService, UpdateStatusService, FindService, or SearchService inside any module's Services/ folder."
license: MIT
metadata:
  author: project
---

# Laravel Module — Services

Services contain **all business logic**. One Service per use case. They receive a Command and delegate persistence to the Repository.

## Location

```
app/Modules/{ModuleName}/Services/
├── {ModuleName}CreateService.php
├── {ModuleName}UpdateService.php
├── {ModuleName}UpdateStatusService.php
├── {ModuleName}FindService.php
└── {ModuleName}SearchService.php
```

## Rules

1. **One Service per action** — never combine use cases in a single service.
2. Always inject the **Repository Interface**, never the concrete Repository.
3. The only public method is `execute()`.
4. Throw domain Exceptions (e.g., `{ModuleName}NotFoundException`) when a record is not found.
5. Never use Eloquent directly inside a Service.
6. Never access HTTP request data — only receive Commands./usage

---

## {ModuleName}CreateService

`create()` returns `void` — after persisting, fetch the record with `findOrFail($command->id)`.

```php
<?php

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

---

## {ModuleName}UpdateService

```php
<?php

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

---

## {ModuleName}UpdateStatusService

```php
<?php

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

---

## {ModuleName}FindService

```php
<?php

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

---

## {ModuleName}SearchService

```php
<?php

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

## Adding Business Logic

Additional business logic goes inside `execute()`, between receiving the command and calling the repository:

```php
public function execute(Create{ModuleName}Command $command): {ModuleName}
{
    // ✅ Business rules here
    if ($this->repository->existsByName($command->name)) {
        throw new {ModuleName}AlreadyExistsException();
    }

    return $this->repository->create($command);
}
```
