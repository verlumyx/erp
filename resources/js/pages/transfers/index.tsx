import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import transferRoutes from '@/routes/transfers';
import type { BreadcrumbItem } from '@/types';
import { TransferList } from './components/TransferList';
import type {
    Transfer,
    TransferFilters,
    TransferMeta,
    TransferOptions,
} from './types/Transfer';

interface Props {
    transfers: Transfer[];
    meta: TransferMeta;
    filters: TransferFilters;
    options: TransferOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TransfersIndex({
    transfers: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Traslados',
            href: transferRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Traslados" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <TransferList
                    transfers={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
