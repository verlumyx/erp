import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Edit,
    Power,
    Scale,
    StickyNote,
    Truck,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import vehicles from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import {
    STATUS_LABELS,
    STATUS_PILL_KIND,
    VEHICLE_TYPE_LABELS,
    type Vehicle,
    type VehicleStatus,
} from './types/Vehicle';

interface Props {
    vehicle: Vehicle;
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

export default function VehiclesShow({ vehicle }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, transform } = useForm<{ status: VehicleStatus }>({
        status: vehicle.status,
    });

    const handleSwitchStatus = (status: VehicleStatus) => {
        transform(() => ({ status }));
        put(
            vehicles.updateStatus({ company: companyId, id: vehicle.id }).url,
            { preserveScroll: true },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Camiones', href: vehicles.index(companyId).url },
        {
            title: vehicle.plate,
            href: vehicles.show({ company: companyId, id: vehicle.id }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Camión ${vehicle.plate}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={vehicles.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Camiones
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-2xl font-extrabold tracking-tight">
                                {vehicle.plate}
                            </span>
                            <StatusPill
                                kind={STATUS_PILL_KIND[vehicle.status]}
                            >
                                {STATUS_LABELS[vehicle.status]}
                            </StatusPill>
                        </div>
                        <div className="text-[16px] font-bold">
                            {vehicle.name}
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5 text-[13.5px] font-medium text-muted-foreground">
                            <span>Código: {vehicle.code}</span>
                            <span>·</span>
                            <span>
                                {vehicle.vehicle_type_label ||
                                    VEHICLE_TYPE_LABELS[vehicle.vehicle_type]}
                            </span>
                            {(vehicle.brand || vehicle.model) && (
                                <>
                                    <span>·</span>
                                    <span>
                                        {[vehicle.brand, vehicle.model, vehicle.year]
                                            .filter(Boolean)
                                            .join(' ')}
                                    </span>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2.5">
                        {vehicle.status !== 'active' && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-primary"
                                onClick={() => handleSwitchStatus('active')}
                                disabled={processing}
                            >
                                <Check className="mr-1.5 size-4" />
                                Poner operativo
                            </Button>
                        )}
                        {vehicle.status !== 'maintenance' && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-warning"
                                onClick={() => handleSwitchStatus('maintenance')}
                                disabled={processing}
                            >
                                <Wrench className="mr-1.5 size-4" />
                                Mantenimiento
                            </Button>
                        )}
                        {vehicle.status !== 'inactive' && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                onClick={() => handleSwitchStatus('inactive')}
                                disabled={processing}
                            >
                                <Power className="mr-1.5 size-4" />
                                Desactivar
                            </Button>
                        )}

                        <Link
                            href={
                                vehicles.edit({
                                    company: companyId,
                                    id: vehicle.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit className="mr-1.5 size-4" />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </Card>

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Truck className="size-4" />
                            Ficha del vehículo
                        </div>
                        <DataRow label="Placa" value={vehicle.plate} />
                        <DataRow label="Código interno" value={vehicle.code} />
                        <DataRow
                            label="Tipo de carrocería"
                            value={
                                vehicle.vehicle_type_label ||
                                VEHICLE_TYPE_LABELS[vehicle.vehicle_type]
                            }
                        />
                        <DataRow
                            label="Marca"
                            value={vehicle.brand || '—'}
                        />
                        <DataRow
                            label="Modelo"
                            value={vehicle.model || '—'}
                        />
                        <DataRow
                            label="Año"
                            value={vehicle.year || '—'}
                        />
                        <DataRow
                            label="Creado por"
                            value={vehicle.creator_name || '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Scale className="size-4" />
                            Capacidades de carga
                        </div>
                        <DataRow
                            label="Capacidad máxima de peso"
                            value={
                                vehicle.capacity_weight > 0
                                    ? `${vehicle.capacity_weight.toLocaleString()} kg`
                                    : 'Sin declarar'
                            }
                        />
                        <DataRow
                            label="Capacidad volumétrica"
                            value={
                                vehicle.capacity_volume > 0
                                    ? `${vehicle.capacity_volume.toLocaleString()} m³`
                                    : 'Sin declarar'
                            }
                        />
                        {vehicle.capacity_weight === 0 &&
                            vehicle.capacity_volume === 0 && (
                                <p className="text-[12px] leading-relaxed text-muted-foreground">
                                    Sin capacidades declaradas, los planes de carga no
                                    podrán contrastar si los despachos sobrepasan los límites del vehículo.
                                </p>
                            )}
                    </Card>
                </div>

                {vehicle.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-4" />
                            Notas y observaciones
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {vehicle.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
