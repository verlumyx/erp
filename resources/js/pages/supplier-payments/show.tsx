import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Ban,
    Banknote,
    Calendar,
    Check,
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
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import purchaseInvoices from '@/routes/purchase-invoices';
import supplierPayments from '@/routes/supplier-payments';
import type { BreadcrumbItem } from '@/types';
import {
    isEditable,
    ORIGIN_TYPE_LABELS,
    PAYMENT_METHOD_LABELS,
    STATUS_LABELS,
    STATUS_PILL_KIND,
    STATUS_TRANSITIONS,
    type SupplierPayment,
    type SupplierPaymentStatus,
} from './types/SupplierPayment';

interface Props {
    supplierPayment: SupplierPayment;
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
 * Un importe del pago con su equivalente debajo. Las cuatro columnas de moneda
 * que congeló el pago se atan aquí una vez, en vez de repetirlas en cada fila.
 */
function Amount({
    payment,
    value,
}: {
    payment: SupplierPayment;
    value: string;
}) {
    return (
        <AmountDual
            amount={value}
            currency={payment.currency}
            rate={payment.exchange_rate}
            baseCurrency={payment.base_currency}
            baseRate={payment.base_exchange_rate}
            className="items-end"
        />
    );
}

export default function SupplierPaymentsShow({ supplierPayment }: Props) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, put, processing, errors, reset, transform } =
        useForm({
            cancellation_reason: '',
        });

    const statusUrl = supplierPayments.updateStatus({
        company: companyId,
        id: supplierPayment.id,
    }).url;

    /** El error de transición llega en `status`, que no es campo del formulario. */
    const fieldErrors = errors as Record<string, string | undefined>;
    const statusError = fieldErrors.status;

    /**
     * Al confirmar, el backend vuelve a comprobar el reparto contra el saldo
     * vivo de cada factura: ese error llega indexado por su fila.
     */
    const applicationError = Object.entries(fieldErrors).find(([field]) =>
        field.startsWith('applications'),
    )?.[1];

    const transitions = STATUS_TRANSITIONS[supplierPayment.status] ?? [];
    const applications = (supplierPayment.applications ?? []).filter(
        (application) => application.status === 'active',
    );

    /** El estado destino viaja en el `transform`: el formulario solo captura el motivo. */
    const advanceTo = (status: SupplierPaymentStatus) => {
        transform(() => ({ status, cancellation_reason: '' }));
        put(statusUrl);
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
            title: 'Pagos a proveedor',
            href: supplierPayments.index(companyId).url,
        },
        {
            title: supplierPayment.code,
            href: supplierPayments.show({
                company: companyId,
                id: supplierPayment.id,
            }).url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={supplierPayment.code} />
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={supplierPayments.index(companyId).url}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Pagos a proveedor
                </Link>

                <Card className="flex-row flex-wrap items-center justify-between gap-5 rounded-2xl p-5">
                    <div className="flex flex-col gap-2">
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-extrabold tracking-tight">
                                {supplierPayment.code}
                            </h1>
                            <StatusPill
                                kind={STATUS_PILL_KIND[supplierPayment.status]}
                            >
                                {STATUS_LABELS[supplierPayment.status]}
                            </StatusPill>
                        </div>
                        <div className="flex flex-wrap gap-x-4 gap-y-1.5">
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Truck className="size-3.5 opacity-80" />
                                {supplierPayment.supplier_name ?? '—'}
                            </span>
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Banknote className="size-3.5 opacity-80" />
                                {
                                    PAYMENT_METHOD_LABELS[
                                        supplierPayment.payment_method
                                    ]
                                }
                            </span>
                            {supplierPayment.reference && (
                                <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                    <Hash className="size-3.5 opacity-80" />
                                    {supplierPayment.reference}
                                </span>
                            )}
                            <span className="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted-foreground">
                                <Calendar className="size-3.5 opacity-80" />
                                {supplierPayment.payment_date}
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
                        {isEditable(supplierPayment) && (
                            <Link
                                href={
                                    supplierPayments.edit({
                                        company: companyId,
                                        id: supplierPayment.id,
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
                                ORIGIN_TYPE_LABELS[supplierPayment.origin_type]
                            }
                        />
                        <DataRow
                            label="Fecha del pago"
                            value={supplierPayment.payment_date}
                        />
                        <DataRow
                            label="Forma de pago"
                            value={
                                PAYMENT_METHOD_LABELS[
                                    supplierPayment.payment_method
                                ]
                            }
                        />
                        <DataRow
                            label="Referencia"
                            value={supplierPayment.reference ?? '—'}
                        />
                        <DataRow
                            label="Cuenta bancaria"
                            value={supplierPayment.bank_account ?? '—'}
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importes
                        </div>
                        <DataRow
                            label="Moneda"
                            value={`${supplierPayment.currency} (tasa ${supplierPayment.exchange_rate})`}
                        />
                        <DataRow
                            label="Moneda de la empresa"
                            value={
                                supplierPayment.base_currency
                                    ? `${supplierPayment.base_currency} (tasa ${supplierPayment.base_exchange_rate})`
                                    : '—'
                            }
                        />
                        <DataRow
                            label="Monto entregado"
                            value={
                                <Amount
                                    payment={supplierPayment}
                                    value={supplierPayment.amount}
                                />
                            }
                        />
                        <DataRow
                            label="Retención"
                            value={
                                <Amount
                                    payment={supplierPayment}
                                    value={supplierPayment.withholding_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Aplicado a facturas"
                            value={
                                <Amount
                                    payment={supplierPayment}
                                    value={supplierPayment.applied_amount}
                                />
                            }
                        />
                        <DataRow
                            label="Sin aplicar"
                            value={
                                <Amount
                                    payment={supplierPayment}
                                    value={supplierPayment.unapplied_amount}
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
                            value={supplierPayment.supplier_name ?? '—'}
                        />
                        <DataRow
                            label="Saldo por pagar"
                            value={
                                supplierPayment.supplier_current_balance ?? '—'
                            }
                        />
                        <DataRow
                            label="Crédito a favor"
                            value={
                                supplierPayment.supplier_advance_balance ?? '—'
                            }
                        />
                    </Card>

                    <Card className="gap-3 rounded-2xl px-[18px] py-4">
                        <div className="text-[13px] font-bold text-muted-foreground">
                            Importe en bolívares
                        </div>
                        <p className="text-[12.5px] text-muted-foreground">
                            Congelado al pagar: contra la tasa de cada factura
                            sale el diferencial cambiario.
                        </p>
                        <DataRow
                            label="Monto entregado"
                            value={supplierPayment.amount_ves}
                        />
                    </Card>
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
                                        purchaseInvoices.show({
                                            company: companyId,
                                            id: application.purchase_invoice_id,
                                        }).url
                                    }
                                    className="flex min-w-0 flex-col transition-colors hover:text-primary"
                                >
                                    <span className="truncate font-bold">
                                        {application.purchase_invoice_code ??
                                            '—'}
                                    </span>
                                    <span className="truncate text-[12.5px] text-muted-foreground">
                                        {application.supplier_invoice_number}
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
                                El pago no se ha repartido entre facturas.
                            </div>
                        )}
                    </div>
                </Card>

                {supplierPayment.cancellation_reason && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <Ban className="size-[15px]" />
                            Motivo de anulación
                        </div>
                        <p className="text-sm leading-relaxed">
                            {supplierPayment.cancellation_reason}
                        </p>
                    </Card>
                )}

                {supplierPayment.notes && (
                    <Card className="gap-2 rounded-2xl px-[18px] py-4">
                        <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                            <StickyNote className="size-[15px]" />
                            Notas
                        </div>
                        <p className="text-sm leading-relaxed whitespace-pre-wrap">
                            {supplierPayment.notes}
                        </p>
                    </Card>
                )}
            </div>

            <Dialog open={cancelOpen} onOpenChange={setCancelOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Anular pago a proveedor</DialogTitle>
                        <DialogDescription>
                            El pago no se elimina: queda anulado y las facturas
                            recuperan el saldo que este pago les había abonado.
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
                            Anular pago
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
