import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import imports from '@/routes/imports';
import type { BreadcrumbItem } from '@/types';
import { ImportList } from './components/ImportList';
import type { Import, ImportFilters, ImportMeta } from './types/Import';

interface Props {
    imports: Import[];
    meta: ImportMeta;
    filters: ImportFilters;
    warehouses: Array<{ id: string; name: string }>;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ImportsIndex({
    imports: rows,
    meta,
    filters,
    warehouses,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Importaciones', href: imports.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Importaciones" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ImportList
                    imports={rows}
                    meta={meta}
                    filters={filters}
                    warehouses={warehouses}
                />
            </div>
        </AppLayout>
    );
}
