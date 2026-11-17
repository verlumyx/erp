import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import vehicles from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { VehicleForm } from './components/VehicleForm';
import { VehicleFormProvider } from './contexts/VehicleFormContext';
import { useVehicleForm } from './hooks/useVehicleForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function VehiclesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Camiones', href: vehicles.index(companyId).url },
        { title: 'Nuevo camión', href: vehicles.create(companyId).url },
    ];

    const formMethods = useVehicleForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo camión" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={vehicles.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Camiones
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo camión
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra una unidad de transporte con su placa y límites de peso y volumen
                    </p>
                </div>

                <VehicleFormProvider value={formMethods}>
                    <VehicleForm />
                </VehicleFormProvider>
            </div>
        </AppLayout>
    );
}
