import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import supplierPayments from '@/routes/supplier-payments';
import type { BreadcrumbItem } from '@/types';
import { SupplierPaymentForm } from './components/SupplierPaymentForm';
import { SupplierPaymentFormProvider } from './contexts/SupplierPaymentFormContext';
import { useSupplierPaymentForm } from './hooks/useSupplierPaymentForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierPaymentsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pagos a proveedor',
            href: supplierPayments.index(companyId).url,
        },
        {
            title: 'Nuevo pago',
            href: supplierPayments.create(companyId).url,
        },
    ];

    const formMethods = useSupplierPaymentForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo pago a proveedor" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierPayments.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Pagos a proveedor
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo pago a proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la salida de dinero y repártela entre sus
                        facturas
                    </p>
                </div>
                <SupplierPaymentFormProvider value={formMethods}>
                    <SupplierPaymentForm />
                </SupplierPaymentFormProvider>
            </div>
        </AppLayout>
    );
}
