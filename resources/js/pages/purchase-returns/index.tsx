import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import purchaseReturns from '@/routes/purchase-returns';
import type { BreadcrumbItem } from '@/types';
import { PurchaseReturnList } from './components/PurchaseReturnList';
import type {
    PurchaseReturn,
    PurchaseReturnFilters,
    PurchaseReturnMeta,
} from './types/PurchaseReturn';

interface Props {
    purchaseReturns: PurchaseReturn[];
    meta: PurchaseReturnMeta;
    filters: PurchaseReturnFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseReturnsIndex({
    purchaseReturns: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Devoluciones de compras',
            href: purchaseReturns.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Devoluciones de compras" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PurchaseReturnList
                    purchaseReturns={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
