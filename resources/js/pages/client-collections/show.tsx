import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Banknote,
    Calendar,
    Check,
    Contact,
    Edit,
    Hash,
    StickyNote,
    UserRound,
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import clientCollections from '@/routes/client-collections';
import salesInvoices from '@/routes/sales-invoices';
import type { BreadcrumbItem } from '@/types';
import {
    CHECK_STATUS_LABELS,
    CHECK_TRANSITIONS,
    isEditable,
    ORIGIN_TYPE_LABELS,
    PAYMENT_METHOD_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type CheckStatus,
    type ClientCollection,
    type ClientCollectionStatus,
} from './types/ClientCollection';

interface Props {
    clientCollection: ClientCollection;
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
 * Un importe del cobro con su equivalente debajo. Las cuatro columnas de moneda
 * que congeló el cobro se atan aquí una vez, en vez de repetirlas en cada fila.
 */
function Amount({
    collection,
    value,
}: {
    collection: ClientCollection;
    value: string;
}) {
    return (
        <AmountDual
            amount={value}
            currency={collection.currency}
            rate={collection.exchange_rate}
            baseCurrency={collection.base_currency}
            baseRate={collection.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function ClientCollectionsShow({ clientCollection }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);
    const [bounceOpen, setBounceOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({
            cancellation_reason: '',
        });

    const statusUrl = clientCollections.updateStatus({
        company: companyId,
        id: clientCollection.id,
    }).url;

    const checkStatusUrl = clientCollections.updateCheckStatus({
        company: companyId,
        id: clientCollection.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const fieldErrors = errors as Record<string, string | undefined>;
    const statusError = fieldErrors.status;
    const checkError = fieldErrors.check_status;

    /**
     * Al confirmar, el backend vuelve a comprobar el reparto contra el saldo
     * vivo de cada factura: ese error llega indexado por su fila.
     */
    const applicationError = Object.entries(fieldErrors).find(([field]) =>
        field.startsWith('applications'),
    )?.[1];

    const transitions = STATUS_TRANSITIONS[clientCollection.status] ?? [];
    const applications = (clientCollection.applications ?? []).filter(
        (application) => application.status === 'active',
    );

    /** El cheque solo se mueve donde hay cheque y el cobro sigue vivo. */
    const checkTransitions =
        clientCollection.payment_method === 'check' &&
        clientCollection.check_status !== null &&
        clientCollection.status !== 'cancelled'
            ? (CHECK_TRANSITIONS[clientCollection.check_status] ?? [])
            : [];

    /** El estado destino viaja en el `transform`: el formulario solo captura el motivo. */
    const advanceTo = (status: ClientCollectionStatus) => {
        transform(() => ({ status, cancellation_reason: '' }));
        put(statusUrl);
    };

    const moveCheckTo = (checkStatus: CheckStatus) => {
        transform(() => ({ check_status: checkStatus }));
        put(checkStatusUrl, { onSuccess: () => setBounceOpen(false) });
    };

    const submitCancellation = () => {
        transform((current) => ({
            status: 'cancelled',
            cancellation_reason: current.cancellation_reason,
        }));

        put(statusUrl, {
            onSuccess: () => {
                setCancelOpen(false);
                reset();
            },
        });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Cobros a clientes',
            href: clientCollections.index(companyId).url,
        },
        {
            title: clientCollection.code,
            href: clientCollections.show({
                company: companyId,
                id: clientCollection.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={clientCollection.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={clientCollections.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Cobros a clientes
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {clientCollection.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[clientCollection.status]}
                            >
                                {STATUS_LABELS[clientCollection.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Contact className="size-3.5 opacity-80" />
                                {clientCollection.client_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Banknote className="size-3.5 opacity-80" />
                                {
                                    PAYMENT_METHOD_LABELS[
                                        clientCollection.payment_method
                                    ]
                                }
                            </span>
                            {clientCollection.reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {clientCollection.reference}
                                </span>
                            )}
                            {clientCollection.collected_by_name && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <UserRound className="size-3.5 opacity-80" />
                                    {clientCollection.collected_by_name}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {clientCollection.collection_date}
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
                                    {STATUS_LABELS[status]}
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
                        {isEditable(clientCollection) && (
                            <Link
                                href={
                                    clientCollections.edit({
                                        company: companyId,
                                        id: clientCollection.id,
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
                {checkError && <p className="text-sm text-bad">{checkError}</p>}
                {applicationError && (
                    <p className="text-sm text-bad">{applicationError}</p>
                )}

                <div className="grid grid-cols-1 items-start gap-5 lg:grid-cols-2">
                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Documento
                        </div>
                        <DataRow
                            label="Aplicado a"
                            value={
                                ORIGIN_TYPE_LABELS[clientCollection.origin_type]
                            }
                        />
                        <DataRow
                            label="Fecha del cobro"
                            value={clientCollection.collection_date}
                        />
                        <DataRow
                            label="Forma de cobro"
                            value={
                                PAYMENT_METHOD_LABELS[
                                    clientCollection.payment_method
                                ]
                            }
                        />
                        <DataRow
                            label="Referencia"
                            value={clientCollection.reference ?? '—'}
                        />
                        <DataRow
                            label="Cuenta receptora"
                            value={clientCollection.bank_account ?? '—'}
                        />
                        <DataRow
                            label="Cobrador"
                            value={clientCollection.collected_by_name ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${clientCollection.currency} (tasa ${clientCollection.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                clientCollection.base_currency
                                    ? `${clientCollection.base_currency} (tasa ${clientCollection.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Monto recibido"
                            value={
                                <Amount
                                    collection={clientCollection}
                                    value={clientCollection.amount}
                                />
                            }
                        />
                        <DataRow
                            label="Retención"
                            value={
                                <Amount
                                    collection={clientCollection}
                                    value={clientCollection.withholding_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Aplicado a facturas"
                            value={
                                <Amount
                                    collection={clientCollection}
                                    value={clientCollection.applied_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Sin aplicar"
                            value={
                                <Amount
                                    collection={clientCollection}
                                    value={clientCollection.unapplied_amount}
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
                            value={clientCollection.client_name ?? '—'}
                        />
                        <DataRow
                            label="Saldo por cobrar"
                            value={
                                clientCollection.client_current_balance ?? '—'
                            }
                        />
                        <DataRow
                            label="Crédito a favor"
                            value={
                                clientCollection.client_advance_balance ?? '—'
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importe en bolívares
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Congelado al cobrar: contra la tasa de cada factura
                            sale el diferencial cambiario.
                        </p>
                        <DataRow
                            label="Monto recibido"
                            value={clientCollection.amount_ves}
                        />
                    </Card>

                    {clientCollection.check_status && (
                        <Card className="gap-3 rounded-2xl px-[18px] py-4 lg:col-span-2">
                            <div className="text-[13px] font-bold text-muted-foreground">
                                Cheque
                            </div>
                            <p className="text-[12.5px] text-muted-foreground">
                                El cheque avanza por su propio carril. Si el
                                banco lo devuelve, el cobro se anula y las
                                facturas recuperan su saldo: el dinero nunca
                                entró.
                            </p>
                            <DataRow
                                label="Número"
                                value={clientCollection.check_number ?? '—'}
                            />
                            <DataRow
                                label="Fecha de cobro"
                                value={clientCollection.check_date ?? '—'}
                            />
                            <DataRow
                                label="Estado"
                                value={
                                    CHECK_STATUS_LABELS[
                                        clientCollection.check_status
                                    ]
                                }
                            />
                            {checkTransitions.length > 0 && (
                                <div className="flex flex-wrap gap-2.5 pt-1">
                                    {checkTransitions.map((checkStatus) =>
                                        checkStatus === 'bounced' ? (
                                            <Button
                                                key={checkStatus}
                                                variant="outline"
                                                className="h-10 rounded-[11px] bg-card px-4 font-semibold text-bad"
                                                onClick={() =>
                                                    setBounceOpen(true)
                                                }
                                                disabled={processing}
                                            >
                                                <Ban />
                                                {
                                                    CHECK_STATUS_LABELS[
                                                        checkStatus
                                                    ]
                                                }
                                            </Button>
                                        ) : (
                                            <Button
                                                key={checkStatus}
                                                variant="outline"
                                                className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                                                onClick={() =>
                                                    moveCheckTo(checkStatus)
                                                }
                                                disabled={processing}
                                            >
                                                <Check />
                                                {
                                                    CHECK_STATUS_LABELS[
                                                        checkStatus
                                                    ]
                                                }
                                            </Button>
                                        ),
                                    )}
                                </div>
                            )}
                        </Card>
                    )}
                </div>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <div className="border-b p-5 text-[13px] font-bold text-muted-foreground">
                        Reparto entre facturas
                    </div>
                    <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[1.8fr_1fr_1fr_1fr_1fr]">
                        {[
                            'Factura',
                            'Vence',
                            'Saldo',
                            'Aplicado',
                            'Diferencial',
                        ].map((header) => (
                            <div
                                key={header}
                                className="text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                {header}
                            </div>
                        ))}
                    </div>
                    <div className="flex flex-col">
                        {applications.map((application) => (
                            <div
                                key={application.id}
                                className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[1.8fr_1fr_1fr_1fr_1fr] lg:items-center"
                            >
                                <Link
                                    href={
                                        salesInvoices.show({
                                            company: companyId,
                                            id: application.sales_invoice_id,
                                        }).url
                                    }
                                    className="flex min-w-0 flex-col transition-colors hover:text-primary"
                                >
                                    <span className="truncate font-bold">
                                        {application.sales_invoice_code ?? '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {application.invoice_number}
                                    </span>
                                </Link>
                                <div className="text-[13.5px] text-muted-foreground tabular-nums">
                                    {application.invoice_due_date ?? '—'}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {application.invoice_balance ?? '—'}
                                </div>
                                <div className="text-[13.5px] font-semibold tabular-nums">
                                    {application.applied_amount}
                                </div>
                                <div className="text-[13.5px] tabular-nums">
                                    {application.exchange_difference}
                                </div>
                            </div>
                        ))}
                        {applications.length === 0 && (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                El cobro no se ha repartido entre facturas.
                            </div>
                        )}
                    </div>
                </Card>

                {clientCollection.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {clientCollection.cancellation_reason}
                        </p>
                    </Card>
                )}

                {clientCollection.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {clientCollection.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular cobro a cliente</DialogTitle>
                        <DialogDescription>
                            El cobro no se elimina: queda anulado y las facturas
                            recuperan el saldo que este cobro les había abonado.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="cancellation_reason"
                            className="text-[13px] font-semibold"
                        >
                            Motivo *
                        </Label>
                        <Textarea
                            id="cancellation_reason"
                            value={data.cancellation_reason}
                            onChange={(e) =>
                                setData('cancellation_reason', e.target.value)
                            }
                            rows={3}
                            maxLength={500}
                            className="rounded-[10px]"
                        />
                        {errors.cancellation_reason && (
                            <p className="text-sm text-bad">
                                {errors.cancellation_reason}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelOpen(false)}
                            disabled={processing}
                        >
                            Volver
                        </Button>
                        <Button
                            onClick={submitCancellation}
                            disabled={processing}
                        >
                            Anular cobro
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={bounceOpen} onOpenChange={setBounceOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Marcar el cheque como devuelto
                        </DialogTitle>
                        <DialogDescription>
                            El dinero nunca entró: el cobro queda anulado, sus
                            aplicaciones se revierten y el cliente recupera la
                            deuda que este cobro le había cancelado.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setBounceOpen(false)}
                            disabled={processing}
                        >
                            Volver
                        </Button>
                        <Button
                            onClick={() => moveCheckTo('bounced')}
                            disabled={processing}
                        >
                            Marcar devuelto
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
