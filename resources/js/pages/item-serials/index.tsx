import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import itemSerials from '@/routes/item-serials';
import type { BreadcrumbItem } from '@/types';
import { ItemSerialList } from './components/ItemSerialList';
import type {
    ItemSerial,
    ItemSerialFilters,
    ItemSerialMeta,
    WarehouseOption,
} from './types/ItemSerial';

interface Props {
    serials: ItemSerial[];
    warehouses: WarehouseOption[];
    meta: ItemSerialMeta;
    filters: ItemSerialFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ItemSerialsIndex({
    serials,
    warehouses,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Series', href: itemSerials.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Series" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ItemSerialList
                    serials={serials}
                    warehouses={warehouses}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
