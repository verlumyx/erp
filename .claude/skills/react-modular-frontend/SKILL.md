---
name: hexagonal-frontend
description: "Frontend rules for Hexagonal Architecture with React/TypeScript/Inertia.js. Activates when creating frontend pages, components, hooks, Context API providers, or adding permissions to permissions-tree.tsx for a new module."
license: MIT
metadata:
  author: project
---

# Hexagonal Frontend (React + TypeScript + Inertia.js)

## When to Apply

Activate this skill when:

- Creating a new frontend module (pages, components, hooks)
- Adding permissions for a new module
- Working with the Context API form pattern
- Creating custom hooks for list/form/actions

## Critical: Add Permissions When Creating a New Module

**MANDATORY**: Add 4 permissions to `resources/js/pages/roles/components/permissions-tree.tsx`:

```typescript
// In the MODULES array:
{
    id: '[modules]',
    name: '[modules]',
    label: '[Modules Label]',
    permissions: [
        { id: '[modules].list', name: 'list', label: 'Listar' },
        { id: '[modules].create', name: 'create', label: 'Crear' },
        { id: '[modules].view', name: 'view', label: 'Ver' },
        { id: '[modules].edit', name: 'edit', label: 'Editar' },
        { id: '[modules].update-status', name: 'edit', label: 'Cambiar estado' },
    ],
},
```

Also add to menu in `InMemoryMenuRepository.php`.

## Module Directory Structure

```
resources/js/pages/[modules]/
├── index.tsx               # List page
├── show.tsx                # Detail page
├── create.tsx              # Create page (has Provider)
├── edit.tsx                # Edit page (has Provider)
├── components/
│   ├── [Module]Form.tsx    # Shared form (uses Context)
│   ├── [Module]Table.tsx   # Table with filters
│   └── [Module]Card.tsx    # Detail card
├── contexts/
│   └── [Module]FormContext.tsx  # Context + Provider + Hook
├── hooks/
│   ├── use[Module]Form.ts   # Form logic with Inertia useForm
│   ├── use[Module]List.ts   # Filters and search
│   └── use[Module]Actions.ts # Navigation actions
├── repositories/
│   └── [module]Repository.ts
└── types/
    └── [Module].ts
```

## Index Page Template

```tsx
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { [Module], [Module]Meta, [Module]Filters } from './types/[Module]';
import { [Module]List } from './components/[Module]List';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface Props {
    [modules]: [Module][];
    meta: [Module]Meta;
    filters: [Module]Filters;
}

export default function [Module]Index({ [modules]: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const handleSearch = (newFilters: [Module]Filters) => {
        router.get(route('[modules].index', { company: companyId }), newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="[Modules]" />
            <[Module]List [modules]={items} meta={meta} filters={filters} onSearch={handleSearch} />
        </>
    );
}
```

## List Component Template

El componente de lista es el corazón del módulo. Reemplaza botones de iconos individuales con un `DropdownMenu` por fila, e incluye la acción de cambio de estado directamente.

```tsx
// components/[Module]List.tsx
import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Eye, Edit, Plus, Search, ChevronDown, Power } from 'lucide-react';
import { [Module], [Module]Filters, [Module]Meta } from '../types/[Module]';
import [modules] from '@/routes/[modules]';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface [Module]ListProps {
    [modules]: [Module][];
    meta: [Module]Meta;
    filters: [Module]Filters;
    onSearch: (filters: [Module]Filters) => void;
}

export function [Module]List({ [modules]: items, meta, filters: initialFilters, onSearch }: [Module]ListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<[Module]Filters>(initialFilters);

    const handleSearch = () => onSearch(filters);

    const handleClear = () => {
        setFilters({});
        router.get([modules].index(companyId).url);
    };

    // Toggle activo/inactivo — usa router.put con Wayfinder, nunca fetch/axios
    const handleToggleStatus = ([module]: [Module]) => {
        router.put([modules].updateStatus({ company: companyId, id: [module].id }).url, {
            status: [module].status === 'active' ? 'inactive' : 'active',
        });
    };

    const statusBadge = (status: string) =>
        status === 'active' ? (
            <Badge className="bg-green-100 text-green-800 hover:bg-green-100">Activo</Badge>
        ) : (
            <Badge variant="secondary" className="bg-red-100 text-red-800 hover:bg-red-100">Inactivo</Badge>
        );

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold">Gestión de [Modules]</h1>
                    <p className="text-muted-foreground">Gestiona los [modules] del sistema</p>
                </div>
                {/* Usar router.visit, NO <Link> ni <a href> */}
                <Button onClick={() => router.visit([modules].create(companyId).url)}>
                    <Plus className="w-4 h-4 mr-2" />
                    Nuevo [Module]
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                {/* filtros aquí */}
                <div className="flex justify-end gap-2 mt-4">
                    <Button onClick={handleSearch}><Search className="w-4 h-4 mr-2" />Buscar</Button>
                    <Button variant="outline" onClick={handleClear}>Limpiar</Button>
                </div>
            </div>

            <Card>
                <CardContent className="p-0">
                    {items.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-muted-foreground mb-4">No se encontraron [modules].</p>
                            <Button onClick={() => router.visit([modules].create(companyId).url)}>
                                <Plus className="w-4 h-4 mr-2" />
                                Crear Primer [Module]
                            </Button>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Creado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.map(([module]) => (
                                    <TableRow key={[module].id}>
                                        <TableCell>{[module].name}</TableCell>
                                        <TableCell>{statusBadge([module].status)}</TableCell>
                                        <TableCell>{[module].created_at}</TableCell>
                                        <TableCell className="text-right">
                                            {/* PATRÓN OBLIGATORIO: DropdownMenu, nunca botones individuales */}
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="outline" size="sm">
                                                        Opciones <ChevronDown className="ml-1 h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            router.visit(
                                                                [modules].show({ company: companyId, id: [module].id }).url,
                                                            )
                                                        }
                                                    >
                                                        <Eye className="mr-2 h-4 w-4" />
                                                        Ver
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            router.visit(
                                                                [modules].edit({ company: companyId, id: [module].id }).url,
                                                            )
                                                        }
                                                    >
                                                        <Edit className="mr-2 h-4 w-4" />
                                                        Editar
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem onClick={() => handleToggleStatus([module])}>
                                                        <Power className="mr-2 h-4 w-4" />
                                                        {[module].status === 'active' ? 'Inactivar' : 'Activar'}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
```

### Reglas del componente de lista

- **NUNCA usar `<Link>` de Inertia** en tablas/listas — usar siempre `router.visit()`.
- **NUNCA usar botones de iconos individuales** (Eye, Edit por separado) — agrupar siempre en `DropdownMenu`.
- La estructura del dropdown es fija: Ver → Editar → `<DropdownMenuSeparator />` → Activar/Inactivar.
- `handleToggleStatus` invierte el estado actual y llama `router.put()` con Wayfinder.
- Si el registro no puede editarse (ej. rol `Administrador`), envolver Editar e Inactivar en `{condition && (<>...</>)}`.

## Create Page Template

```tsx
import { Head } from '@inertiajs/react';
import { [Module]Form } from './components/[Module]Form';
import { [Module]FormProvider } from './contexts/[Module]FormContext';
import { use[Module]Form } from './hooks/use[Module]Form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function [Module]Create() {
    const formMethods = use[Module]Form({ mode: 'create' });

    return (
        <>
            <Head title="Crear [Module]" />
            <div className="space-y-6">
                <h1 className="text-3xl font-bold tracking-tight">Crear [Module]</h1>
                <Card>
                    <CardHeader><CardTitle>Información del [Module]</CardTitle></CardHeader>
                    <CardContent>
                        <[Module]FormProvider value={formMethods}>
                            <[Module]Form />
                        </[Module]FormProvider>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
```

## Context API Template

```tsx
// contexts/[Module]FormContext.tsx
import { createContext, useContext } from 'react';
import { use[Module]Form } from '../hooks/use[Module]Form';

type [Module]FormContextType = ReturnType<typeof use[Module]Form>;

const [Module]FormContext = createContext<[Module]FormContextType | null>(null);

export function [Module]FormProvider({
    children,
    value
}: {
    children: React.ReactNode;
    value: [Module]FormContextType;
}) {
    return (
        <[Module]FormContext.Provider value={value}>
            {children}
        </[Module]FormContext.Provider>
    );
}

export function use[Module]FormContext(): [Module]FormContextType {
    const context = useContext([Module]FormContext);
    if (!context) {
        throw new Error('use[Module]FormContext must be used within [Module]FormProvider');
    }
    return context;
}
```

## Form Hook Template

```tsx
// hooks/use[Module]Form.ts
import { useForm } from '@inertiajs/react';

interface [Module]FormOptions {
    mode: 'create' | 'edit';
    [module]?: { id: string; name: string };
    onSuccess?: () => void;
}

export function use[Module]Form({ mode, [module], onSuccess }: [Module]FormOptions) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        id: [module]?.id ?? crypto.randomUUID(),  // UUID generated on client, required by Create[Module]Request
        name: [module]?.name ?? '',
        status: 'active',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (mode === 'create') {
            post(route('[modules].store'), { onSuccess: () => { reset(); onSuccess?.(); } });
        } else {
            put(route('[modules].update', [module]?.id), { onSuccess });
        }
    };

    return { data, setData, processing, errors, handleSubmit };
}
```

## Form Component Template (uses Context)

```tsx
// components/[Module]Form.tsx
import { use[Module]FormContext } from '../contexts/[Module]FormContext';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export function [Module]Form() {
    const { data, setData, processing, errors, handleSubmit } = use[Module]FormContext();

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <Label htmlFor="name">Nombre</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="Nombre del [module]"
                />
                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
            </div>

            <Button type="submit" disabled={processing}>
                {processing ? 'Guardando...' : 'Guardar'}
            </Button>
        </form>
    );
}
```

## Actions Hook Template

```tsx
// hooks/use[Module]Actions.ts
import { router } from '@inertiajs/react';

export function use[Module]Actions() {
    const goToIndex = () => router.visit(route('[modules].index'));
    const goToCreate = () => router.visit(route('[modules].create'));
    const goToShow = (id: string) => router.visit(route('[modules].show', id));
    const goToEdit = (id: string) => router.visit(route('[modules].edit', id));

    return { goToIndex, goToCreate, goToShow, goToEdit };
}
```

## List Hook Template

```tsx
// hooks/use[Module]List.ts
import { useState } from 'react';
import { router } from '@inertiajs/react';

export function use[Module]List(initialFilters: Record<string, string>) {
    const [filters, setFilters] = useState(initialFilters);

    const search = () => {
        router.get(route('[modules].index'), filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        setFilters({});
        router.get(route('[modules].index'));
    };

    return { filters, setFilters, search, resetFilters };
}
```

## Types Template

```tsx
// types/[Module].ts
export interface [Module] {
    id: string;
    name: string;
    status: 'active' | 'inactive';
    created_at: string;
    updated_at: string | null;
}

export interface [Module]Meta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface [Module]Filters {
    name?: string;
    status?: string;
    limit?: number;
    offset?: number;
}
```

## Rules

- Use Context API to share form state — avoid prop drilling
- Hook instantiated in the page, passed via Provider to child components
- Components consume context with `use[Module]FormContext()`
- Use `router.get()` with `preserveState: true` for filter navigation
- Use Inertia's `useForm()` for all form submissions
- Do NOT use `fetch()`, `axios`, or manual API calls — use Inertia
- All deactivation uses PATCH/PUT, never DELETE (see no-delete-policy skill)

### Navigation in tables/lists

- **NEVER use `<Link>` from Inertia** inside table rows — always use `router.visit()`.
- **NEVER use individual icon buttons** (Eye, Edit, etc.) per row — always use a `DropdownMenu`.

### Status toggle

- The `handleToggleStatus` function sends `router.put()` to the `updateStatus` Wayfinder route.
- The payload is `{ status: current === 'active' ? 'inactive' : 'active' }`.
- After the PUT, the controller redirects back to `*.index` with a `success` flash.
- Do NOT use optimistic UI updates — rely on the Inertia redirect to refresh the list.

### DropdownMenu structure (fixed order)

```
Ver
Editar
─────────── (DropdownMenuSeparator)
Activar / Inactivar
```

If a row should not be editable or have its status changed (e.g. the `Administrador` role), wrap those items in `{condition && (<>...</>)}`.
