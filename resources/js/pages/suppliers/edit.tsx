import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import suppliers from '@/routes/suppliers';
import type { BreadcrumbItem } from '@/types';
import { SupplierForm } from './components/SupplierForm';
import { SupplierFormProvider } from './contexts/SupplierFormContext';
import { useSupplierForm } from './hooks/useSupplierForm';
import type { Supplier, SupplierOptions } from './types/Supplier';

interface Props {
    supplier: Supplier;
    options: SupplierOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SuppliersEdit({ supplier, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Proveedores', href: suppliers.index(companyId).url },
        {
            title: supplier.name,
            href: suppliers.show({ company: companyId, id: supplier.id }).url,
        },
        {
            title: 'Editar',
            href: suppliers.edit({ company: companyId, id: supplier.id }).url,
        },
    ];

    const formMethods = useSupplierForm({
        mode: 'edit',
        initialData: supplier,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${supplier.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        suppliers.show({
                            company: companyId,
                            id: supplier.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {supplier.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar proveedor
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Actualiza los datos, contactos y direcciones del
                        proveedor
                    </p>
                </div>
                <SupplierFormProvider value={{ ...formMethods, options }}>
                    <SupplierForm />
                </SupplierFormProvider>
            </div>
        </AppLayout>
    );
}
