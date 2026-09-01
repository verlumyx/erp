import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import adjustments from '@/routes/adjustments';
import type { BreadcrumbItem } from '@/types';
import { AdjustmentList } from './components/AdjustmentList';
import type {
    Adjustment,
    AdjustmentFilters,
    AdjustmentMeta,
} from './types/Adjustment';

interface Props {
    adjustments: Adjustment[];
    meta: AdjustmentMeta;
    filters: AdjustmentFilters;
    warehouses: Array<{ id: string; name: string }>;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function AdjustmentsIndex({
    adjustments: rows,
    meta,
    filters,
    warehouses,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Ajustes',
            href: adjustments.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajustes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <AdjustmentList
                    adjustments={rows}
                    meta={meta}
                    filters={filters}
                    warehouses={warehouses}
                />
            </div>
        </AppLayout>
    );
}
