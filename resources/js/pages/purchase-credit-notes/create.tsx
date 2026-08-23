import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseCreditNotes from '@/routes/purchase-credit-notes';
import type { BreadcrumbItem } from '@/types';
import { PurchaseCreditNoteForm } from './components/PurchaseCreditNoteForm';
import { PurchaseCreditNoteFormProvider } from './contexts/PurchaseCreditNoteFormContext';
import { usePurchaseCreditNoteForm } from './hooks/usePurchaseCreditNoteForm';
import type { PurchaseCreditNoteOptions } from './types/PurchaseCreditNote';

interface Props {
    options: PurchaseCreditNoteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseCreditNotesCreate({ options }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a proveedor',
            href: purchaseCreditNotes.index(companyId).url,
        },
        {
            title: 'Nueva nota',
            href: purchaseCreditNotes.create(companyId).url,
        },
    ];

    const formMethods = usePurchaseCreditNoteForm({ mode: 'create', options });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva nota de crédito a proveedor" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={purchaseCreditNotes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Notas de crédito a proveedor
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva nota de crédito
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra el crédito que el proveedor reconoce y que
                        disminuye la deuda con él
                    </p>
                </div>
                <PurchaseCreditNoteFormProvider
                    value={{ ...formMethods, options }}
                >
                    <PurchaseCreditNoteForm />
                </PurchaseCreditNoteFormProvider>
            </div>
        </AppLayout>
    );
}
