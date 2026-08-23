import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import supplierAdvances from '@/routes/supplier-advances';
import type { BreadcrumbItem } from '@/types';
import { SupplierAdvanceForm } from './components/SupplierAdvanceForm';
import { SupplierAdvanceFormProvider } from './contexts/SupplierAdvanceFormContext';
import { useSupplierAdvanceForm } from './hooks/useSupplierAdvanceForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierAdvancesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos a proveedor',
            href: supplierAdvances.index(companyId).url,
        },
        {
            title: 'Nuevo anticipo',
            href: supplierAdvances.create(companyId).url,
        },
    ];

    const formMethods = useSupplierAdvanceForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo anticipo a proveedor" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierAdvances.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Anticipos a proveedor
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo anticipo a proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra lo que se le adelanta antes de recibir su
                        factura
                    </p>
                </div>
                <SupplierAdvanceFormProvider value={formMethods}>
                    <SupplierAdvanceForm />
                </SupplierAdvanceFormProvider>
            </div>
        </AppLayout>
    );
}
