import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import suppliers from '@/routes/suppliers';
import type { BreadcrumbItem } from '@/types';
import { SupplierForm } from './components/SupplierForm';
import { SupplierFormProvider } from './contexts/SupplierFormContext';
import { useSupplierForm } from './hooks/useSupplierForm';
import type { SupplierOptions } from './types/Supplier';

interface Props {
    options: SupplierOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SuppliersCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Proveedores', href: suppliers.index(companyId).url },
        { title: 'Nuevo proveedor', href: suppliers.create(companyId).url },
    ];

    const formMethods = useSupplierForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo proveedor" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={suppliers.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Proveedores
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Define el proveedor, sus contactos, sus direcciones y
                        sus condiciones comerciales
                    </p>
                </div>
                <SupplierFormProvider value={{ ...formMethods, options }}>
                    <SupplierForm />
                </SupplierFormProvider>
            </div>
        </AppLayout>
    );
}
