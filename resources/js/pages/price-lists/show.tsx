import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Edit, Hash, Power, StickyNote } from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import priceLists from '@/routes/price-lists';
import type { BreadcrumbItem } from '@/types';
import type { PriceList } from './types/PriceList';

interface Props {
    priceList: PriceList;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function PriceListsShow({ priceList }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: priceList.status === 'active' ? 'inactive' : 'active',
    });

    const estadoLista = priceList.status === 'inactive' ? 'inactivo' : 'activo';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Listas de precio', href: priceLists.index(companyId).url },
        {
            title: priceList.name,
            href: priceLists.show({ company: companyId, id: priceList.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            priceLists.updateStatus({ company: companyId, id: priceList.id })
                .url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={priceList.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={priceLists.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Listas de precio
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {priceList.name}
                            </h1>
                            <StatusPill kind={estadoLista} />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {priceList.code}
                            </span>
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
                            {priceList.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                priceLists.edit({
                                    company: companyId,
                                    id: priceList.id,
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

                {priceList.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {priceList.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
