import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    Contact,
    ExternalLink,
    Hash,
    Link2,
    Mail,
    Phone,
    Power,
} from 'lucide-react';
import { useState } from 'react';
import { InitialsAvatar } from '@/components/initials-avatar';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import clients from '@/routes/clients';
import storeCustomers from '@/routes/store-customers';
import type { BreadcrumbItem } from '@/types';
import { StoreCustomerLinkDialog } from '../components/StoreCustomerLinkDialog';
import { StoreOrderList } from '../components/StoreOrderList';
import { useStorePermissions } from '../hooks/useStorePermissions';
import {
    CUSTOMER_STATUS_LABELS,
    CUSTOMER_STATUS_PILL,
    formatDocument,
    LINK_SOURCE_LABELS,
    type StoreCustomer,
    type StoreOrder,
} from '../types/Store';

interface Props {
    store_customer: StoreCustomer;
    store_orders: StoreOrder[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

export default function StoreCustomersShow({
    store_customer: customer,
    store_orders,
}: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const [linking, setLinking] = useState<StoreCustomer | null>(null);

    const toggleStatus = () =>
        router.put(
            storeCustomers.updateStatus({ company: companyId, id: customer.id })
                .url,
            { status: customer.status === 'inactive' ? 'active' : 'inactive' },
            { preserveScroll: true },
        );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Compradores', href: storeCustomers.index(companyId).url },
        {
            title: customer.code,
            href: storeCustomers.show({ company: companyId, id: customer.id })
                .url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={customer.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={storeCustomers.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Compradores
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex items-center gap-[18px]">
                        <InitialsAvatar name={customer.name} size={64} />
                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-extrabold tracking-tight">
                                    {customer.name}
                                </h1>
                                <StatusPill
                                    kind={CUSTOMER_STATUS_PILL[customer.status]}
                                >
                                    {CUSTOMER_STATUS_LABELS[customer.status]}
                                </StatusPill>
                            </div>
                            <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {customer.code}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Mail className="size-3.5 opacity-80" />
                                    {customer.email}
                                </span>
                                {customer.phone && (
                                    <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                        <Phone className="size-3.5 opacity-80" />
                                        {customer.phone}
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Contact className="size-3.5 opacity-80" />
                                    {formatDocument(
                                        customer.document_type,
                                        customer.document_number,
                                    )}
                                </span>
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Clock className="size-3.5 opacity-80" />
                                    Último acceso:{' '}
                                    {customer.last_login_at ?? 'nunca'}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {can('store-customers.link') && !customer.client_id && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                onClick={() => setLinking(customer)}
                            >
                                <Link2 />
                                Vincular
                            </Button>
                        )}
                        {can('store-customers.update-status') &&
                            customer.status !== 'invited' && (
                                <Button
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                    onClick={toggleStatus}
                                >
                                    <Power />
                                    {customer.status === 'inactive'
                                        ? 'Activar'
                                        : 'Bloquear'}
                                </Button>
                            )}
                    </div>
                </Card>

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Vínculo con el cliente
                        </div>
                        <DataRow
                            label="Cliente"
                            value={
                                customer.client ? (
                                    <Link
                                        href={
                                            clients.show({
                                                company: companyId,
                                                id: customer.client.id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1 text-primary hover:underline"
                                    >
                                        {customer.client.code} —{' '}
                                        {customer.client.name}
                                        <ExternalLink className="size-3.5" />
                                    </Link>
                                ) : (
                                    'Sin vincular'
                                )
                            }
                        />
                        <DataRow
                            label="Cómo se vinculó"
                            value={
                                customer.link_source
                                    ? LINK_SOURCE_LABELS[customer.link_source]
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Vinculado el"
                            value={customer.linked_at ?? '—'}
                        />
                        <DataRow
                            label="Vinculado por"
                            value={customer.linker?.name ?? 'Automático'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Cuenta
                        </div>
                        <DataRow
                            label="Registrado el"
                            value={customer.created_at ?? '—'}
                        />
                        <DataRow
                            label="Correo verificado"
                            value={customer.email_verified_at ?? 'No'}
                        />
                        {customer.status === 'invited' && (
                            <DataRow
                                label="Invitación vence"
                                value={customer.invitation_expires_at ?? '—'}
                            />
                        )}
                        <DataRow
                            label="Pedidos web"
                            value={String(store_orders.length)}
                        />
                    </Card>
                </div>

                <div>
                    <div className="mb-3 text-[13px] font-bold text-muted-foreground">
                        Pedidos web del comprador
                    </div>
                    <StoreOrderList
                        storeOrders={store_orders}
                        meta={{
                            total: store_orders.length,
                            limit: store_orders.length,
                            offset: 0,
                            has_more: false,
                        }}
                        filters={{}}
                        embedded
                    />
                </div>
            </div>
            <StoreCustomerLinkDialog
                customer={linking}
                onClose={() => setLinking(null)}
            />
        </AppLayout>
    );
}
