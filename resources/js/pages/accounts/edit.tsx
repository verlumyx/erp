import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import accounts from '@/routes/accounts';
import type { BreadcrumbItem } from '@/types';
import { AccountForm } from './components/AccountForm';
import { AccountFormProvider } from './contexts/AccountFormContext';
import { useAccountForm } from './hooks/useAccountForm';
import type { Account, AccountServiceOption } from './types/Account';

interface Props {
    account: Account;
    services: AccountServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function AccountsEdit({ account, services }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Cuentas', href: accounts.index(companyId).url },
        {
            title: account.code,
            href: accounts.show({ company: companyId, id: account.id }).url,
        },
        {
            title: 'Editar',
            href: accounts.edit({ company: companyId, id: account.id }).url,
        },
    ];

    const formMethods = useAccountForm({
        mode: 'edit',
        services,
        initialData: account,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${account.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        accounts.show({ company: companyId, id: account.id })
                            .url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {account.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar cuenta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica la cabecera y los perfiles de la cuenta
                    </p>
                </div>
                <AccountFormProvider value={formMethods}>
                    <AccountForm />
                </AccountFormProvider>
            </div>
        </AppLayout>
    );
}
