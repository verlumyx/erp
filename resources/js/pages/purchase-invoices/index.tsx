import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import purchaseInvoices from '@/routes/purchase-invoices';
import type { BreadcrumbItem } from '@/types';
import { PurchaseInvoiceList } from './components/PurchaseInvoiceList';
import type {
    PurchaseInvoice,
    PurchaseInvoiceFilters,
    PurchaseInvoiceMeta,
    PurchaseInvoiceOptions,
} from './types/PurchaseInvoice';

interface Props {
    purchaseInvoices: PurchaseInvoice[];
    meta: PurchaseInvoiceMeta;
    filters: PurchaseInvoiceFilters;
    options: PurchaseInvoiceOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseInvoicesIndex({
    purchaseInvoices: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de compra',
            href: purchaseInvoices.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturas de compra" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PurchaseInvoiceList
                    purchaseInvoices={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
