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

    return (
        <div className="flex flex-col gap-5">
            {/* Header */}
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-extrabold tracking-tight">
                        Planes de Carga
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Consolidación de despachos y asignación de camiones por ruta comercial
                    </p>
                </div>
                <Link href={loadPlans.create(companyId).url}>
                    <Button className="h-10 rounded-[11px] px-4 font-semibold">
                        <Plus className="mr-1.5 size-4" />
                        Nuevo plan de carga
                    </Button>
                </Link>
            </div>

            {/* Filters */}
            <Card className="gap-3 rounded-2xl p-4">
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
                    {/* Search Q */}
                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <Label
                            htmlFor="filter-q"
                            className="text-[12px] font-semibold text-muted-foreground"
                        >
                            Buscar por código, chofer o ayudante
                        </Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="filter-q"
                                value={filters.q ?? ''}
                                onChange={(e) =>
                                    setFilters({ ...filters, q: e.target.value })
                                }
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        applyFilters({ ...filters, offset: 0 });
                                    }
                                }}
                                placeholder="PLC000001, Juan Perez..."
                                className="h-9 rounded-[9px] pl-9 text-xs"
                            />
                        </div>
                    </div>

                    {/* Route Filter */}
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[12px] font-semibold text-muted-foreground">
                            Ruta
                        </Label>
                        <Select2
                            inputId="filter-route"
                            options={routeOptions}
                            value={
                                routeOptions.find(
                                    (opt) =>
                                        opt.value === (filters.route_id ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) => {
                                const next = {
                                    ...filters,
                                    route_id: option?.value,
                                    offset: 0,
                                };
                                setFilters(next);
                                applyFilters(next);
                            }}
                            size="sm"
                        />
                    </div>

                    {/* Vehicle Filter */}
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[12px] font-semibold text-muted-foreground">
                            Camión
                        </Label>
                        <Select2
                            inputId="filter-vehicle"
                            options={vehicleOptions}
                            value={
                                vehicleOptions.find(
                                    (opt) =>
                                        opt.value === (filters.vehicle_id ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) => {
                                const next = {
                                    ...filters,
                                    vehicle_id: option?.value,
                                    offset: 0,
                                };
                                setFilters(next);
                                applyFilters(next);
                            }}
                            size="sm"
                        />
                    </div>

                    {/* Status Filter */}
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[12px] font-semibold text-muted-foreground">
                            Estado
                        </Label>
                        <Select2
                            inputId="filter-status"
                            options={statusOptions}
                            value={
                                statusOptions.find(
                                    (opt) =>
                                        opt.value === (filters.status ?? ALL),
                                ) ?? null
                            }
                            onChange={(option) => {
                                const next = {
                                    ...filters,
                                    status: option?.value,
                                    offset: 0,
                                };
                                setFilters(next);
                                applyFilters(next);
                            }}
                            size="sm"
                        />
                    </div>
                </div>

                <div className="flex justify-end gap-2 pt-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={handleClearFilters}
                        className="h-8 text-xs text-muted-foreground"
                    >
                        Limpiar filtros
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => applyFilters({ ...filters, offset: 0 })}
                        className="h-8 text-xs font-semibold"
                    >
                        Filtrar
                    </Button>
                </div>
            </Card>

            {/* Table */}
            <Card className="overflow-hidden rounded-2xl border">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-muted/40 font-semibold text-muted-foreground uppercase text-[11px] border-b">
                            <tr>
                                <th className="p-3.5">Plan / Fecha</th>
                                <th className="p-3.5">Ruta</th>
                                <th className="p-3.5">Camión</th>
                                <th className="p-3.5">Tripulación</th>
                                <th className="p-3.5 text-center">Despachos</th>
                                <th className="p-3.5 text-right">Peso (kg)</th>
                                <th className="p-3.5 text-right">Volumen (m³)</th>
                                <th className="p-3.5 text-center">Estado</th>
                                <th className="p-3.5 w-12 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {items.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={9}
                                        className="p-8 text-center text-muted-foreground"
                                    >
                                        <div className="flex flex-col items-center justify-center gap-2">
                                            <Truck className="size-8 text-muted-foreground/40" />
                                            <p className="font-medium text-sm">
                                                No se encontraron planes de carga
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                items.map((plan) => (
                                    <tr
                                        key={plan.id}
                                        className="hover:bg-muted/30 transition-colors"
                                    >
                                        {/* Code & Date */}
                                        <td className="p-3.5">
                                            <Link
                                                href={
                                                    loadPlans.show({
                                                        company: companyId,
                                                        id: plan.id,
                                                    }).url
                                                }
                                                className="font-bold text-primary hover:underline"
                                            >
                                                {plan.code}
                                            </Link>
                                            <div className="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                                                <Calendar className="size-3" />
                                                {plan.plan_date}
                                            </div>
                                        </td>

                                        {/* Route */}
                                        <td className="p-3.5">
                                            <div className="font-semibold text-foreground">
                                                {plan.route_name || '—'}
                                            </div>
                                            {plan.route_code && (
                                                <div className="text-[11px] text-muted-foreground">
                                                    {plan.route_code}
                                                </div>
                                            )}
                                        </td>

                                        {/* Vehicle */}
                                        <td className="p-3.5">
                                            {plan.vehicle_plate ? (
                                                <div>
                                                    <span className="font-mono font-bold text-xs uppercase bg-muted px-1.5 py-0.5 rounded">
                                                        {plan.vehicle_plate}
                                                    </span>
                                                    <div className="text-[11px] text-muted-foreground truncate max-w-[140px] mt-0.5">
                                                        {plan.vehicle_name}
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">—</span>
                                            )}
                                        </td>

                                        {/* Driver & Helper */}
                                        <td className="p-3.5">
                                            <div className="font-medium">
                                                Chofer: {plan.driver_name}
                                            </div>
                                            {plan.helper_name && (
                                                <div className="text-[11px] text-muted-foreground">
                                                    Ayudante: {plan.helper_name}
                                                </div>
                                            )}
                                        </td>

                                        {/* Dispatches Count */}
                                        <td className="p-3.5 text-center">
                                            <span className="font-bold tabular-nums px-2 py-0.5 rounded-full bg-primary/10 text-primary">
                                                {plan.total_dispatches}
                                            </span>
                                        </td>

                                        {/* Weight */}
                                        <td className="p-3.5 text-right font-semibold tabular-nums">
                                            {Number(plan.total_weight).toLocaleString('es-VE', {
                                                maximumFractionDigits: 1,
                                            })}
                                        </td>

                                        {/* Volume */}
                                        <td className="p-3.5 text-right tabular-nums text-muted-foreground">
                                            {Number(plan.total_volume).toLocaleString('es-VE', {
                                                maximumFractionDigits: 2,
                                            })}
                                        </td>

                                        {/* Status */}
                                        <td className="p-3.5 text-center">
                                            <StatusPill kind={STATUS_PILL_KIND[plan.status]}>
                                                {STATUS_LABELS[plan.status] ?? plan.status}
                                            </StatusPill>
                                        </td>

                                        {/* Actions */}
                                        <td className="p-3.5 text-center">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-7 rounded-md"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                        <span className="sr-only">Acciones</span>
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-44">
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
                                                            onClick={() => router.visit(loadPlans.show({ company: companyId, id: plan.id }).url + '?confirm=1')}
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
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {meta.total > meta.limit && (
                    <div className="border-t p-4">
                        <Pagination
                            meta={meta}
                            onPageChange={(offset) =>
                                applyFilters({ ...filters, offset })
                            }
                            label="plan de carga"
                            labelPlural="planes de carga"
                        />
                    </div>
                )}
            </Card>
        </div>
    );
}
