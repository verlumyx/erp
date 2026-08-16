import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import items from '@/routes/items';
import type { BreadcrumbItem } from '@/types';
import { ItemList } from './components/ItemList';
import type { Item, ItemFilters, ItemMeta, ItemOptions } from './types/Item';

interface Props {
    items: Item[];
    meta: ItemMeta;
    filters: ItemFilters;
    options: ItemOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemsIndex({
    items: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Catálogo de artículos', href: items.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Catálogo de artículos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ItemList
                    items={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
