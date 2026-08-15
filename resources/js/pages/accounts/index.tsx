import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import accounts from '@/routes/accounts';
import type { BreadcrumbItem } from '@/types';
import { AccountList } from './components/AccountList';
import type {
    Account,
    AccountFilters,
    AccountMeta,
    AccountServiceOption,
} from './types/Account';

interface Props {
    accounts: Account[];
    meta: AccountMeta;
    filters: AccountFilters;
    services: AccountServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function AccountsIndex({
    accounts: items,
    meta,
    filters,
    services,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inventario', href: accounts.index(companyId).url },
        { title: 'Cuentas', href: accounts.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cuentas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <AccountList
                    accounts={items}
                    meta={meta}
                    filters={filters}
                    services={services}
                />
            </div>
        </AppLayout>
    );
}
