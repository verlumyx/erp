import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import entries from '@/routes/entries';
import type { BreadcrumbItem } from '@/types';
import { EntryList } from './components/EntryList';
import type { Entry, EntryFilters, EntryMeta } from './types/Entry';

interface Props {
    entries: Entry[];
    meta: EntryMeta;
    filters: EntryFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function EntriesIndex({ entries: rows, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Entradas',
            href: entries.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Entradas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <EntryList entries={rows} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
