import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Edit, Hash, Power, StickyNote, Truck } from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import supplierTypes from '@/routes/supplier-types';
import type { BreadcrumbItem } from '@/types';
import type { SupplierType } from './types/SupplierType';

interface Props {
    supplierType: SupplierType;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function SupplierTypesShow({ supplierType }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: supplierType.status === 'active' ? 'inactive' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de proveedor',
            href: supplierTypes.index(companyId).url,
        },
        {
            title: supplierType.name,
            href: supplierTypes.show({
                company: companyId,
                id: supplierType.id,
            }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            supplierTypes.updateStatus({
                company: companyId,
                id: supplierType.id,
            }).url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={supplierType.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierTypes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tipos de proveedor
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-2xl bg-primary-soft text-primary">
                            <Truck className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {supplierType.name}
                                </h1>
                                <StatusPill
                                    kind={
                                        supplierType.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {supplierType.code}
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
                            {supplierType.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                supplierTypes.edit({
                                    company: companyId,
                                    id: supplierType.id,
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

                {supplierType.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {supplierType.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
