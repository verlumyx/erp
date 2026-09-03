import { router, usePage } from '@inertiajs/react';
import { Eye, Link2, MoreHorizontal, Power, Search } from 'lucide-react';
import { useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import storeCustomers from '@/routes/store-customers';
import { useStorePermissions } from '../hooks/useStorePermissions';
import {
    CUSTOMER_STATUS_LABELS,
    CUSTOMER_STATUS_PILL,
    formatDocument,
    type ListMeta,
    type StoreCustomer,
    type StoreCustomerFilters,
} from '../types/Store';
import { StoreCustomerLinkDialog } from './StoreCustomerLinkDialog';

interface StoreCustomerListProps {
    storeCustomers: StoreCustomer[];
    meta: ListMeta;
    filters: StoreCustomerFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

const STATUS_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    ...Object.entries(CUSTOMER_STATUS_LABELS).map(([value, label]) => ({
        value,
        label,
    })),
];

const LINKED_OPTIONS: OptionType[] = [
    { value: ALL, label: 'Todos' },
    { value: 'yes', label: 'Vinculados' },
    { value: 'no', label: 'Sin vincular' },
];

export function StoreCustomerList({
    storeCustomers: rows,
    meta,
    filters: initialFilters,
}: StoreCustomerListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const [filters, setFilters] =
        useState<StoreCustomerFilters>(initialFilters);
    const [linking, setLinking] = useState<StoreCustomer | null>(null);

    const applyFilters = (next: StoreCustomerFilters) => {
        setFilters(next);
        router.get(
            storeCustomers.index(companyId).url,
            next as Record<string, string>,
            { preserveState: true, preserveScroll: true },
        );
    };

    const handleClear = () => {
        setFilters({});
        router.get(storeCustomers.index(companyId).url);
    };

    const toggleStatus = (row: StoreCustomer) => {
        router.put(
            storeCustomers.updateStatus({ company: companyId, id: row.id }).url,
            { status: row.status === 'inactive' ? 'active' : 'inactive' },
            { preserveScroll: true },
        );
    };

    const goTo = (row: StoreCustomer) =>
        router.visit(
            storeCustomers.show({ company: companyId, id: row.id }).url,
        );

    return (
        <div className="flex flex-col gap-5">
            <div>
                <h1 className="text-[27px] font-extrabold tracking-tight">
                    Compradores
                </h1>
                <p className="mt-1 text-[14.5px] text-muted-foreground">
                    {meta.total} cuenta{meta.total !== 1 ? 's' : ''} de la
                    tienda
                </p>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2 lg:col-span-2">
                        <Label htmlFor="filter-q">Buscar</Label>
                        <Input
                            id="filter-q"
                            placeholder="Correo, nombre, RIF o código…"
                            value={filters.q ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, q: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters(filters)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-linked">Vínculo</Label>
                        <Select2
                            inputId="filter-linked"
                            options={LINKED_OPTIONS}
                            value={
                                LINKED_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.linked ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    linked:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select2
                            inputId="filter-status"
                            options={STATUS_OPTIONS}
                            value={
                                STATUS_OPTIONS.find(
                                    (option) =>
                                        option.value ===
                                        (filters.status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        !option || option.value === ALL
                                            ? undefined
                                            : option.value,
                                })
                            }
                        />
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={() => applyFilters(filters)}>
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[2.2fr_1.5fr_1fr_1.6fr_1fr_1.2fr_0.7fr]">
                    {[
                        'Correo',
                        'Nombre',
                        'RIF',
                        'Cliente vinculado',
                        'Estado',
                        'Último acceso',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${index === 6 ? 'text-right' : ''}`}
                        >
                            {header}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {rows.map((row) => (
                        <div
                            key={row.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[2.2fr_1.5fr_1fr_1.6fr_1fr_1.2fr_0.7fr] lg:py-0"
                            onClick={() => goTo(row)}
                        >
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {row.email}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {row.code}
                                    {row.phone ? ` · ${row.phone}` : ''}
                                </span>
                            </div>
                            <div className="hidden truncate text-[13.5px] lg:block">
                                {row.name}
                            </div>
                            <div className="hidden text-[13.5px] tabular-nums lg:block">
                                {formatDocument(
                                    row.document_type,
                                    row.document_number,
                                )}
                            </div>
                            <div className="hidden truncate text-[13.5px] lg:block">
                                {row.client ? (
                                    <span className="font-semibold">
                                        {row.client.code} — {row.client.name}
                                    </span>
                                ) : (
                                    <span className="text-muted-foreground">
                                        Sin vincular
                                    </span>
                                )}
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={CUSTOMER_STATUS_PILL[row.status]}
                                >
                                    {CUSTOMER_STATUS_LABELS[row.status]}
                                </StatusPill>
                            </div>
                            <div className="hidden text-[13px] text-muted-foreground tabular-nums lg:block">
                                {row.last_login_at ?? 'Nunca'}
                            </div>
                            <div
                                className="flex items-center justify-end gap-2"
                                onClick={(e) => e.stopPropagation()}
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="outline"
                                            size="icon"
                                            className="rounded-[10px] bg-card"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() => goTo(row)}
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        {can('store-customers.link') &&
                                            !row.client_id && (
                                                <DropdownMenuItem
                                                    onClick={() =>
                                                        setLinking(row)
                                                    }
                                                >
                                                    <Link2 className="mr-2 h-4 w-4" />
                                                    Vincular
                                                </DropdownMenuItem>
                                            )}
                                        {can('store-customers.update-status') &&
                                            row.status !== 'invited' && (
                                                <>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        onClick={() =>
                                                            toggleStatus(row)
                                                        }
                                                    >
                                                        <Power className="mr-2 h-4 w-4" />
                                                        {row.status ===
                                                        'inactive'
                                                            ? 'Activar'
                                                            : 'Bloquear'}
                                                    </DropdownMenuItem>
                                                </>
                                            )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Todavía nadie se ha registrado en la tienda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {rows.length} de {meta.total} comprador
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>

            <StoreCustomerLinkDialog
                customer={linking}
                onClose={() => setLinking(null)}
            />
        </div>
    );
}
