import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import reports from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import { IncomeExpenseReport } from './components/IncomeExpenseReport';
import type {
    IncomeExpenseFilters,
    IncomeExpenseMeta,
    IncomeExpenseSummary,
    Movement,
} from './types/IncomeExpense';

interface Props {
    movements: Movement[];
    summary: IncomeExpenseSummary;
    meta: IncomeExpenseMeta;
    filters: IncomeExpenseFilters;
    searched: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function IncomeExpensesReport({
    movements,
    summary,
    meta,
    filters,
    searched,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Reportes', href: reports.incomeExpenses.index(companyId).url },
        {
            title: 'Ingresos y Gastos',
            href: reports.incomeExpenses.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ingresos y Gastos" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <IncomeExpenseReport
                    movements={movements}
                    summary={summary}
                    meta={meta}
                    filters={filters}
                    searched={searched}
                />
            </div>
        </AppLayout>
    );
}
