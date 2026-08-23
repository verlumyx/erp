import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import supplierPayments from '@/routes/supplier-payments';
import type { BreadcrumbItem } from '@/types';
import { SupplierPaymentList } from './components/SupplierPaymentList';
import type {
    SupplierPayment,
    SupplierPaymentFilters,
    SupplierPaymentMeta,
} from './types/SupplierPayment';

interface Props {
    supplierPayments: SupplierPayment[];
    meta: SupplierPaymentMeta;
    filters: SupplierPaymentFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierPaymentsIndex({
    supplierPayments: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pagos a proveedor',
            href: supplierPayments.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pagos a proveedor" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SupplierPaymentList
                    supplierPayments={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
