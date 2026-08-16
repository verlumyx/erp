import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    ContactRound,
    Edit,
    Hash,
    Power,
    StickyNote,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import clientTypes from '@/routes/client-types';
import type { BreadcrumbItem } from '@/types';
import type { ClientType } from './types/ClientType';

interface Props {
    clientType: ClientType;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export default function ClientTypesShow({ clientType }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: clientType.status === 'active' ? 'inactive' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Tipos de cliente',
            href: clientTypes.index(companyId).url,
        },
        {
            title: clientType.name,
            href: clientTypes.show({
                company: companyId,
                id: clientType.id,
            }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(
            clientTypes.updateStatus({
                company: companyId,
                id: clientType.id,
            }).url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={clientType.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientTypes.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Tipos de cliente
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <span className="grid size-16 shrink-0 place-items-center rounded-2xl bg-primary-soft text-primary">
                            <ContactRound className="size-7" />
                        </span>
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {clientType.name}
                                </h1>
                                <StatusPill
                                    kind={
                                        clientType.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {clientType.code}
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
                            {clientType.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                clientTypes.edit({
                                    company: companyId,
                                    id: clientType.id,
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

                {clientType.description && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Descripción
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {clientType.description}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
