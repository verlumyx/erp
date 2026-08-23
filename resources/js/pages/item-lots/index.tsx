import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import itemLots from '@/routes/item-lots';
import type { BreadcrumbItem } from '@/types';
import { ItemLotList } from './components/ItemLotList';
import type { ItemLot, ItemLotFilters, ItemLotMeta } from './types/ItemLot';

interface Props {
    lots: ItemLot[];
    meta: ItemLotMeta;
    filters: ItemLotFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemLotsIndex({ lots, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Lotes', href: itemLots.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lotes" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ItemLotList lots={lots} meta={meta} filters={filters} />
            </div>
        </AppLayout>
    );
}
