import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import reports from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { ServicePlanReport } from './components/ServicePlanReport';
import type {
    ServicePlanFilters,
    ServicePlanMeta,
    ServicePlanRow,
    ServicePlanSummary,
} from './types/ServicePlan';

interface Props {
    rows: ServicePlanRow[];
    summary: ServicePlanSummary;
    meta: ServicePlanMeta;
    filters: ServicePlanFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ServicePlanReportPage({
    rows,
    summary,
    meta,
    filters,
    searched,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reportes', href: reports.servicePlan.index(companyId).url },
        {
            title: 'Servicio / Plan',
            href: reports.servicePlan.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte por Servicio / Plan" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ServicePlanReport
                    rows={rows}
                    summary={summary}
                    meta={meta}
                    filters={filters}
                    searched={searched}
                />
            </div>
        </AppLayout>
    );
}
