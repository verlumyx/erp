import { router, usePage } from '@inertiajs/react';
import { Eye, MoreHorizontal, Power, Search, Users } from 'lucide-react';
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
import services from '@/routes/services';
import type { Service, ServiceFilters, ServiceMeta } from '../types/Service';
import { ServiceLogo } from './ServiceLogo';

interface ServiceListProps {
    services: Service[];
    meta: ServiceMeta;
    filters: ServiceFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function ServiceList({
    services: items,
    meta,
    filters: initialFilters,
}: ServiceListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [searchName, setSearchName] = useState(initialFilters.name ?? '');
    const [searchCode, setSearchCode] = useState(initialFilters.code ?? '');
    const [activeFilter, setActiveFilter] = useState<string>(
        initialFilters.active ?? 'all',
    );

    const handleSearch = () => {
        const filters: ServiceFilters = {};

        if (searchName.trim()) filters.name = searchName.trim();
        if (searchCode.trim()) filters.code = searchCode.trim();
        if (activeFilter !== 'all') filters.active = activeFilter;

        router.get(
            services.index(companyId).url,
            filters as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClearSearch = () => {
        setSearchName('');
        setSearchCode('');
        setActiveFilter('all');
        router.get(services.index(companyId).url);
    };

    const handleToggleStatus = (service: Service) => {
        router.put(
            services.updateStatus({ company: companyId, id: service.id }).url,
            { active: !service.active },
        );
    };

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900">Servicios</h1>
                <p className="text-gray-600">
                    Consulta el catálogo de servicios
                </p>
            </div>

            <div className="rounded-lg border border-gray-200 bg-white p-4">
                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.4fr_1.2fr_1fr_1fr]">
                    {[
                        'Código',
                        'Servicio',
                        'Máx. perfiles',
                        'Estado',
                        'Acciones',
                    ].map((h, i) => (
                        <div
                            key={h}
                            className={`text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase ${
                                i === 4 ? 'text-right' : ''
                            }`}
                        >
                            {h}
                        </div>
                    ))}
                </div>
                <div className="flex flex-col">
                    {items.map((service) => (
                        <div
                            key={service.id}
                            className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.4fr_1.2fr_1fr_1fr] lg:py-0"
                            onClick={() =>
                                router.visit(
                                    services.show({
                                        company: companyId,
                                        id: service.id,
                                    }).url,
                                )
                            }
                        >
                            <div className="hidden lg:block">
                                <span className="font-semibold tabular-nums text-muted-foreground">
                                    {service.code}
                                </span>
                            </div>
                            <div className="flex items-center gap-3">
                                <ServiceLogo
                                    name={service.name}
                                    logoUrl={service.logo_url}
                                />
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {service.name}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground lg:hidden">
                                        {service.code}
                                    </span>
                                </div>
                            </div>
                            <div className="hidden lg:flex lg:items-center lg:gap-1.5">
                                <Users className="size-4 text-muted-foreground" />
                                <span className="font-bold tabular-nums">
                                    {service.max_profiles}
                                </span>
                            </div>
                            <div className="hidden lg:block">
                                <StatusPill
                                    kind={service.active ? 'activo' : 'inactivo'}
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
                                                    services.show({
                                                        company: companyId,
                                                        id: service.id,
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Eye className="mr-2 h-4 w-4" />
                                            Ver
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            onClick={() =>
                                                handleToggleStatus(service)
                                            }
                                        >
                                            <Power className="mr-2 h-4 w-4" />
                                            {service.active
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
                    {items.length} de {meta.total} servicio
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
