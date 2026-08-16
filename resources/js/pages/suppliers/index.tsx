import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import suppliers from '@/routes/suppliers';
import type { BreadcrumbItem } from '@/types';
import { SupplierList } from './components/SupplierList';
import type {
    Supplier,
    SupplierFilters,
    SupplierMeta,
    SupplierOptions,
} from './types/Supplier';

interface Props {
    suppliers: Supplier[];
    meta: SupplierMeta;
    filters: SupplierFilters;
    options: SupplierOptions;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SuppliersIndex({
    suppliers: rows,
    meta,
    filters,
    options,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Proveedores', href: suppliers.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Proveedores" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SupplierList
                    suppliers={rows}
                    meta={meta}
                    filters={filters}
                    options={options}
                />
            </div>
        </AppLayout>
    );
}
