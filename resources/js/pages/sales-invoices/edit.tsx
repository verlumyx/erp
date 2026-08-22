import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import salesInvoices from '@/routes/sales-invoices';
import type { BreadcrumbItem } from '@/types';
import { SalesInvoiceForm } from './components/SalesInvoiceForm';
import { SalesInvoiceFormProvider } from './contexts/SalesInvoiceFormContext';
import { useSalesInvoiceForm } from './hooks/useSalesInvoiceForm';
import type { SalesInvoice, SalesInvoiceOptions } from './types/SalesInvoice';

interface Props {
    salesInvoice: SalesInvoice;
    options: SalesInvoiceOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesInvoicesEdit({ salesInvoice, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de venta',
            href: salesInvoices.index(companyId).url,
        },
        {
            title: salesInvoice.code,
            href: salesInvoices.show({
                company: companyId,
                id: salesInvoice.id,
            }).url,
        },
        {
            title: 'Editar',
            href: salesInvoices.edit({
                company: companyId,
                id: salesInvoice.id,
            }).url,
        },
    ];

    const formMethods = useSalesInvoiceForm({
        mode: 'edit',
        options,
        initialData: salesInvoice,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${salesInvoice.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        salesInvoices.show({
                            company: companyId,
                            id: salesInvoice.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {salesInvoice.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar factura de venta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo una factura en borrador se edita: una emitida se
                        anula o se corrige con nota de crédito
                    </p>
                </div>
                <SalesInvoiceFormProvider value={{ ...formMethods, options }}>
                    <SalesInvoiceForm />
                </SalesInvoiceFormProvider>
            </div>
        </AppLayout>
    );
}
