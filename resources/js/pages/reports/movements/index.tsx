import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import reports from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { MovementList } from './components/MovementList';
import type { Movement, MovementFilters, MovementMeta } from './types/Movement';

interface Props {
    movements: Movement[];
    meta: MovementMeta;
    filters: MovementFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function MovementsReport({ movements, meta, filters, searched }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reportes', href: reports.movements.index(companyId).url },
        { title: 'Movimientos', href: reports.movements.index(companyId).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Movimientos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <MovementList
                    movements={movements}
                    meta={meta}
                    filters={filters}
                    searched={searched}
                />
            </div>
        </AppLayout>
    );
}
