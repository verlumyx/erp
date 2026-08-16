import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import priceLists from '@/routes/price-lists';
import type { BreadcrumbItem } from '@/types';
import { PriceListList } from './components/PriceListList';
import type {
    PriceList,
    PriceListFilters,
    PriceListMeta,
} from './types/PriceList';

interface Props {
    priceLists: PriceList[];
    meta: PriceListMeta;
    filters: PriceListFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PriceListsIndex({
    priceLists: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Listas de precio', href: priceLists.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Listas de precio" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <PriceListList
                    priceLists={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
