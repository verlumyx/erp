import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import supplierAdvances from '@/routes/supplier-advances';
import type { BreadcrumbItem } from '@/types';
import { SupplierAdvanceList } from './components/SupplierAdvanceList';
import type {
    SupplierAdvance,
    SupplierAdvanceFilters,
    SupplierAdvanceMeta,
} from './types/SupplierAdvance';

interface Props {
    supplierAdvances: SupplierAdvance[];
    meta: SupplierAdvanceMeta;
    filters: SupplierAdvanceFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierAdvancesIndex({
    supplierAdvances: rows,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos a proveedor',
            href: supplierAdvances.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Anticipos a proveedor" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <SupplierAdvanceList
                    supplierAdvances={rows}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
