import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Contact,
    ContactRound,
    Edit,
    Hash,
    Mail,
    MapPin,
    Phone,
    Power,
    StickyNote,
} from 'lucide-react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { WhatsAppAction } from '@/components/whatsapp-button';
import AppLayout from '@/layouts/app-layout';
import { mesesDesde } from '@/lib/crm-demo';
import clients from '@/routes/clients';
import type { BreadcrumbItem } from '@/types';
import {
    ADDRESS_TYPE_LABELS,
    DOCUMENT_TYPE_LABELS,
    formatDocument,
    type Client,
} from './types/Client';

interface Props {
    client: Client;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function ClientsShow({ client }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: client.status === 'active' ? 'inactive' : 'active',
    });

    const tel = client.phone ?? '';
    const antig = mesesDesde(client.created_at);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: clients.index(companyId).url },
        {
            title: client.name,
            href: clients.show({ company: companyId, id: client.id }).url,
        },
    ];

    const activeContacts = (client.contacts ?? []).filter(
        (contact) => contact.status === 'active',
    );
    const activeAddresses = (client.addresses ?? []).filter(
        (address) => address.status === 'active',
    );

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
                                <StatusPill
                                    kind={
                                        client.status === 'inactive'
                                            ? 'inactivo'
                                            : 'activo'
                                    }
                                />
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {client.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Contact className="size-3.5 opacity-80" />
                                    {formatDocument(
                                        client.document_type,
                                        client.document_number,
                                    )}
                                </span>
                                {client.client_type_name && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <ContactRound className="size-3.5 opacity-80" />
                                        {client.client_type_name}
                                    </span>
                                )}
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

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Identificación
                        </div>
                        <DataRow
                            label="Razón social"
                            value={client.legal_name ?? '—'}
                        />
                        <DataRow
                            label="Contribuyente"
                            value={DOCUMENT_TYPE_LABELS[client.document_type]}
                        />
                        <DataRow
                            label="Dirección fiscal"
                            value={client.address ?? '—'}
                        />
                        <DataRow
                            label="Ciudad / Estado"
                            value={`${client.city ?? '—'} / ${client.state ?? '—'}`}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Condiciones comerciales
                        </div>
                        <DataRow
                            label="Lista de precio"
                            value={
                                client.price_list_name ??
                                'Lista por defecto de la empresa'
                            }
                        />
                        <DataRow
                            label="Días de crédito"
                            value={
                                client.payment_term_days === 0
                                    ? 'Contado'
                                    : String(client.payment_term_days)
                            }
                        />
                        <DataRow
                            label="Límite de crédito"
                            value={client.credit_limit}
                        />
                        <DataRow
                            label="Saldo por cobrar"
                            value={client.current_balance}
                        />
                        <DataRow
                            label="Anticipos disponibles"
                            value={client.advance_balance}
                        />
                        <DataRow
                            label="Descuento fijo"
                            value={`${client.discount_percent}%`}
                        />
                        <DataRow
                            label="Crédito bloqueado"
                            value={
                                client.credit_blocked === 'yes' ? 'Sí' : 'No'
                            }
                        />
                        <DataRow
                            label="Vendedor"
                            value={client.salesperson_name ?? '—'}
                        />
                    </Card>
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <Contact className="size-[15px]" />
                        Contactos
                    </div>
                    <div className="flex flex-col">
                        {activeContacts.map((contact) => (
                            <div
                                key={contact.id}
                                className="flex flex-wrap items-center justify-between gap-4 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {contact.name}
                                        {contact.position
                                            ? ` · ${contact.position}`
                                            : ''}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {[contact.email, contact.phone]
                                            .filter(Boolean)
                                            .join(' · ') ||
                                            'Sin datos de contacto'}
                                    </span>
                                </div>
                                {contact.is_primary === 'yes' && (
                                    <span className="rounded-full bg-primary-soft px-2.5 py-1 text-[12.5px] font-bold text-primary">
                                        Principal
                                    </span>
                                )}
                            </div>
                        ))}
                        {activeContacts.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El cliente no tiene contactos activos.
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="flex items-center gap-2 border-b p-5 text-[13px] font-bold text-muted-foreground">
                        <MapPin className="size-[15px]" />
                        Direcciones
                    </div>
                    <div className="flex flex-col">
                        {activeAddresses.map((address) => (
                            <div
                                key={address.id}
                                className="flex flex-wrap items-center justify-between gap-4 border-b px-5 py-3 last:border-b-0"
                            >
                                <div className="flex min-w-0 flex-col">
                                    <span className="truncate font-bold">
                                        {address.name}
                                        {' · '}
                                        {ADDRESS_TYPE_LABELS[address.type]}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {[
                                            address.address,
                                            address.city,
                                            address.state,
                                            address.country,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </span>
                                </div>
                                {address.is_default === 'yes' && (
                                    <span className="rounded-full bg-primary-soft px-2.5 py-1 text-[12.5px] font-bold text-primary">
                                        Predeterminada
                                    </span>
                                )}
                            </div>
                        ))}
                        {activeAddresses.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El cliente no tiene direcciones activas.
                            </div>
                        )}
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
