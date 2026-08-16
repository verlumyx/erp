import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import clientTypeRoutes from '@/routes/client-types';
import type { BreadcrumbItem } from '@/types';
import { ClientTypeList } from './components/ClientTypeList';
import type {
    ClientType,
    ClientTypeFilters,
    ClientTypeMeta,
} from './types/ClientType';

interface Props {
    clientTypes: ClientType[];
    meta: ClientTypeMeta;
    filters: ClientTypeFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientTypesIndex({
    clientTypes: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de cliente',
            href: clientTypeRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tipos de cliente" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ClientTypeList
                    clientTypes={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
