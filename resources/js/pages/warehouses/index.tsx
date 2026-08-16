import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import { WarehouseList } from './components/WarehouseList';
import type {
    Warehouse,
    WarehouseFilters,
    WarehouseMeta,
} from './types/Warehouse';

interface Props {
    warehouses: Warehouse[];
    meta: WarehouseMeta;
    filters: WarehouseFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehousesIndex({
    warehouses: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Bodegas', href: warehouses.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bodegas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <WarehouseList
                    warehouses={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
