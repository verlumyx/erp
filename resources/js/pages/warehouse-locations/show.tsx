import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Boxes,
    Edit,
    Hash,
    Layers,
    MapPin,
    Power,
    Warehouse as WarehouseIcon,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import warehouseLocations from '@/routes/warehouse-locations';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import {
    LOCATION_TYPE_LABELS,
    type WarehouseLocation,
} from './types/WarehouseLocation';

interface Props {
    location: WarehouseLocation;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehouseLocationsShow({ location }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: location.status === 'active' ? 'inactive' : 'active',
    });

    const estadoUbicacion =
        location.status === 'inactive' ? 'inactivo' : 'activo';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ubicaciones', href: warehouseLocations.index(companyId).url },
        {
            title: location.name,
            href: warehouseLocations.show({
                company: companyId,
                id: location.id,
            }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            warehouseLocations.updateStatus({
                company: companyId,
                id: location.id,
            }).url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={location.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={warehouseLocations.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Ubicaciones
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {location.name}
                            </h1>
                            <StatusPill kind={estadoUbicacion} />
                            {location.is_default === 'yes' && (
                                <span className="rounded-full bg-primary-soft px-2.5 py-1 text-[11.5px] font-bold text-primary">
                                    Por defecto
                                </span>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {location.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <MapPin className="size-3.5 opacity-80" />
                                {location.location_code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Layers className="size-3.5 opacity-80" />
                                {LOCATION_TYPE_LABELS[location.type]}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Boxes className="size-3.5 opacity-80" />
                                Capacidad {location.capacity}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Link
                            href={
                                warehouses.show({
                                    company: companyId,
                                    id: location.warehouse_id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <WarehouseIcon />
                                {location.warehouse_name ?? 'Bodega'}
                            </Button>
                        </Link>
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {location.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                warehouseLocations.edit({
                                    company: companyId,
                                    id: location.id,
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

                <Card className="gap-3 rounded-2xl p-5">
                    <div className="text-base font-bold tracking-tight">
                        Jerarquía
                    </div>
                    <div className="flex flex-col gap-2.5">
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Bodega
                            </span>
                            <b className="font-bold">
                                {location.warehouse_name ?? '—'}
                            </b>
                        </div>
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Ubicación padre
                            </span>
                            <b className="font-bold">
                                {location.parent_name ?? 'Raíz'}
                            </b>
                        </div>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
