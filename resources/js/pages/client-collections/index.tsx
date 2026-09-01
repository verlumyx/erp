import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import clientCollections from '@/routes/client-collections';
import type { BreadcrumbItem } from '@/types';
import { ClientCollectionList } from './components/ClientCollectionList';
import type {
    ClientCollection,
    ClientCollectionFilters,
    ClientCollectionMeta,
    ClientCollectionOptions,
} from './types/ClientCollection';

interface Props {
    clientCollections: ClientCollection[];
    meta: ClientCollectionMeta;
    filters: ClientCollectionFilters;
    options: ClientCollectionOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientCollectionsIndex({
    clientCollections: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Cobros a clientes',
            href: clientCollections.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cobros a clientes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ClientCollectionList
                    clientCollections={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
