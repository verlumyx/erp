import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import purchaseCreditNotes from '@/routes/purchase-credit-notes';
import type { BreadcrumbItem } from '@/types';
import { PurchaseCreditNoteList } from './components/PurchaseCreditNoteList';
import type {
    PurchaseCreditNote,
    PurchaseCreditNoteFilters,
    PurchaseCreditNoteMeta,
} from './types/PurchaseCreditNote';

interface Props {
    purchaseCreditNotes: PurchaseCreditNote[];
    meta: PurchaseCreditNoteMeta;
    filters: PurchaseCreditNoteFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PurchaseCreditNotesIndex({
    purchaseCreditNotes: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Notas de crédito a proveedor',
            href: purchaseCreditNotes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notas de crédito a proveedor" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PurchaseCreditNoteList
                    purchaseCreditNotes={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
