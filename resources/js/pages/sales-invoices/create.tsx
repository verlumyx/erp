import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import salesInvoices from '@/routes/sales-invoices';
import type { BreadcrumbItem } from '@/types';
import { SalesInvoiceForm } from './components/SalesInvoiceForm';
import { SalesInvoiceFormProvider } from './contexts/SalesInvoiceFormContext';
import { useSalesInvoiceForm } from './hooks/useSalesInvoiceForm';
import type { SalesInvoiceOptions } from './types/SalesInvoice';

interface Props {
    options: SalesInvoiceOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesInvoicesCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Facturas de venta',
            href: salesInvoices.index(companyId).url,
        },
        { title: 'Nueva factura', href: salesInvoices.create(companyId).url },
    ];

    const formMethods = useSalesInvoiceForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva factura de venta" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={salesInvoices.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Facturas de venta
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva factura de venta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        La factura nace en borrador: quema su número fiscal y
                        genera la cuenta por cobrar recién al emitirla
                    </p>
                </div>
                <SalesInvoiceFormProvider value={{ ...formMethods, options }}>
                    <SalesInvoiceForm />
                </SalesInvoiceFormProvider>
            </div>
        </AppLayout>
    );
}
