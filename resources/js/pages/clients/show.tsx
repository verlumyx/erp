import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Clock, Edit, Mail, Phone, Power, StickyNote } from 'lucide-react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { WhatsAppAction } from '@/components/whatsapp-button';
import AppLayout from '@/layouts/app-layout';
import { mesesDesde } from '@/lib/crm-demo';
import clients from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';
import type { Client } from './types/Client';

interface Props {
    client: Client;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

export default function ClientsShow({ client }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: client.status === 'active' ? 'inactive' : 'active',
    });

    const tel = client.phone ?? '';
    const estadoCliente =
        client.status === 'inactive' ? 'inactivo' : 'activo';
    const antig = mesesDesde(client.created_at);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: clients.index(companyId).url },
        {
            title: client.name,
            href: clients.show({ company: companyId, id: client.id }).url,
        },
    ];

    const handleToggleStatus = () => {
        put(clients.updateStatus({ company: companyId, id: client.id }).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={client.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clients.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Clientes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <InitialsAvatar name={client.name} size={64} />
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {client.name}
                                </h1>
                                <StatusPill kind={estadoCliente} />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                {tel && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Phone className="size-3.5 opacity-80" />
                                        {tel}
                                    </span>
                                )}
                                {client.email && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Mail className="size-3.5 opacity-80" />
                                        {client.email}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Clock className="size-3.5 opacity-80" />
                                    Cliente hace {antig} mes
                                    {antig !== 1 ? 'es' : ''}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {tel && <WhatsAppAction tel={tel} />}
                        <Button
                            variant="outline"
                            className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                            onClick={handleToggleStatus}
                            disabled={processing}
                        >
                            <Power />
                            {client.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                clients.edit({
                                    company: companyId,
                                    id: client.id,
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

                {client.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Nota
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {client.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
