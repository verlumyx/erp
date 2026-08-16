import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import measurementUnits from '@/routes/measurement-units';
import type { BreadcrumbItem } from '@/types';
import { MeasurementUnitForm } from './components/MeasurementUnitForm';
import { MeasurementUnitFormProvider } from './contexts/MeasurementUnitFormContext';
import { useMeasurementUnitForm } from './hooks/useMeasurementUnitForm';
import type { MeasurementUnit } from './types/MeasurementUnit';

interface Props {
    measurementUnit: MeasurementUnit;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function MeasurementUnitsEdit({ measurementUnit }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Unidades de medida',
            href: measurementUnits.index(companyId).url,
        },
        {
            title: measurementUnit.name,
            href: measurementUnits.show({
                company: companyId,
                id: measurementUnit.id,
            }).url,
        },
        {
            title: 'Editar',
            href: measurementUnits.edit({
                company: companyId,
                id: measurementUnit.id,
            }).url,
        },
    ];

    const formMethods = useMeasurementUnitForm({
        mode: 'edit',
        initialData: measurementUnit,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${measurementUnit.name}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        measurementUnits.show({
                            company: companyId,
                            id: measurementUnit.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {measurementUnit.name}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar unidad de medida
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el nombre, el símbolo o la descripción
                    </p>
                </div>
                <MeasurementUnitFormProvider value={formMethods}>
                    <MeasurementUnitForm />
                </MeasurementUnitFormProvider>
            </div>
        </AppLayout>
    );
}
