import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import refunds from '@/routes/refunds';
import type { BreadcrumbItem } from '@/types';
import { RefundList } from './components/RefundList';
import type { Refund, RefundFilters, RefundMeta } from './types/Refund';

interface Props {
    refunds: Refund[];
    meta: RefundMeta;
    filters: RefundFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function RefundsIndex({ refunds: items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reembolsos', href: refunds.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reembolsos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <RefundList refunds={items} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
