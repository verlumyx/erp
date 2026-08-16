import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Coins,
    Edit,
    Hash,
    Landmark,
    Power,
    StickyNote,
} from 'lucide-react';
import { useCurrencies } from '@/components/currency-select';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import exchangeRates from '@/routes/exchange-rates';
import type { BreadcrumbItem } from '@/types';
import type { ExchangeRate } from './types/ExchangeRate';
import { TYPE_LABELS } from './types/ExchangeRate';

interface Props {
    exchangeRate: ExchangeRate;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ExchangeRatesShow({ exchangeRate }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const currencies = useCurrencies();
    const currencyLabel =
        currencies.find((currency) => currency.code === exchangeRate.currency)
            ?.name ?? exchangeRate.currency;

    const title = `${exchangeRate.currency} · ${exchangeRate.rate_date}`;

    const { put, processing } = useForm({
        status: exchangeRate.status === 'active' ? 'inactive' : 'active',
    });

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
    ];

    const handleToggleStatus = () => {
        put(
            exchangeRates.updateStatus({
                company: companyId,
                id: exchangeRate.id,
            }).url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={exchangeRates.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tasas
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-2xl bg-primary-soft text-primary">
                            <Coins className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight tabular-nums">
                                    {exchangeRate.rate}
                                </h1>
                                <StatusPill
                                    kind={
                                        exchangeRate.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {exchangeRate.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Coins className="size-3.5 opacity-80" />
                                    {currencyLabel}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <CalendarDays className="size-3.5 opacity-80" />
                                    {exchangeRate.rate_date}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Landmark className="size-3.5 opacity-80" />
                                    Tasa {TYPE_LABELS[exchangeRate.type]}
                                    {exchangeRate.source
                                        ? ` · ${exchangeRate.source}`
                                        : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {exchangeRate.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                exchangeRates.edit({
                                    company: companyId,
                                    id: exchangeRate.id,
                                }).url
                            }
                        >
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            >
                                <Edit />
                                Editar
                            </Button>
                        </Link>
                    </div>
                </Card>

                {exchangeRate.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {exchangeRate.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
