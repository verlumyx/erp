import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import { SalesOrderList } from './components/SalesOrderList';
import type {
    SalesOrder,
    SalesOrderFilters,
    SalesOrderMeta,
    SalesOrderOptions,
} from './types/SalesOrder';

interface Props {
    salesOrders: SalesOrder[];
    meta: SalesOrderMeta;
    filters: SalesOrderFilters;
    options: SalesOrderOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesOrdersIndex({
    salesOrders: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Órdenes de venta', href: salesOrders.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Órdenes de venta" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SalesOrderList
                    salesOrders={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
