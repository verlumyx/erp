import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import supplierPayments from '@/routes/supplier-payments';
import type { BreadcrumbItem } from '@/types';
import { SupplierPaymentForm } from './components/SupplierPaymentForm';
import { SupplierPaymentFormProvider } from './contexts/SupplierPaymentFormContext';
import { useSupplierPaymentForm } from './hooks/useSupplierPaymentForm';
import type { SupplierPayment } from './types/SupplierPayment';

interface Props {
    supplierPayment: SupplierPayment;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierPaymentsEdit({ supplierPayment }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Pagos a proveedor',
            href: supplierPayments.index(companyId).url,
        },
        {
            title: supplierPayment.code,
            href: supplierPayments.show({
                company: companyId,
                id: supplierPayment.id,
            }).url,
        },
        {
            title: 'Editar',
            href: supplierPayments.edit({
                company: companyId,
                id: supplierPayment.id,
            }).url,
        },
    ];

    const formMethods = useSupplierPaymentForm({
        mode: 'edit',
        initialData: supplierPayment,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${supplierPayment.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        supplierPayments.show({
                            company: companyId,
                            id: supplierPayment.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {supplierPayment.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar pago a proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el pago está en borrador
                    </p>
                </div>
                <SupplierPaymentFormProvider value={formMethods}>
                    <SupplierPaymentForm />
                </SupplierPaymentFormProvider>
            </div>
        </AppLayout>
    );
}
