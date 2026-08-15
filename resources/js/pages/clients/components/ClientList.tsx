import { router, usePage } from '@inertiajs/react';
import {
    Download,
    Edit,
    Eye,
    MoreHorizontal,
    Plus,
    Power,
    Search,
} from 'lucide-react';
import { useState } from 'react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { ServiceStack } from '@/components/service-badge';
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
import { WhatsAppButton } from '@/components/whatsapp-button';
import clients from '@/routes/clients';
import type {
    Client,
    ClientFilters,
    ClientMeta,
    ClientPlatform,
} from '../types/Client';

interface ClientListProps {
    clients: Client[];
    meta: ClientMeta;
    filters: ClientFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export function ClientList({
    clients: items,
    meta,
    filters: initialFilters,
}: ClientListProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [filters, setFilters] = useState<ClientFilters>(initialFilters);
    const [plat, setPlat] = useState<string>('todas');

    const platformOptions: ClientPlatform[] = Array.from(
        new Map(
            items
                .flatMap((client) => client.platforms ?? [])
                .map((platform) => [platform.id, platform]),
        ).values(),
    );

    const applyFilters = (next: ClientFilters) => {
        setFilters(next);
        router.get(
            clients.index(companyId).url,
            next as Record<string, string>,
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleClear = () => {
        setFilters({});
        setPlat('todas');
        router.get(clients.index(companyId).url);
    };

    const handleToggleStatus = (client: Client) => {
        router.put(
            clients.updateStatus({ company: companyId, id: client.id }).url,
            {
                status: client.status === 'active' ? 'inactive' : 'active',
            },
        );
    };

    const rows = items.filter(
        (client) =>
            plat === 'todas' ||
            (client.platforms ?? []).some((p) => p.id === plat),
    );

    return (
        <div className="flex flex-col gap-5">
            <div className="flex flex-col items-start justify-between gap-4 sm:flex-row">
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Clientes
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        {meta.total} cliente{meta.total !== 1 ? 's' : ''} ·
                        perfiles y cuentas de streaming
                    </p>
                </div>
                <div className="flex gap-2.5">
                    <Button
                        variant="outline"
                        className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                    >
                        <Download />
                        Exportar
                    </Button>
                    <Button
                        className="h-10 rounded-[11px] px-4 font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                        onClick={() =>
                            router.visit(clients.create(companyId).url)
                        }
                    >
                        <Plus />
                        Nuevo cliente
                    </Button>
                </div>
            </div>

            <div className="rounded-lg border bg-card p-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {(
                        [
                            ['name', 'Nombre'],
                            ['email', 'Correo'],
                            ['phone', 'Teléfono'],
                            ['code', 'Código'],
                        ] as Array<[keyof ClientFilters, string]>
                    ).map(([key, label]) => (
                        <div key={key} className="space-y-2">
                            <Label htmlFor={`filter-${key}`}>{label}</Label>
                            <Input
                                id={`filter-${key}`}
                                type="text"
                                placeholder={`Buscar por ${label.toLowerCase()}...`}
                                value={
                                    (filters[key] as string | undefined) ?? ''
                                }
                                onChange={(e) =>
                                    setFilters({
                                        ...filters,
                                        [key]: e.target.value,
                                    })
                                }
                                onKeyDown={(e) =>
                                    e.key === 'Enter' && applyFilters(filters)
                                }
                            />
                        </div>
                    ))}
                    <div className="space-y-2">
                        <Label htmlFor="filter-status">Estado</Label>
                        <Select
                            value={filters.status ?? 'todos'}
                            onValueChange={(value) =>
                                applyFilters({
                                    ...filters,
                                    status:
                                        value === 'todos' ? undefined : value,
                                })
                            }
                        >
                            <SelectTrigger id="filter-status" className="w-full">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                <SelectItem value="active">Activos</SelectItem>
                                <SelectItem value="inactive">
                                    Inactivos
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="filter-platform">Plataforma</Label>
                        <Select value={plat} onValueChange={setPlat}>
                            <SelectTrigger
                                id="filter-platform"
                                className="w-full"
                            >
                                <SelectValue placeholder="Todas las plataformas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">
                                    Todas las plataformas
                                </SelectItem>
                                {platformOptions.map((p) => (
                                    <SelectItem key={p.id} value={p.id}>
                                        {p.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button onClick={() => applyFilters(filters)} variant="default">
                        <Search className="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                    <Button onClick={handleClear} variant="outline">
                        Limpiar
                    </Button>
                </div>
            </div>

            <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                <div className="hidden h-12 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[0.9fr_2.2fr_1.2fr_0.9fr_1.2fr]">
                    {[
                        'Código',
                        'Cliente',
                        'Plataformas',
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
                    {rows.map((client) => {
                        const estadoCliente =
                            client.status === 'inactive'
                                ? 'inactivo'
                                : 'activo';
                        const tel = client.phone ?? '';
                        return (
                            <div
                                key={client.id}
                                className="grid min-h-[66px] cursor-pointer grid-cols-[1fr_auto] items-center gap-3.5 border-b px-5 py-3 transition-colors last:border-b-0 hover:bg-muted lg:grid-cols-[0.9fr_2.2fr_1.2fr_0.9fr_1.2fr] lg:py-0"
                                onClick={() =>
                                    router.visit(
                                        clients.show({
                                            company: companyId,
                                            id: client.id,
                                        }).url,
                                    )
                                }
                            >
                                <div className="hidden lg:block">
                                    <span className="font-semibold tabular-nums text-muted-foreground">
                                        {client.code}
                                    </span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <InitialsAvatar
                                        name={client.name}
                                        size={40}
                                    />
                                    <div className="flex min-w-0 flex-col">
                                        <span className="truncate font-bold">
                                            {client.name}
                                        </span>
                                        <span className="truncate text-[12.5px] text-muted-foreground">
                                            {client.email ?? '—'}
                                        </span>
                                    </div>
                                </div>
                                <div className="hidden lg:block">
                                    <ServiceStack
                                        services={client.platforms ?? []}
                                    />
                                </div>
                                <div className="hidden lg:block">
                                    <StatusPill kind={estadoCliente} />
                                </div>
                                <div
                                    className="flex items-center justify-end gap-2"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    {tel && <WhatsAppButton tel={tel} />}
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
                                                        clients.show({
                                                            company: companyId,
                                                            id: client.id,
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
                                                        clients.edit({
                                                            company: companyId,
                                                            id: client.id,
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
                                                    handleToggleStatus(client)
                                                }
                                            >
                                                <Power className="mr-2 h-4 w-4" />
                                                {client.status === 'active'
                                                    ? 'Inactivar'
                                                    : 'Activar'}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        );
                    })}
                    {rows.length === 0 && (
                        <div className="p-12 text-center text-sm text-muted-foreground">
                            Sin resultados para tu búsqueda.
                        </div>
                    )}
                </div>
                <div className="px-5 py-3.5 text-[13px] font-semibold text-muted-foreground">
                    {rows.length} de {meta.total} cliente
                    {meta.total !== 1 ? 's' : ''}
                </div>
            </Card>
        </div>
    );
}
