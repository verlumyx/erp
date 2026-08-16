import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import exchangeRates from '@/routes/exchange-rates';
import type { BreadcrumbItem } from '@/types';
import { ExchangeRateForm } from './components/ExchangeRateForm';
import { ExchangeRateFormProvider } from './contexts/ExchangeRateFormContext';
import { useExchangeRateForm } from './hooks/useExchangeRateForm';
import type { ExchangeRate } from './types/ExchangeRate';

interface Props {
    exchangeRate: ExchangeRate;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ExchangeRatesEdit({ exchangeRate }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const title = `${exchangeRate.currency} · ${exchangeRate.rate_date}`;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tasas',
            href: exchangeRates.index(companyId).url,
        },
        {
            title,
            href: exchangeRates.show({
                company: companyId,
                id: exchangeRate.id,
            }).url,
        },
        {
            title: 'Editar',
            href: exchangeRates.edit({
                company: companyId,
                id: exchangeRate.id,
            }).url,
        },
    ];

    const formMethods = useExchangeRateForm({
        mode: 'edit',
        initialData: exchangeRate,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${title}`} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={
                        exchangeRates.show({
                            company: companyId,
                            id: exchangeRate.id,
                        }).url
                    }
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    {title}
                </Link>
                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar tasa
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica el valor, la fuente o la descripción
                    </p>
                </div>
                <ExchangeRateFormProvider value={formMethods}>
                    <ExchangeRateForm />
                </ExchangeRateFormProvider>
            </div>
        </AppLayout>
    );
}
