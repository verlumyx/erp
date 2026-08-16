import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import measurementUnitRoutes from '@/routes/measurement-units';
import type { BreadcrumbItem } from '@/types';
import { MeasurementUnitList } from './components/MeasurementUnitList';
import type {
    MeasurementUnit,
    MeasurementUnitFilters,
    MeasurementUnitMeta,
} from './types/MeasurementUnit';

interface Props {
    measurementUnits: MeasurementUnit[];
    meta: MeasurementUnitMeta;
    filters: MeasurementUnitFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function MeasurementUnitsIndex({
    measurementUnits: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Unidades de medida',
            href: measurementUnitRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Unidades de medida" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <MeasurementUnitList
                    measurementUnits={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
