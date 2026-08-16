import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import warehouseLocations from '@/routes/warehouse-locations';
import type { BreadcrumbItem } from '@/types';
import { WarehouseLocationList } from './components/WarehouseLocationList';
import type {
    WarehouseLocation,
    WarehouseLocationFilters,
    WarehouseLocationMeta,
    WarehouseOption,
} from './types/WarehouseLocation';

interface Props {
    locations: WarehouseLocation[];
    warehouses: WarehouseOption[];
    meta: WarehouseLocationMeta;
    filters: WarehouseLocationFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehouseLocationsIndex({
    locations,
    warehouses,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ubicaciones', href: warehouseLocations.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ubicaciones" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <WarehouseLocationList
                    locations={locations}
                    warehouses={warehouses}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
