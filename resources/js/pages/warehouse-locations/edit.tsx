import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import warehouseLocations from '@/routes/warehouse-locations';
import type { BreadcrumbItem } from '@/types';
import { WarehouseLocationForm } from './components/WarehouseLocationForm';
import { WarehouseLocationFormProvider } from './contexts/WarehouseLocationFormContext';
import { useWarehouseLocationForm } from './hooks/useWarehouseLocationForm';
import type {
    ParentOption,
    WarehouseLocation,
    WarehouseOption,
} from './types/WarehouseLocation';

interface Props {
    location: WarehouseLocation;
    warehouses: WarehouseOption[];
    parents: ParentOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function WarehouseLocationsEdit({
    location,
    warehouses,
    parents,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ubicaciones', href: warehouseLocations.index(companyId).url },
        {
            title: location.name,
            href: warehouseLocations.show({
                company: companyId,
                id: location.id,
            }).url,
        },
        {
            title: 'Editar',
            href: warehouseLocations.edit({
                company: companyId,
                id: location.id,
            }).url,
        },
    ];

    const formMethods = useWarehouseLocationForm({
        mode: 'edit',
        warehouses,
        parents,
        initialData: location,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${location.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        warehouseLocations.show({
                            company: companyId,
                            id: location.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {location.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar ubicación
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        La bodega no se cambia: crea la ubicación en la bodega
                        que corresponda
                    </p>
                </div>
                <WarehouseLocationFormProvider value={formMethods}>
                    <WarehouseLocationForm />
                </WarehouseLocationFormProvider>
            </div>
        </AppLayout>
    );
}
