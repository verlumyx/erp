import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import dispatchRoutes from '@/routes/dispatches';
import type { BreadcrumbItem } from '@/types';
import { DispatchList } from './components/DispatchList';
import type { Dispatch, DispatchFilters, DispatchMeta } from './types/Dispatch';

interface Props {
    dispatches: Dispatch[];
    meta: DispatchMeta;
    filters: DispatchFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function DispatchesIndex({
    dispatches: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Despachos',
            href: dispatchRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Despachos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <DispatchList dispatches={rows} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
