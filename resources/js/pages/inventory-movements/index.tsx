import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import inventoryMovements from '@/routes/inventory-movements';
import type { BreadcrumbItem } from '@/types';
import { InventoryMovementList } from './components/InventoryMovementList';
import type {
    InventoryMovement,
    InventoryMovementFilters,
    InventoryMovementMeta,
    WarehouseOption,
} from './types/InventoryMovement';

interface Props {
    movements: InventoryMovement[];
    warehouses: WarehouseOption[];
    meta: InventoryMovementMeta;
    filters: InventoryMovementFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function InventoryMovementsIndex({
    movements,
    warehouses,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kardex', href: inventoryMovements.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kardex" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <InventoryMovementList
                    movements={movements}
                    warehouses={warehouses}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
