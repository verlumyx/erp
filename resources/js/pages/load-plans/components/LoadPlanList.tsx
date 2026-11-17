import { Pagination } from '@/components/pagination';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    CheckCircle2,
    Edit,
    Eye,
    MoreHorizontal,
    Plus,
    Printer,
    Search,
    Truck,
    XCircle,
} from 'lucide-react';
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
import loadPlans from '@/routes/load-plans';
import {
    STATUS_LABELS,
    STATUS_PILL_KIND,
    type LoadPlan,
    type LoadPlanFilters,
    type LoadPlanMeta,
    type LoadPlanOptions,
    type LoadPlanStatus,
} from '../types/LoadPlan';

interface LoadPlanListProps {
    loadPlans: LoadPlan[];
    meta: LoadPlanMeta;
    filters: LoadPlanFilters;
    options: LoadPlanOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const ALL = 'todos';

export function LoadPlanList({
    loadPlans: items,
    meta,
    filters: initialFilters,
    options,
}: LoadPlanListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<LoadPlanFilters>(initialFilters);

    const statusOptions: OptionType[] = [
        { value: ALL, label: 'Todos los estados' },
        ...Object.entries(STATUS_LABELS).map(([value, label]) => ({
            value,
            label,
        })),
    ];

    const routeOptions: OptionType[] = [
        { value: ALL, label: 'Todas las rutas' },
        ...options.routes.map((r) => ({
            value: r.id,
            label: r.name,
        })),
    ];

    const vehicleOptions: OptionType[] = [
        { value: ALL, label: 'Todos los camiones' },
        ...options.vehicles.map((v) => ({
            value: v.id,
            label: `${v.plate} — ${v.name}`,
        })),
    ];

    const applyFilters = (newFilters: LoadPlanFilters) => {
        const cleaned: Record<string, string | number> = {};

        Object.entries(newFilters).forEach(([key, val]) => {
            if (val !== undefined && val !== null && val !== '' && val !== ALL) {
                cleaned[key] = val;
            }
        });

        router.get(loadPlans.index(companyId).url, cleaned, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearFilters = () => {
        setFilters({});
        router.get(loadPlans.index(companyId).url);
    };

    const handleUpdateStatus = (plan: LoadPlan, status: LoadPlanStatus) => {
        router.put(
            loadPlans.updateStatus({ company: companyId, id: plan.id }).url,
            { status },
            { preserveScroll: true },
        );
    };

    const pickFilter = (
        list: OptionType[],
        value: string | undefined,
        apply: (next: string | undefined) => void,
    ) => ({
        options: list,
        value: list.find((option) => option.value === (value ?? ALL)) ?? null,
        onChange: (option: OptionType | null) =>
            apply(!option || option.value === ALL ? undefined : option.value),
    });

    return (
        <div className="flex flex-col gap-5">
            {/* Header */}
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Planes de Carga
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} plan{meta.total !== 1 ? 'es' : ''} de carga
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() =>
                        router.visit(loadPlans.create(companyId).url)
                    }
                >
                    <Plus />
                    Nuevo plan de carga
                </Button>
            </div>

            {/* Filters */}
            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="filter-q">Buscar por código, chofer o ayudante</Label>
                        <Input
                            id="filter-q"
                            type="text"
                            placeholder="PLC000001, Juan Perez..."
                            value={filters.q ?? ''}
                            onChange={(e) =>
                                setFilters({ ...filters, q: e.target.value })
                            }
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    applyFilters({ ...filters, offset: 0 });
                                }
                            }}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-route">Ruta</Label>
                        <Select2
                            inputId="filter-route"
                            {...pickFilter(
                                routeOptions,
                                filters.route_id,
                                (route_id) => {
                                    const next = {
                                        ...filters,
                                        route_id,
                                        offset: 0,
                                    };
                                    setFilters(next);
                                    applyFilters(next);
                                },
                            )}
                            placeholder="Todas las rutas"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-vehicle">Camión</Label>
                        <Select2
                            inputId="filter-vehicle"
                            {...pickFilter(
                                vehicleOptions,
                                filters.vehicle_id,
                                (vehicle_id) => {
                                    const next = {
                                        ...filters,
                                        vehicle_id,
                                        offset: 0,
                                    };
                                    setFilters(next);
                                    applyFilters(next);
                                },
                            )}
                            placeholder="Todos los camiones"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select2
                            inputId="filter-status"
                            {...pickFilter(
                                statusOptions,
                                filters.status,
                                (status) => {
                                    const next = {
                                        ...filters,
                                        status: status as LoadPlanStatus | undefined,
                                        offset: 0,
                                    };
                                    setFilters(next);
                                    applyFilters(next);
                                },
                            )}
                            placeholder="Todos los estados"
                        />
                    </div>
                </div>

                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        onClick={() => applyFilters({ ...filters, offset: 0 })}
                        variant="default"
                    >
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClearFilters} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            {/* List Table Card */}
            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[1.1fr_1.1fr_1.1fr_1.4fr_0.7fr_0.8fr_0.8fr_0.9fr_0.5fr]">
                    {[
                        'Plan / Fecha',
                        'Ruta',
                        'Camión',
                        'Tripulación',
                        'Despachos',
                        'Peso (kg)',
                        'Volumen (m³)',
                        'Estado',
                        'Acciones',
                    ].map((header, index) => (
                        <div
                            key={header}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                index === 4 || index === 7
                                    ? 'text-center'
                                    : index === 5 || index === 6 || index === 8
                                      ? 'text-right'
                                      : ''
                            }`}
                        >
                            {header}
                        </div>
                    ))}
                </div>

                <div className="flex flex-col">
                    {items.map((plan) => (
                        <div
                            key={plan.id}
                            className="grid min-h-[66px] grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted/50 lg:grid-cols-[1.1fr_1.1fr_1.1fr_1.4fr_0.7fr_0.8fr_0.8fr_0.9fr_0.5fr] lg:py-0"
                        >
                            {/* Code & Date */}
                            <div>
                                <Link
                                    href={
                                        loadPlans.show({
                                            company: companyId,
                                            id: plan.id,
                                        }).url
                                    }
                                    className="font-bold text-primary hover:underline tabular-nums"
                                >
                                    {plan.code}
                                </Link>
                                <div className="mt-0.5 flex items-center gap-1 text-[11.5px] text-muted-foreground">
                                    <Calendar className="size-3" />
                                    {plan.plan_date}
                                </div>
                            </div>

                            {/* Route */}
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-semibold text-foreground">
                                    {plan.route_name || '—'}
                                </span>
                                {plan.route_code && (
                                    <span className="truncate text-[11.5px] text-muted-foreground">
                                        {plan.route_code}
                                    </span>
                                )}
                            </div>

                            {/* Vehicle */}
                            <div className="hidden min-w-0 flex-col lg:flex">
                                {plan.vehicle_plate ? (
                                    <div>
                                        <span className="rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-bold uppercase">
                                            {plan.vehicle_plate}
                                        </span>
                                        <div className="mt-0.5 max-w-[140px] truncate text-[11.5px] text-muted-foreground">
                                            {plan.vehicle_name}
                                        </div>
                                    </div>
                                ) : (
                                    <span className="text-muted-foreground">—</span>
                                )}
                            </div>

                            {/* Driver & Helper */}
                            <div className="hidden min-w-0 flex-col lg:flex">
                                <span className="truncate text-[13px] font-medium">
                                    Chofer: {plan.driver_name}
                                </span>
                                {plan.helper_name && (
                                    <span className="truncate text-[11.5px] text-muted-foreground">
                                        Ayudante: {plan.helper_name}
                                    </span>
                                )}
                            </div>

                            {/* Dispatches Count */}
                            <div className="hidden text-center lg:block">
                                <span className="rounded-full bg-primary/10 px-2 py-0.5 font-bold tabular-nums text-primary">
                                    {plan.total_dispatches}
                                </span>
                            </div>

                            {/* Weight */}
                            <div className="hidden text-right text-[13px] font-semibold tabular-nums lg:block">
                                {Number(plan.total_weight).toLocaleString('es-VE', {
                                    maximumFractionDigits: 1,
                                })}
                            </div>

                            {/* Volume */}
                            <div className="hidden text-right text-[13px] tabular-nums text-muted-foreground lg:block">
                                {Number(plan.total_volume).toLocaleString('es-VE', {
                                    maximumFractionDigits: 2,
                                })}
                            </div>

                            {/* Status */}
                            <div className="hidden text-center lg:block">
                                <StatusPill kind={STATUS_PILL_KIND[plan.status]}>
                                    {STATUS_LABELS[plan.status] ?? plan.status}
                                </StatusPill>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end gap-2">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 rounded-md text-muted-foreground hover:text-foreground"
                                            aria-label="Opciones"
                                        >
                                            <MoreHorizontal className="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem asChild>
                                            <Link
                                                href={
                                                    loadPlans.show({
                                                        company: companyId,
                                                        id: plan.id,
                                                    }).url
                                                }
                                                className="flex items-center gap-2 text-xs"
                                            >
                                                <Eye className="size-3.5 text-muted-foreground" />
                                                Ver detalle
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem asChild>
                                            <a
                                                href={`/${companyId}/load-plans/${plan.id}/pdf`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex items-center gap-2 text-xs"
                                            >
                                                <Printer className="size-3.5 text-muted-foreground" />
                                                Imprimir Hoja de Carga (PDF)
                                            </a>
                                        </DropdownMenuItem>

                                        {plan.is_editable && (
                                            <DropdownMenuItem asChild>
                                                <Link
                                                    href={
                                                        loadPlans.edit({
                                                            company: companyId,
                                                            id: plan.id,
                                                        }).url
                                                    }
                                                    className="flex items-center gap-2 text-xs"
                                                >
                                                    <Edit className="size-3.5 text-muted-foreground" />
                                                    Editar plan
                                                </Link>
                                            </DropdownMenuItem>
                                        )}

                                        <DropdownMenuSeparator />

                                        {plan.status === 'draft' && (
                                            <DropdownMenuItem
                                                onClick={() => handleUpdateStatus(plan, 'in_transit')}
                                                className="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400"
                                            >
                                                <Truck className="size-3.5" />
                                                Despachar a tránsito
                                            </DropdownMenuItem>
                                        )}

                                        {plan.status === 'in_transit' && (
                                            <DropdownMenuItem
                                                onClick={() =>
                                                    router.visit(
                                                        loadPlans.show({
                                                            company: companyId,
                                                            id: plan.id,
                                                        }).url + '?confirm=1',
                                                    )
                                                }
                                                className="flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400"
                                            >
                                                <CheckCircle2 className="size-3.5" />
                                                Confirmar entrega
                                            </DropdownMenuItem>
                                        )}

                                        {(plan.status === 'draft' || plan.status === 'in_transit') && (
                                            <DropdownMenuItem
                                                onClick={() => handleUpdateStatus(plan, 'cancelled')}
                                                className="flex items-center gap-2 text-xs text-bad"
                                            >
                                                <XCircle className="size-3.5" />
                                                Anular plan
                                            </DropdownMenuItem>
                                        )}
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))}
                    {items.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>

                <Pagination
                    meta={meta}
                    onPageChange={(offset) =>
                        applyFilters({ ...filters, offset })
                    }
                    label="plan de carga"
                    labelPlural="planes de carga"
                />
            </Card>
        </div>
    );
}
