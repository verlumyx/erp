import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import supplierTypeRoutes from '@/routes/supplier-types';
import type { BreadcrumbItem } from '@/types';
import { SupplierTypeList } from './components/SupplierTypeList';
import type {
    SupplierType,
    SupplierTypeFilters,
    SupplierTypeMeta,
} from './types/SupplierType';

interface Props {
    supplierTypes: SupplierType[];
    meta: SupplierTypeMeta;
    filters: SupplierTypeFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierTypesIndex({
    supplierTypes: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de proveedor',
            href: supplierTypeRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tipos de proveedor" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SupplierTypeList
                    supplierTypes={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
