import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import manualTransactions from '@/routes/manual-transactions';
import type { BreadcrumbItem } from '@/types';
import { ManualTransactionList } from './components/ManualTransactionList';
import type {
    ManualTransaction,
    ManualTransactionFilters,
    ManualTransactionMeta,
} from './types/ManualTransaction';

interface Props {
    manualTransactions: ManualTransaction[];
    meta: ManualTransactionMeta;
    filters: ManualTransactionFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ManualTransactionsIndex({
    manualTransactions: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Transacciones manuales',
            href: manualTransactions.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transacciones manuales" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ManualTransactionList
                    manualTransactions={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
