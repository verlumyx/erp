import { router, usePage } from '@inertiajs/react';
import {
    Edit,
    Eye,
    MoreHorizontal,
    Package,
    Plus,
    Power,
    Search,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { clp } from '@/lib/crm-demo';
import plans from '@/routes/plans';
import {
    CAPACITY_LABELS,
    type Plan,
    type PlanFilters,
    type PlanMeta,
} from '../types/Plan';

interface PlanListProps {
    plans: Plan[];
    meta: PlanMeta;
    filters: PlanFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function PlanList({
    plans: items,
    meta,
    filters: initialFilters,
}: PlanListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [searchName, setSearchName] = useState(initialFilters.name ?? '');
    const [searchCode, setSearchCode] = useState(initialFilters.code ?? '');
    const [capacityFilter, setCapacityFilter] = useState(
        initialFilters.capacity ?? 'all',
    );
    const [activeFilter, setActiveFilter] = useState(
        initialFilters.active ?? 'all',
    );

    const handleSearch = () => {
        const filters: Record<string, string> = {};

        if (searchName.trim()) filters.name = searchName.trim();
        if (searchCode.trim()) filters.code = searchCode.trim();
        if (capacityFilter !== 'all') filters.capacity = capacityFilter;
        if (activeFilter !== 'all') filters.active = activeFilter;

        router.get(plans.index(companyId).url, filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClearSearch = () => {
        setSearchName('');
        setSearchCode('');
        setCapacityFilter('all');
        setActiveFilter('all');
        router.get(plans.index(companyId).url);
    };

    const handleToggleStatus = (plan: Plan) => {
        router.put(plans.updateStatus({ company: companyId, id: plan.id }).url, {
            active: !plan.active,
        });
    };

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Planes
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} plan{meta.total !== 1 ? 'es' : ''} vendible
                        {meta.total !== 1 ? 's' : ''} en el catálogo
                    </p>
                </div>
                <Button
                    className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                    onClick={() => router.visit(plans.create(companyId).url)}
                >
                    <Plus />
                    Nuevo plan
                </Button>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label htmlFor="search-name">Nombre</Label>
                        <Input
                            id="search-name"
                            type="text"
                            value={searchName}
                            onChange={(e) => setSearchName(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                            placeholder="Buscar por nombre..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="search-code">Código</Label>
                        <Input
                            id="search-code"
                            type="text"
                            value={searchCode}
                            onChange={(e) => setSearchCode(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                            placeholder="Buscar por código..."
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="capacity-filter">Capacidad</Label>
                        <Select
                            value={capacityFilter}
                            onValueChange={(value) => setCapacityFilter(value)}
                        >
                            <SelectTrigger id="capacity-filter">
                                <SelectValue placeholder="Todas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="profile">
                                    {CAPACITY_LABELS.profile}
                                </SelectItem>
                                <SelectItem value="full_account">
                                    {CAPACITY_LABELS.full_account}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="active-filter">Estado</Label>
                        <Select
                            value={activeFilter}
                            onValueChange={(value) => setActiveFilter(value)}
                        >
                            <SelectTrigger id="active-filter">
                                <SelectValue placeholder="Todos" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="1">Activos</SelectItem>
                                <SelectItem value="0">Inactivos</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={handleSearch} variant="default">
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClearSearch} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.1fr_0.9fr_1fr_0.9fr_0.9fr]">
                    {[
                        'Código',
                        'Plan',
                        'Capacidad',
                        'Duración',
                        'Precio',
                        'Estado',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 6 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((plan) => (
                        <div
                            key={plan.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.1fr_0.9fr_1fr_0.9fr_0.9fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    plans.show({
                                        company: companyId,
                                        id: plan.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold tabular-nums text-muted-foreground">
                                    {plan.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-3">
                                <span className="grid size-10 shrink-0 place-items-center rounded-[11px] border bg-muted text-muted-foreground">
                                    <Package className="size-5" />
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {plan.name}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {plan.service?.name ?? '—'}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden lg:block">
                                <span className="text-[13.5px] font-semibold">
                                    {CAPACITY_LABELS[plan.capacity]}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-semibold tabular-nums">
                                    {plan.duration_days} días
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <span className="font-bold tabular-nums">
                                    {clp(Number(plan.sale_price))}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={plan.active ? 'activo' : 'inactivo'}
                                />
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
                                            onClick={() =>
                                                router.visit(
                                                    plans.show({
                                                        company: companyId,
                                                        id: plan.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                router.visit(
                                                    plans.edit({
                                                        company: companyId,
                                                        id: plan.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Editar
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() =>
                                                handleToggleStatus(plan)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {plan.active
                                                ? 'Inactivar'
                                                : 'Activar'}
                                        </DropdownMenuItem>
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
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {items.length} de {meta.total} plan
                    {meta.total !== 1 ? 'es' : ''}
                </div>
            </Card>
        </div>
    );
}
