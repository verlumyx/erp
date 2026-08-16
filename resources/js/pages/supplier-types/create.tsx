import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import supplierTypes from '@/routes/supplier-types';
import type { BreadcrumbItem } from '@/types';
import { SupplierTypeForm } from './components/SupplierTypeForm';
import { SupplierTypeFormProvider } from './contexts/SupplierTypeFormContext';
import { useSupplierTypeForm } from './hooks/useSupplierTypeForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierTypesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de proveedor',
            href: supplierTypes.index(companyId).url,
        },
        {
            title: 'Nuevo tipo',
            href: supplierTypes.create(companyId).url,
        },
    ];

    const formMethods = useSupplierTypeForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo tipo de proveedor" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierTypes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tipos de proveedor
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo tipo de proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra la clasificación con la que agrupas a tus
                        proveedores
                    </p>
                </div>
                <SupplierTypeFormProvider value={formMethods}>
                    <SupplierTypeForm />
                </SupplierTypeFormProvider>
            </div>
        </AppLayout>
    );
}
