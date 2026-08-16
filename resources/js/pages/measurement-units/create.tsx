import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import measurementUnits from '@/routes/measurement-units';
import type { BreadcrumbItem } from '@/types';
import { MeasurementUnitForm } from './components/MeasurementUnitForm';
import { MeasurementUnitFormProvider } from './contexts/MeasurementUnitFormContext';
import { useMeasurementUnitForm } from './hooks/useMeasurementUnitForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function MeasurementUnitsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Unidades de medida',
            href: measurementUnits.index(companyId).url,
        },
        {
            title: 'Nueva unidad',
            href: measurementUnits.create(companyId).url,
        },
    ];

    const formMethods = useMeasurementUnitForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva unidad de medida" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={measurementUnits.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Unidades de medida
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva unidad de medida
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra el nombre y el símbolo de la unidad
                    </p>
                </div>
                <MeasurementUnitFormProvider value={formMethods}>
                    <MeasurementUnitForm />
                </MeasurementUnitFormProvider>
            </div>
        </AppLayout>
    );
}
