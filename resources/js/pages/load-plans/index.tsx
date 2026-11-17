import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import loadPlans from '@/routes/load-plans';
import type { BreadcrumbItem } from '@/types';
import { LoadPlanList } from './components/LoadPlanList';
import type {
    LoadPlan,
    LoadPlanFilters,
    LoadPlanMeta,
    LoadPlanOptions,
} from './types/LoadPlan';

interface Props {
    loadPlans: LoadPlan[];
    meta: LoadPlanMeta;
    filters: LoadPlanFilters;
    options: LoadPlanOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function LoadPlansIndex({
    loadPlans: items,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Planes de Carga', href: loadPlans.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Planes de Carga" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <LoadPlanList
                    loadPlans={items}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
