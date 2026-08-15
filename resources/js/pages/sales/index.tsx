import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import sales from '@/routes/sales';
import type { BreadcrumbItem } from '@/types';
import { SaleList } from './components/SaleList';
import type {
    AgentOption,
    ClientOption,
    Sale,
    SaleFilters,
    SaleMeta,
    ServiceOption,
} from './types/Sale';

interface Props {
    sales: Sale[];
    meta: SaleMeta;
    filters: SaleFilters;
    clients: ClientOption[];
    services: ServiceOption[];
    agents: AgentOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesIndex({
    sales: items,
    meta,
    filters,
    clients,
    services,
    agents,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ventas', href: sales.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ventas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SaleList
                    sales={items}
                    meta={meta}
                    filters={filters}
                    clients={clients}
                    services={services}
                    agents={agents}
                />
            </div>
        </AppLayout>
    );
}
