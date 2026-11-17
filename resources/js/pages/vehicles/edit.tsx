import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import vehicles from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { VehicleForm } from './components/VehicleForm';
import { VehicleFormProvider } from './contexts/VehicleFormContext';
import { useVehicleForm } from './hooks/useVehicleForm';
import type { Vehicle } from './types/Vehicle';

interface Props {
    vehicle: Vehicle;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function VehiclesEdit({ vehicle }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Camiones', href: vehicles.index(companyId).url },
        {
            title: vehicle.plate,
            href: vehicles.show({ company: companyId, id: vehicle.id }).url,
        },
        {
            title: 'Editar',
            href: vehicles.edit({ company: companyId, id: vehicle.id }).url,
        },
    ];

    const formMethods = useVehicleForm({
        mode: 'edit',
        initialData: vehicle,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${vehicle.plate}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        vehicles.show({
                            company: companyId,
                            id: vehicle.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {vehicle.plate}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar camión {vehicle.plate}
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los datos del vehículo o sus capacidades de carga
                    </p>
                </div>

                <VehicleFormProvider value={formMethods}>
                    <VehicleForm />
                </VehicleFormProvider>
            </div>
        </AppLayout>
    );
}
