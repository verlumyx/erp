import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import accounts from '@/routes/accounts';
import type { BreadcrumbItem } from '@/types';
import { AccountForm } from './components/AccountForm';
import { AccountFormProvider } from './contexts/AccountFormContext';
import { useAccountForm } from './hooks/useAccountForm';
import type { AccountServiceOption } from './types/Account';

interface Props {
    services: AccountServiceOption[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function AccountsCreate({ services }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Cuentas', href: accounts.index(companyId).url },
        { title: 'Nueva cuenta', href: accounts.create(companyId).url },
    ];

    const formMethods = useAccountForm({ mode: 'create', services });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva cuenta" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={accounts.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Cuentas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva cuenta
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra una cuenta de streaming y sus perfiles
                    </p>
                </div>
                <AccountFormProvider value={formMethods}>
                    <AccountForm />
                </AccountFormProvider>
            </div>
        </AppLayout>
    );
}
