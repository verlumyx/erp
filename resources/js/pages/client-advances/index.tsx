import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import clientAdvances from '@/routes/client-advances';
import type { BreadcrumbItem } from '@/types';
import { ClientAdvanceList } from './components/ClientAdvanceList';
import type {
    ClientAdvance,
    ClientAdvanceFilters,
    ClientAdvanceMeta,
} from './types/ClientAdvance';

interface Props {
    clientAdvances: ClientAdvance[];
    meta: ClientAdvanceMeta;
    filters: ClientAdvanceFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientAdvancesIndex({
    clientAdvances: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos de clientes',
            href: clientAdvances.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Anticipos de clientes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ClientAdvanceList
                    clientAdvances={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
