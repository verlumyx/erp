import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Banknote,
    Calendar,
    Check,
    ClipboardList,
    Edit,
    Hash,
    StickyNote,
    Truck,
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
import purchaseOrders from '@/routes/purchase-orders';
import supplierAdvances from '@/routes/supplier-advances';
import supplierPayments from '@/routes/supplier-payments';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    PAYMENT_METHOD_LABELS,
    REQUESTABLE_TRANSITIONS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    TRANSITION_LABELS,
    type SupplierAdvance,
    type SupplierAdvanceStatus,
} from './types/SupplierAdvance';

interface Props {
    supplierAdvance: SupplierAdvance;
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
function Amount({
    advance,
    value,
}: {
    advance: SupplierAdvance;
    value: string;
}) {
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

export default function SupplierAdvancesShow({ supplierAdvance }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { put, processing, errors, transform } = useForm({});

    const statusUrl = supplierAdvances.updateStatus({
        company: companyId,
        id: supplierAdvance.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const statusError = (errors as Record<string, string | undefined>).status;

    const transitions = REQUESTABLE_TRANSITIONS[supplierAdvance.status] ?? [];

    const advanceTo = (status: SupplierAdvanceStatus) => {
        transform(() => ({ status }));
        put(statusUrl, { onSuccess: () => setCancelOpen(false) });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Anticipos a proveedor',
            href: supplierAdvances.index(companyId).url,
        },
        {
            title: supplierAdvance.code,
            href: supplierAdvances.show({
                company: companyId,
                id: supplierAdvance.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={supplierAdvance.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierAdvances.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Anticipos a proveedor
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {supplierAdvance.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[supplierAdvance.status]}
                            >
                                {STATUS_LABELS[supplierAdvance.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {supplierAdvance.supplier_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Banknote className="size-3.5 opacity-80" />
                                {
                                    PAYMENT_METHOD_LABELS[
                                        supplierAdvance.payment_method
                                    ]
                                }
                            </span>
                            {supplierAdvance.reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {supplierAdvance.reference}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {supplierAdvance.advance_date}
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
                        {isEditable(supplierAdvance) && (
                            <Link
                                href={
                                    supplierAdvances.edit({
                                        company: companyId,
                                        id: supplierAdvance.id,
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
                            value={supplierAdvance.advance_date}
                        />
                        <DataRow
                            label="Forma de pago"
                            value={
                                PAYMENT_METHOD_LABELS[
                                    supplierAdvance.payment_method
                                ]
                            }
                        />
                        <DataRow
                            label="Referencia"
                            value={supplierAdvance.reference ?? '—'}
                        />
                        <DataRow
                            label="Cuenta bancaria"
                            value={supplierAdvance.bank_account ?? '—'}
                        />
                        <DataRow
                            label="Orden de compra"
                            value={
                                supplierAdvance.purchase_order_id ? (
                                    <Link
                                        href={
                                            purchaseOrders.show({
                                                company: companyId,
                                                id: supplierAdvance.purchase_order_id,
                                            }).url
                                        }
                                        className="inline-flex items-center gap-1.5 transition-colors hover:text-primary"
                                    >
                                        <ClipboardList className="size-3.5 opacity-80" />
                                        {supplierAdvance.purchase_order_code ??
                                            'Ver orden'}
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
                            value={`${supplierAdvance.currency} (tasa ${supplierAdvance.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                supplierAdvance.base_currency
                                    ? `${supplierAdvance.base_currency} (tasa ${supplierAdvance.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Monto entregado"
                            value={
                                <Amount
                                    advance={supplierAdvance}
                                    value={supplierAdvance.amount}
                                />
                            }
                        />
                        <DataRow
                            label="Aplicado a facturas"
                            value={
                                <Amount
                                    advance={supplierAdvance}
                                    value={supplierAdvance.applied_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Disponible"
                            value={
                                <Amount
                                    advance={supplierAdvance}
                                    value={supplierAdvance.balance}
                                />
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Proveedor
                        </div>
                        <DataRow
                            label="Nombre"
                            value={supplierAdvance.supplier_name ?? '—'}
                        />
                        <DataRow
                            label="Saldo por pagar"
                            value={
                                supplierAdvance.supplier_current_balance ?? '—'
                            }
                        />
                        <DataRow
                            label="Crédito a favor"
                            value={
                                supplierAdvance.supplier_advance_balance ?? '—'
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Pago que lo entrega
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Aprobar el anticipo lo compromete; confirmar este
                            pago es lo que lo entrega y le da crédito al
                            proveedor.
                        </p>
                        {supplierAdvance.payment_id ? (
                            <>
                                <DataRow
                                    label="Pago"
                                    value={
                                        <Link
                                            href={
                                                supplierPayments.show({
                                                    company: companyId,
                                                    id: supplierAdvance.payment_id,
                                                }).url
                                            }
                                            className="transition-colors hover:text-primary"
                                        >
                                            {supplierAdvance.payment_code ??
                                                'Ver pago'}
                                        </Link>
                                    }
                                />
                                <DataRow
                                    label="Estado del pago"
                                    value={
                                        supplierAdvance.payment_status ?? '—'
                                    }
                                />
                            </>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Todavía no tiene pago: se genera al aprobarlo.
                            </p>
                        )}
                    </Card>
                </div>

                {supplierAdvance.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {supplierAdvance.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular anticipo a proveedor</DialogTitle>
                        <DialogDescription>
                            El anticipo no se elimina: queda anulado y, si ya
                            tenía un pago generado, ese pago se anula con él.
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
