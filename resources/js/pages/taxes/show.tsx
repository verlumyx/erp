import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Edit,
    Hash,
    Percent,
    Power,
    Receipt,
    StickyNote,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import taxRoutes from '@/routes/taxes';
import type { BreadcrumbItem } from '@/types';
import type { Tax } from './types/Tax';
import { formatPercentage } from './types/Tax';

interface Props {
    tax: Tax;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function TaxesShow({ tax }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing, errors } = useForm({
        status: tax.status === 'active' ? 'inactive' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Impuestos',
            href: taxRoutes.index(companyId).url,
        },
        {
            title: tax.name,
            href: taxRoutes.show({ company: companyId, id: tax.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(taxRoutes.updateStatus({ company: companyId, id: tax.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={tax.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={taxRoutes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Impuestos
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-2xl bg-primary-soft text-primary">
                            <Percent className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {tax.name}
                                </h1>
                                <StatusPill
                                    kind={
                                        tax.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {tax.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Percent className="size-3.5 opacity-80" />
                                    Impuesto: {formatPercentage(tax.percentage)}
                                    %
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Receipt className="size-3.5 opacity-80" />
                                    Retención:{' '}
                                    {tax.has_withholding === 'yes'
                                        ? `${formatPercentage(tax.withholding_percentage)}%`
                                        : 'No aplica'}
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
                            {tax.status === 'active' ? 'Desactivar' : 'Activar'}
                        </Button>
                        <Link
                            href={
                                taxRoutes.edit({
                                    company: companyId,
                                    id: tax.id,
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

                {errors.status && (
                    <p className="text-sm text-bad">{errors.status}</p>
                )}

                {tax.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {tax.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
