import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import plans from '@/routes/plans';
import services from '@/routes/services';
import type { BreadcrumbItem } from '@/types';
import { PlanList } from './components/PlanList';
import type { Plan, PlanFilters, PlanMeta } from './types/Plan';

interface Props {
    plans: Plan[];
    meta: PlanMeta;
    filters: PlanFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PlansIndex({ plans: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Catálogo', href: services.index(companyId).url },
        { title: 'Planes', href: plans.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Planes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PlanList plans={items} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
