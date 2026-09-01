import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Banknote,
    Calendar,
    Check,
    ClipboardList,
    Contact,
    Edit,
    Hash,
    StickyNote,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { AmountDual } from '@/components/amount-dual';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import clientAdvances from '@/routes/client-advances';
import clientCollections from '@/routes/client-collections';
import salesOrders from '@/routes/sales-orders';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    PAYMENT_METHOD_LABELS,
    REQUESTABLE_TRANSITIONS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    TRANSITION_LABELS,
    type ClientAdvance,
    type ClientAdvanceStatus,
} from './types/ClientAdvance';

interface Props {
    clientAdvance: ClientAdvance;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function DataRow({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-4 text-[13.5px]">
            <span className="font-medium text-muted-foreground">{label}</span>
            <b className="text-right font-bold">{value}</b>
        </div>
    );
}

/**
 * Un importe del anticipo con su equivalente debajo. Las cuatro columnas de
 * moneda que congeló se atan aquí una vez, en vez de repetirlas en cada fila.
 */
function Amount({ advance, value }: { advance: ClientAdvance; value: string }) {
    return (
        <AmountDual
            amount={value}
            currency={advance.currency}
            rate={advance.exchange_rate}
            baseCurrency={advance.base_currency}
            baseRate={advance.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function ClientAdvancesShow({ clientAdvance }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { put, processing, errors, transform } = useForm({});

    const statusUrl = clientAdvances.updateStatus({
        company: companyId,
        id: clientAdvance.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = REQUESTABLE_TRANSITIONS[clientAdvance.status] ?? [];

    const advanceTo = (status: ClientAdvanceStatus) => {
        transform(() => ({ status }));
        put(statusUrl, { onSuccess: () => setCancelOpen(false) });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos de clientes',
            href: clientAdvances.index(companyId).url,
        },
        {
            title: clientAdvance.code,
            href: clientAdvances.show({
                company: companyId,
                id: clientAdvance.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={clientAdvance.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientAdvances.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Anticipos de clientes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {clientAdvance.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[clientAdvance.status]}
                            >
                                {STATUS_LABELS[clientAdvance.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Contact className="size-3.5 opacity-80" />
                                {clientAdvance.client_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Banknote className="size-3.5 opacity-80" />
                                {
                                    PAYMENT_METHOD_LABELS[
                                        clientAdvance.payment_method
                                    ]
                                }
                            </span>
                            {clientAdvance.reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {clientAdvance.reference}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {clientAdvance.advance_date}
                            </span>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2.5">
                        {transitions
                            .filter((status) => status !== 'cancelled')
                            .map((status) => (
                                <Button
                                    key={status}
                                    variant="outline"
                                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                    onClick={() => advanceTo(status)}
                                    disabled={processing}
                                >
                                    <Check />
                                    {TRANSITION_LABELS[status] ??
                                        STATUS_LABELS[status]}
                                </Button>
                            ))}
                        {transitions.includes('cancelled') && (
                            <Button
                                variant="outline"
                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                onClick={() => setCancelOpen(true)}
                                disabled={processing}
                            >
                                <Ban />
                                Anular
                            </Button>
                        )}
                        {isEditable(clientAdvance) && (
                            <Link
                                href={
                                    clientAdvances.edit({
                                        company: companyId,
                                        id: clientAdvance.id,
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
                        )}
                    </div>
                </Card>

                {statusError && (
                    <p className="text-sm text-bad">{statusError}</p>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Documento
                        </div>
                        <DataRow
                            label="Fecha del anticipo"
                            value={clientAdvance.advance_date}
                        />
                        <DataRow
                            label="Forma de cobro"
                            value={
                                PAYMENT_METHOD_LABELS[
                                    clientAdvance.payment_method
                                ]
                            }
                        />
                        <DataRow
                            label="Referencia"
                            value={clientAdvance.reference ?? '—'}
                        />
                        <DataRow
                            label="Cuenta receptora"
                            value={clientAdvance.bank_account ?? '—'}
                        />
                        <DataRow
                            label="Pedido de venta"
                            value={
                                clientAdvance.sales_order_id ? (
                                    <Link
                                        href={
                                            salesOrders.show({
                                                company: companyId,
                                                id: clientAdvance.sales_order_id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1.5 transition-colors hover:text-primary"
                                    >
                                        <ClipboardList className="size-3.5 opacity-80" />
                                        {clientAdvance.sales_order_code ??
                                            'Ver pedido'}
                                    </Link>
                                ) : (
                                    '—'
                                )
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${clientAdvance.currency} (tasa ${clientAdvance.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                clientAdvance.base_currency
                                    ? `${clientAdvance.base_currency} (tasa ${clientAdvance.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Monto recibido"
                            value={
                                <Amount
                                    advance={clientAdvance}
                                    value={clientAdvance.amount}
                                />
                            }
                        />
                        <DataRow
                            label="Aplicado a facturas"
                            value={
                                <Amount
                                    advance={clientAdvance}
                                    value={clientAdvance.applied_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Devuelto al cliente"
                            value={
                                <Amount
                                    advance={clientAdvance}
                                    value={clientAdvance.refunded_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Disponible"
                            value={
                                <Amount
                                    advance={clientAdvance}
                                    value={clientAdvance.balance}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Cliente
                        </div>
                        <DataRow
                            label="Nombre"
                            value={clientAdvance.client_name ?? '—'}
                        />
                        <DataRow
                            label="Saldo por cobrar"
                            value={clientAdvance.client_current_balance ?? '—'}
                        />
                        <DataRow
                            label="Crédito a favor"
                            value={clientAdvance.client_advance_balance ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Cobro que lo recibe
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Aprobar el anticipo lo compromete; confirmar este
                            cobro es lo que lo recibe y le da crédito al
                            cliente.
                        </p>
                        {clientAdvance.collection_id ? (
                            <>
                                <DataRow
                                    label="Cobro"
                                    value={
                                        <Link
                                            href={
                                                clientCollections.show({
                                                    company: companyId,
                                                    id: clientAdvance.collection_id,
                                                }).url
                                            }
                                            className="transition-colors hover:text-primary"
                                        >
                                            {clientAdvance.collection_code ??
                                                'Ver cobro'}
                                        </Link>
                                    }
                                />
                                <DataRow
                                    label="Estado del cobro"
                                    value={
                                        clientAdvance.collection_status ?? '—'
                                    }
                                />
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Todavía no tiene cobro: se genera al aprobarlo.
                            </p>
                        )}
                    </Card>
                </div>

                {clientAdvance.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {clientAdvance.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular anticipo de cliente</DialogTitle>
                        <DialogDescription>
                            El anticipo no se elimina: queda anulado y, si ya
                            tenía un cobro generado, ese cobro se anula con él.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelOpen(false)}
                            disabled={processing}
                        >
                            Volver
                        </Button>
                        <Button
                            onClick={() => advanceTo('cancelled')}
                            disabled={processing}
                        >
                            Anular anticipo
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
