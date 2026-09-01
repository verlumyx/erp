import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import salesReturns from '@/routes/sales-returns';
import type { BreadcrumbItem } from '@/types';
import { SalesReturnList } from './components/SalesReturnList';
import type {
    SalesReturn,
    SalesReturnFilters,
    SalesReturnMeta,
} from './types/SalesReturn';

interface Props {
    salesReturns: SalesReturn[];
    meta: SalesReturnMeta;
    filters: SalesReturnFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesReturnsIndex({
    salesReturns: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de ventas',
            href: salesReturns.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Devoluciones de ventas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SalesReturnList
                    salesReturns={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
