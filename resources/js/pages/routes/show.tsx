import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Check,
    Clock,
    Edit,
    MapPinned,
    StickyNote,
    Truck,
    Users,
    Warehouse,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import routeRoutes from '@/routes/routes';
import type { BreadcrumbItem } from '@/types';
import { RouteDayCard } from './components/RouteDayCard';
import { RouteStopsCard } from './components/RouteStopsCard';
import {
    FREQUENCY_LABELS,
    hasDeclaredCapacity,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    TYPE_LABELS,
    weekdayLabels,
    type Route,
    type RouteStop,
} from './types/Route';

interface Props {
    route: Route;
    stop_date: string;
    stops: RouteStop[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function RoutesShow({ route, stop_date, stops }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, errors, transform } = useForm({ status: '' });

    /** El error de desactivación llega en `status`, que aquí no se captura. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const activeClients = (route.clients ?? []).filter(
        (client) => client.status === 'active',
    );

    /** El estado destino viaja en el `transform`: la pantalla no captura nada. */
    const switchTo = (status: 'active' | 'inactive') => {
        transform(() => ({ status }));
        put(
            routeRoutes.updateStatus({ company: companyId, id: route.id }).url,
            { preserveScroll: true },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Rutas',
            href: routeRoutes.index(companyId).url,
        },
        {
            title: route.code,
            href: routeRoutes.show({ company: companyId, id: route.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={route.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={routeRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Rutas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {route.code}
                            </h1>
                            <StatusPill kind={STATUS_PILL_KIND[route.status]}>
                                {STATUS_LABELS[route.status]}
                            </StatusPill>
                        </div>
                        <div className="text-[15px] font-bold">
                            {route.name}
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <MapPinned className="size-3.5 opacity-80" />
                                {TYPE_LABELS[route.type]}
                                {route.zone ? ` · ${route.zone}` : ''}
                                {route.city ? ` · ${route.city}` : ''}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Clock className="size-3.5 opacity-80" />
                                {FREQUENCY_LABELS[route.frequency]}
                                {weekdayLabels(route.weekdays)
                                    ? ` · ${weekdayLabels(route.weekdays)}`
                                    : ''}
                            </span>
                            {route.warehouse_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Warehouse className="size-3.5 opacity-80" />
                                    {route.warehouse_name}
                                </span>
                            )}
                            {route.driver_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Truck className="size-3.5 opacity-80" />
                                    {route.driver_name}
                                    {route.vehicle_plate
                                        ? ` · ${route.vehicle_plate}`
                                        : ''}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {route.status === 'active' ? (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                onClick={() => switchTo('inactive')}
                                disabled={processing}
                            >
                                <Ban />
                                Desactivar
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => switchTo('active')}
                                disabled={processing}
                            >
                                <Check />
                                Activar
                            </Button>
                        )}
                        <Link
                            href={
                                routeRoutes.edit({
                                    company: companyId,
                                    id: route.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </Card>

                {statusError && (
                    <p className="text-sm text-bad">{statusError}</p>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            El recorrido
                        </div>
                        <DataRow
                            label="Clientes fijos"
                            value={activeClients.length}
                        />
                        <DataRow
                            label="Duración estimada"
                            value={
                                route.estimated_duration_minutes > 0
                                    ? `${route.estimated_duration_minutes} min`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Distancia estimada"
                            value={
                                Number(route.estimated_distance_km) > 0
                                    ? `${route.estimated_distance_km} km`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Vendedor"
                            value={route.salesperson_name ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            El vehículo
                        </div>
                        <DataRow
                            label="Placa"
                            value={route.vehicle_plate ?? '—'}
                        />
                        <DataRow
                            label="Capacidad en peso"
                            value={
                                Number(route.vehicle_capacity_weight) > 0
                                    ? route.vehicle_capacity_weight
                                    : 'Sin declarar'
                            }
                        />
                        <DataRow
                            label="Capacidad en volumen"
                            value={
                                Number(route.vehicle_capacity_volume) > 0
                                    ? route.vehicle_capacity_volume
                                    : 'Sin declarar'
                            }
                        />
                        {!hasDeclaredCapacity(route) && (
                            <p className="text-[12px] leading-relaxed text-muted-foreground">
                                Sin capacidad declarada no se comprueba que la
                                carga del día quepa en el vehículo.
                            </p>
                        )}
                    </Card>
                </div>

                <RouteDayCard route={route} stopDate={stop_date} />

                <RouteStopsCard
                    route={route}
                    stopDate={stop_date}
                    stops={stops}
                />

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <Users className="size-[15px]" />
                        Clientes fijos de la ruta
                    </div>
                    <div className="flex flex-col">
                        {activeClients.map((client) => (
                            <div
                                key={client.id}
                                className="flex items-center gap-3.5 border-b px-5 py-3 last:border-b-0"
                            >
                                <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary tabular-nums">
                                    {client.sequence}
                                </span>
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {client.client_name ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {client.client_code}
                                        {client.client_address_name
                                            ? ` · ${client.client_address_name}`
                                            : ' · Dirección por defecto'}
                                    </span>
                                </div>
                            </div>
                        ))}
                        {activeClients.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                La ruta no tiene clientes fijos. Al planificar
                                un día solo aparecerán los clientes con
                                despachos asignados.
                            </div>
                        )}
                    </div>
                </Card>

                {route.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {route.description}
                        </p>
                    </Card>
                )}

                {route.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {route.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
