import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import purchaseOrders from '@/routes/purchase-orders';
import type { BreadcrumbItem } from '@/types';
import { PurchaseOrderList } from './components/PurchaseOrderList';
import type {
    PurchaseOrder,
    PurchaseOrderFilters,
    PurchaseOrderMeta,
    PurchaseOrderOptions,
} from './types/PurchaseOrder';

interface Props {
    purchaseOrders: PurchaseOrder[];
    meta: PurchaseOrderMeta;
    filters: PurchaseOrderFilters;
    options: PurchaseOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseOrdersIndex({
    purchaseOrders: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Órdenes de compra',
            href: purchaseOrders.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Órdenes de compra" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PurchaseOrderList
                    purchaseOrders={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
