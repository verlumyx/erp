import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import taxRoutes from '@/routes/taxes';
import type { BreadcrumbItem } from '@/types';
import { TaxList } from './components/TaxList';
import type { Tax, TaxFilters, TaxMeta } from './types/Tax';

interface Props {
    taxes: Tax[];
    meta: TaxMeta;
    filters: TaxFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TaxesIndex({ taxes: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Impuestos',
            href: taxRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Impuestos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <TaxList taxes={items} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
