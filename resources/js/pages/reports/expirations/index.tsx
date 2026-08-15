import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { Sale } from '@/pages/sales/types/Sale';
import reports from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { ExpirationReport } from './components/ExpirationReport';
import type {
    ExpirationAgentOption,
    ExpirationFilters,
    ExpirationMeta,
    ExpirationServiceOption,
    ExpirationSummary,
} from './types/Expiration';

interface Props {
    sales: Sale[];
    summary: ExpirationSummary;
    services: ExpirationServiceOption[];
    agents: ExpirationAgentOption[];
    meta: ExpirationMeta;
    filters: ExpirationFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ExpirationsReportPage({
    sales,
    summary,
    services,
    agents,
    meta,
    filters,
    searched,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reportes', href: reports.expirations.index(companyId).url },
        {
            title: 'Vencimientos',
            href: reports.expirations.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reporte de Vencimientos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ExpirationReport
                    sales={sales}
                    summary={summary}
                    services={services}
                    agents={agents}
                    meta={meta}
                    filters={filters}
                    searched={searched}
                />
            </div>
        </AppLayout>
    );
}
