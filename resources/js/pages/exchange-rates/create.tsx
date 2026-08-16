import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import exchangeRates from '@/routes/exchange-rates';
import type { BreadcrumbItem } from '@/types';
import { ExchangeRateForm } from './components/ExchangeRateForm';
import { ExchangeRateFormProvider } from './contexts/ExchangeRateFormContext';
import { useExchangeRateForm } from './hooks/useExchangeRateForm';

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ExchangeRatesCreate() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tasas',
            href: exchangeRates.index(companyId).url,
        },
        {
            title: 'Nueva tasa',
            href: exchangeRates.create(companyId).url,
        },
    ];

    const formMethods = useExchangeRateForm({ mode: 'create' });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva tasa" />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={exchangeRates.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tasas
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Nueva tasa
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Registra el valor de la tasa para una moneda y fecha
                    </p>
                </div>
                <ExchangeRateFormProvider value={formMethods}>
                    <ExchangeRateForm />
                </ExchangeRateFormProvider>
            </div>
        </AppLayout>
    );
}
