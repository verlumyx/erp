import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Contact,
    Edit,
    Globe,
    Hash,
    Mail,
    MapPin,
    Phone,
    Power,
    StickyNote,
    Truck,
} from 'lucide-react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import suppliers from '@/routes/suppliers';
import type { BreadcrumbItem } from '@/types';
import {
    ADDRESS_TYPE_LABELS,
    DOCUMENT_TYPE_LABELS,
    formatDocument,
    type Supplier,
} from './types/Supplier';

interface Props {
    supplier: Supplier;
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

export default function SuppliersShow({ supplier }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { put, processing } = useForm({
        status: supplier.status === 'active' ? 'inactive' : 'active',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Proveedores', href: suppliers.index(companyId).url },
        {
            title: supplier.name,
            href: suppliers.show({ company: companyId, id: supplier.id }).url,
        },
    ];

    const activeContacts = (supplier.contacts ?? []).filter(
        (contact) => contact.status === 'active',
    );
    const activeAddresses = (supplier.addresses ?? []).filter(
        (address) => address.status === 'active',
    );

    const handleToggleStatus = () => {
        put(
            suppliers.updateStatus({ company: companyId, id: supplier.id }).url,
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={supplier.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={suppliers.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Proveedores
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {supplier.name}
                            </h1>
                            <StatusPill
                                kind={
                                    supplier.status === 'inactive'
                                        ? 'inactivo'
                                        : 'activo'
                                }
                            />
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Hash className="size-3.5 opacity-80" />
                                {supplier.code}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Contact className="size-3.5 opacity-80" />
                                {formatDocument(
                                    supplier.document_type,
                                    supplier.document_number,
                                )}
                            </span>
                            {supplier.supplier_type_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Truck className="size-3.5 opacity-80" />
                                    {supplier.supplier_type_name}
                                </span>
                            )}
                            {supplier.email && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Mail className="size-3.5 opacity-80" />
                                    {supplier.email}
                                </span>
                            )}
                            {supplier.phone && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Phone className="size-3.5 opacity-80" />
                                    {supplier.phone}
                                </span>
                            )}
                            {supplier.website && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Globe className="size-3.5 opacity-80" />
                                    {supplier.website}
                                </span>
                            )}
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
                            {supplier.status === 'active'
                                ? 'Desactivar'
                                : 'Activar'}
                        </Button>
                        <Link
                            href={
                                suppliers.edit({
                                    company: companyId,
                                    id: supplier.id,
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
                            label="Nombre legal"
                            value={supplier.legal_name ?? '—'}
                        />
                        <DataRow
                            label="Contribuyente"
                            value={DOCUMENT_TYPE_LABELS[supplier.document_type]}
                        />
                        <DataRow
                            label="Dirección fiscal"
                            value={supplier.address ?? '—'}
                        />
                        <DataRow
                            label="Ciudad / Estado"
                            value={`${supplier.city ?? '—'} / ${supplier.state ?? '—'}`}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Condiciones comerciales
                        </div>
                        <DataRow label="Moneda" value={supplier.currency} />
                        <DataRow
                            label="Días de crédito"
                            value={
                                supplier.payment_term_days === 0
                                    ? 'Contado'
                                    : String(supplier.payment_term_days)
                            }
                        />
                        <DataRow
                            label="Límite de crédito"
                            value={supplier.credit_limit}
                        />
                        <DataRow
                            label="Saldo por pagar"
                            value={supplier.current_balance}
                        />
                        <DataRow
                            label="Anticipos disponibles"
                            value={supplier.advance_balance}
                        />
                        <DataRow
                            label="Días de entrega"
                            value={String(supplier.lead_time_days)}
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
                                El proveedor no tiene contactos activos.
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
                                El proveedor no tiene direcciones activas.
                            </div>
                        )}
                    </div>
                </Card>

                {supplier.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {supplier.notes}
                        </p>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
