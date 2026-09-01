import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import salesCreditNotes from '@/routes/sales-credit-notes';
import type { BreadcrumbItem } from '@/types';
import { SalesCreditNoteList } from './components/SalesCreditNoteList';
import type {
    SalesCreditNote,
    SalesCreditNoteFilters,
    SalesCreditNoteMeta,
} from './types/SalesCreditNote';

interface Props {
    salesCreditNotes: SalesCreditNote[];
    meta: SalesCreditNoteMeta;
    filters: SalesCreditNoteFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SalesCreditNotesIndex({
    salesCreditNotes: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a cliente',
            href: salesCreditNotes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notas de crédito a cliente" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SalesCreditNoteList
                    salesCreditNotes={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
