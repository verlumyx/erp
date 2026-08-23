import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import itemStocks from '@/routes/item-stocks';
import type { BreadcrumbItem } from '@/types';
import { ItemStockList } from './components/ItemStockList';
import type {
    ItemStock,
    ItemStockFilters,
    ItemStockMeta,
    WarehouseOption,
} from './types/ItemStock';

interface Props {
    stocks: ItemStock[];
    warehouses: WarehouseOption[];
    meta: ItemStockMeta;
    filters: ItemStockFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemStocksIndex({
    stocks,
    warehouses,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Existencias', href: itemStocks.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Existencias" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ItemStockList
                    stocks={stocks}
                    warehouses={warehouses}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
