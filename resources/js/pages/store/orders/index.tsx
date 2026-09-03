import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import storeOrders from '@/routes/store-orders';
import type { BreadcrumbItem } from '@/types';
import { StoreOrderList } from '../components/StoreOrderList';
import type { ListMeta, StoreOrder, StoreOrderFilters } from '../types/Store';

interface Props {
    store_orders: StoreOrder[];
    meta: ListMeta;
    filters: StoreOrderFilters;
    pending_count?: number;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function StoreOrdersIndex({
    store_orders,
    meta,
    filters,
    pending_count,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tienda', href: storeOrders.index(companyId).url },
        { title: 'Pedidos web', href: storeOrders.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pedidos web" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <StoreOrderList
                    storeOrders={store_orders}
                    meta={meta}
                    filters={filters}
                    pendingCount={pending_count}
                />
            </div>
        </AppLayout>
    );
}
