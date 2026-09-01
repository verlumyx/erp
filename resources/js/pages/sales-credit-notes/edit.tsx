import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import salesCreditNotes from '@/routes/sales-credit-notes';
import type { BreadcrumbItem } from '@/types';
import { SalesCreditNoteForm } from './components/SalesCreditNoteForm';
import { SalesCreditNoteFormProvider } from './contexts/SalesCreditNoteFormContext';
import { useSalesCreditNoteForm } from './hooks/useSalesCreditNoteForm';
import type {
    SalesCreditNote,
    SalesCreditNoteOptions,
} from './types/SalesCreditNote';

interface Props {
    salesCreditNote: SalesCreditNote;
    options: SalesCreditNoteOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesCreditNotesEdit({
    salesCreditNote,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a cliente',
            href: salesCreditNotes.index(companyId).url,
        },
        {
            title: salesCreditNote.code,
            href: salesCreditNotes.show({
                company: companyId,
                id: salesCreditNote.id,
            }).url,
        },
        {
            title: 'Editar',
            href: salesCreditNotes.edit({
                company: companyId,
                id: salesCreditNote.id,
            }).url,
        },
    ];

    const formMethods = useSalesCreditNoteForm({
        mode: 'edit',
        options,
        initialData: salesCreditNote,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${salesCreditNote.code}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        salesCreditNotes.show({
                            company: companyId,
                            id: salesCreditNote.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {salesCreditNote.code}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar nota de crédito
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Solo se edita mientras la nota está en borrador
                    </p>
                </div>
                <SalesCreditNoteFormProvider value={{ ...formMethods, options }}>
                    <SalesCreditNoteForm />
                </SalesCreditNoteFormProvider>
            </div>
        </AppLayout>
    );
}
