import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import manualTransactions from '@/routes/manual-transactions';
import type { BreadcrumbItem } from '@/types';
import { ManualTransactionForm } from './components/ManualTransactionForm';
import { ManualTransactionFormProvider } from './contexts/ManualTransactionFormContext';
import { useManualTransactionForm } from './hooks/useManualTransactionForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ManualTransactionsCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const formState = useManualTransactionForm();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Transacciones manuales',
            href: manualTransactions.index(companyId).url,
        },
        {
            title: 'Nueva transacción',
            href: manualTransactions.create(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva transacción manual" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={manualTransactions.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Transacciones manuales
                </Link>

                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva transacción manual
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra una cabecera con una o más líneas tipadas por
                        categoría.
                    </p>
                </div>

                <Card className="rounded-2xl p-6">
                    <ManualTransactionFormProvider value={formState}>
                        <ManualTransactionForm
                            onCancel={() =>
                                router.visit(
                                    manualTransactions.index(companyId).url,
                                )
                            }
                        />
                    </ManualTransactionFormProvider>
                </Card>
            </div>
        </AppLayout>
    );
}
