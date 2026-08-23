import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import purchaseCreditNotes from '@/routes/purchase-credit-notes';
import type { BreadcrumbItem } from '@/types';
import { PurchaseCreditNoteForm } from './components/PurchaseCreditNoteForm';
import { PurchaseCreditNoteFormProvider } from './contexts/PurchaseCreditNoteFormContext';
import { usePurchaseCreditNoteForm } from './hooks/usePurchaseCreditNoteForm';
import type {
    PurchaseCreditNote,
    PurchaseCreditNoteOptions,
} from './types/PurchaseCreditNote';

interface Props {
    purchaseCreditNote: PurchaseCreditNote;
    options: PurchaseCreditNoteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseCreditNotesEdit({
    purchaseCreditNote,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a proveedor',
            href: purchaseCreditNotes.index(companyId).url,
        },
        {
            title: purchaseCreditNote.code,
            href: purchaseCreditNotes.show({
                company: companyId,
                id: purchaseCreditNote.id,
            }).url,
        },
        {
            title: 'Editar',
            href: purchaseCreditNotes.edit({
                company: companyId,
                id: purchaseCreditNote.id,
            }).url,
        },
    ];

    const formMethods = usePurchaseCreditNoteForm({
        mode: 'edit',
        options,
        initialData: purchaseCreditNote,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${purchaseCreditNote.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        purchaseCreditNotes.show({
                            company: companyId,
                            id: purchaseCreditNote.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {purchaseCreditNote.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar nota de crédito
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la nota está en borrador
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
