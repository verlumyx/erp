import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import transferRoutes from '@/routes/transfers';
import type { BreadcrumbItem } from '@/types';
import { TransferForm } from './components/TransferForm';
import { TransferFormProvider } from './contexts/TransferFormContext';
import { useTransferForm } from './hooks/useTransferForm';
import type { TransferOptions } from './types/Transfer';

interface Props {
    options: TransferOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TransfersCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Traslados',
            href: transferRoutes.index(companyId).url,
        },
        {
            title: 'Nuevo traslado',
            href: transferRoutes.create(companyId).url,
        },
    ];

    const formMethods = useTransferForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo traslado" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={transferRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Traslados
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nuevo traslado
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Mueve mercancía de una bodega a otra sin cambiar su
                        valor
                    </p>
                </div>
                <TransferFormProvider value={{ ...formMethods, options }}>
                    <TransferForm />
                </TransferFormProvider>
            </div>
        </AppLayout>
    );
}
