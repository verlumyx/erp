import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import warehouses from '@/routes/warehouses';
import type { BreadcrumbItem } from '@/types';
import { WarehouseForm } from './components/WarehouseForm';
import { WarehouseFormProvider } from './contexts/WarehouseFormContext';
import { useWarehouseForm } from './hooks/useWarehouseForm';
import type { WarehouseUserOption } from './types/Warehouse';

interface Props {
    users: WarehouseUserOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehousesCreate({ users }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Bodegas', href: warehouses.index(companyId).url },
        { title: 'Nueva bodega', href: warehouses.create(companyId).url },
    ];

    const formMethods = useWarehouseForm({ mode: 'create', users });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva bodega" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={warehouses.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Bodegas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva bodega
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Se crea con su ubicación «Principal»: todo saldo vive en
                        una ubicación
                    </p>
                </div>
                <WarehouseFormProvider value={formMethods}>
                    <WarehouseForm />
                </WarehouseFormProvider>
            </div>
        </AppLayout>
    );
}
