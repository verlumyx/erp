import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Edit,
    Hash,
    MapPin,
    Phone,
    Power,
    StickyNote,
    User,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import warehouseLocations from '@/routes/warehouse-locations';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import { WAREHOUSE_TYPE_LABELS, type Warehouse } from './types/Warehouse';

interface Props {
    warehouse: Warehouse;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface FlagRowProps {
    label: string;
    value: 'yes' | 'no';
}

function FlagRow({ label, value }: FlagRowProps) {
    return (
        <div className="flex items-center justify-between text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="font-bold">{value === 'yes' ? 'Sí' : 'No'}</b>
        </div>
    );
}

export default function WarehousesShow({ warehouse }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: warehouse.status === 'active' ? 'inactive' : 'active',
    });

    const estadoBodega =
        warehouse.status === 'inactive' ? 'inactivo' : 'activo';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Bodegas', href: warehouses.index(companyId).url },
        {
            title: warehouse.name,
            href: warehouses.show({ company: companyId, id: warehouse.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            warehouses.updateStatus({ company: companyId, id: warehouse.id })
                .url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={warehouse.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={warehouses.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Bodegas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {warehouse.name}
                            </h1>
                            <StatusPill kind={estadoBodega} />
                            {warehouse.is_default === 'yes' && (
                                <span className="rounded-full bg-primary-soft px-2.5 py-1 text-[11.5px] font-bold text-primary">
                                    Por defecto
                                </span>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {warehouse.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Building2 className="size-3.5 opacity-80" />
                                {WAREHOUSE_TYPE_LABELS[warehouse.type]}
                            </span>
                            {warehouse.city && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <MapPin className="size-3.5 opacity-80" />
                                    {warehouse.city}
                                </span>
                            )}
                            {warehouse.phone && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Phone className="size-3.5 opacity-80" />
                                    {warehouse.phone}
                                </span>
                            )}
                            {warehouse.responsible_user_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <User className="size-3.5 opacity-80" />
                                    {warehouse.responsible_user_name}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {warehouse.uses_locations === 'yes' && (
                            <Link
                                href={
                                    warehouseLocations.index(companyId, {
                                        query: {
                                            warehouse_id: warehouse.id,
                                        },
                                    }).url
                                }
                            >
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                >
                                    <MapPin />
                                    Ubicaciones
                                </Button>
                            </Link>
                        )}
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {warehouse.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                warehouses.edit({
                                    company: companyId,
                                    id: warehouse.id,
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

                <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl p-5">
                        <div className="text-base font-bold tracking-tight">
                            Comportamiento
                        </div>
                        <div className="flex flex-col gap-2.5">
                            <FlagRow
                                label="Bodega por defecto"
                                value={warehouse.is_default}
                            />
                            <FlagRow
                                label="Disponible para venta"
                                value={warehouse.is_sales_available}
                            />
                            <FlagRow
                                label="Permite existencia negativa"
                                value={warehouse.allows_negative_stock}
                            />
                            <FlagRow
                                label="Usa ubicaciones"
                                value={warehouse.uses_locations}
                            />
                        </div>
                    </Card>

                    {warehouse.address && (
                        <Card className="gap-2 rounded-2xl p-5">
                            <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                                <MapPin className="size-[15px]" />
                                Dirección
                            </div>
                            <p className="text-sm leading-relaxed">
                                {warehouse.address}
                            </p>
                        </Card>
                    )}
                </div>

                {warehouse.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {warehouse.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
