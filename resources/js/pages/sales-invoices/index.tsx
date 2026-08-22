import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import salesInvoices from '@/routes/sales-invoices';
import type { BreadcrumbItem } from '@/types';
import { SalesInvoiceList } from './components/SalesInvoiceList';
import type {
    SalesInvoice,
    SalesInvoiceFilters,
    SalesInvoiceMeta,
    SalesInvoiceOptions,
} from './types/SalesInvoice';

interface Props {
    salesInvoices: SalesInvoice[];
    meta: SalesInvoiceMeta;
    filters: SalesInvoiceFilters;
    options: SalesInvoiceOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesInvoicesIndex({
    salesInvoices: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de venta',
            href: salesInvoices.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facturas de venta" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SalesInvoiceList
                    salesInvoices={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
