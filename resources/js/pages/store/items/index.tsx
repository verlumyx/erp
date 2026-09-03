import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import storeItems from '@/routes/store-items';
import type { BreadcrumbItem } from '@/types';
import { StoreItemList } from '../components/StoreItemList';
import type { ListMeta, StoreItem, StoreItemFilters } from '../types/Store';

interface Props {
    store_items: StoreItem[];
    meta: ListMeta;
    filters: StoreItemFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function StoreItemsIndex({ store_items, meta, filters }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tienda', href: storeItems.index(companyId).url },
        { title: 'Publicaciones', href: storeItems.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Publicaciones" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <StoreItemList
                    storeItems={store_items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
