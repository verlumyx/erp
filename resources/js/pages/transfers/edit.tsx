import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import transferRoutes from '@/routes/transfers';
import type { BreadcrumbItem } from '@/types';
import { TransferForm } from './components/TransferForm';
import { TransferFormProvider } from './contexts/TransferFormContext';
import { useTransferForm } from './hooks/useTransferForm';
import type { Transfer, TransferOptions } from './types/Transfer';

interface Props {
    transfer: Transfer;
    options: TransferOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TransfersEdit({ transfer, options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Traslados',
            href: transferRoutes.index(companyId).url,
        },
        {
            title: transfer.code,
            href: transferRoutes.show({
                company: companyId,
                id: transfer.id,
            }).url,
        },
        {
            title: 'Editar',
            href: transferRoutes.edit({
                company: companyId,
                id: transfer.id,
            }).url,
        },
    ];

    const formMethods = useTransferForm({
        mode: 'edit',
        options,
        initialData: transfer,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${transfer.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        transferRoutes.show({
                            company: companyId,
                            id: transfer.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {transfer.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar traslado
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras el traslado está en borrador
                    </p>
                </div>
                <TransferFormProvider value={{ ...formMethods, options }}>
                    <TransferForm />
                </TransferFormProvider>
            </div>
        </AppLayout>
    );
}
