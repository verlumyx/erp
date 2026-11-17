import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import vehicles from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { VehicleList } from './components/VehicleList';
import type {
    Vehicle,
    VehicleFilters,
    VehicleMeta,
} from './types/Vehicle';

interface Props {
    vehicles: Vehicle[];
    meta: VehicleMeta;
    filters: VehicleFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function VehiclesIndex({
    vehicles: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Camiones', href: vehicles.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Camiones y Flota" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <VehicleList
                    vehicles={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
