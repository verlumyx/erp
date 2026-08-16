import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import { WarehouseForm } from './components/WarehouseForm';
import { WarehouseFormProvider } from './contexts/WarehouseFormContext';
import { useWarehouseForm } from './hooks/useWarehouseForm';
import type { Warehouse, WarehouseUserOption } from './types/Warehouse';

interface Props {
    warehouse: Warehouse;
    users: WarehouseUserOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehousesEdit({ warehouse, users }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Bodegas', href: warehouses.index(companyId).url },
        {
            title: warehouse.name,
            href: warehouses.show({ company: companyId, id: warehouse.id }).url,
        },
        {
            title: 'Editar',
            href: warehouses.edit({ company: companyId, id: warehouse.id }).url,
        },
    ];

    const formMethods = useWarehouseForm({
        mode: 'edit',
        users,
        initialData: warehouse,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${warehouse.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        warehouses.show({
                            company: companyId,
                            id: warehouse.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {warehouse.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar bodega
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los datos, el encargado y el comportamiento de
                        la bodega
                    </p>
                </div>
                <WarehouseFormProvider value={formMethods}>
                    <WarehouseForm />
                </WarehouseFormProvider>
            </div>
        </AppLayout>
    );
}
