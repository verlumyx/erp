import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import exchangeRateRoutes from '@/routes/exchange-rates';
import type { BreadcrumbItem } from '@/types';
import { ExchangeRateList } from './components/ExchangeRateList';
import type {
    ExchangeRate,
    ExchangeRateFilters,
    ExchangeRateMeta,
} from './types/ExchangeRate';

interface Props {
    exchangeRates: ExchangeRate[];
    meta: ExchangeRateMeta;
    filters: ExchangeRateFilters;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ExchangeRatesIndex({
    exchangeRates: items,
    meta,
    filters,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tasas',
            href: exchangeRateRoutes.index(companyId).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tasas" />
            <div className="mx-auto w-full max-w-7xl p-6 pb-14">
                <ExchangeRateList
                    exchangeRates={items}
                    meta={meta}
                    filters={filters}
                />
            </div>
        </AppLayout>
    );
}
