import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import storeCustomers from '@/routes/store-customers';
import type { BreadcrumbItem } from '@/types';
import { StoreCustomerList } from '../components/StoreCustomerList';
import type {
    ListMeta,
    StoreCustomer,
    StoreCustomerFilters,
} from '../types/Store';

interface Props {
    store_customers: StoreCustomer[];
    meta: ListMeta;
    filters: StoreCustomerFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function StoreCustomersIndex({
    store_customers,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tienda', href: storeCustomers.index(companyId).url },
        { title: 'Compradores', href: storeCustomers.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Compradores" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <StoreCustomerList
                    storeCustomers={store_customers}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
