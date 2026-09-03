import { Check } from 'lucide-react';
import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { usePurchaseCreditNoteFormContext } from '../contexts/PurchaseCreditNoteFormContext';
import {
    REASON_LABELS,
    type PurchaseCreditNoteReason,
} from '../types/PurchaseCreditNote';
import { PurchaseCreditNoteLinesSection } from './PurchaseCreditNoteLinesSection';

interface FormSectionHeadProps {
    step: number;
    title: string;
    sub: string;
}

function FormSectionHead({ step, title, sub }: FormSectionHeadProps) {
    return (
        <div className="flex items-center gap-3 border-b p-5">
            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary">
                {step}
            </span>
            <div className="mr-auto">
                <div className="text-base font-bold tracking-tight">
                    {title}
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    {sub}
                </div>
            </div>
        </div>
    );
}

const REASON_OPTIONS: OptionType[] = Object.entries(REASON_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function PurchaseCreditNoteForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        supplierLookupUrl,
        supplierOption,
        selectSupplier,
        invoiceLookupUrl,
        invoiceOption,
        selectInvoice,
        returnLookupUrl,
        returnOption,
        selectReturn,
        selectCurrency,
    } = usePurchaseCreditNoteFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la nota"
                        sub="Quién la emite, contra qué factura y por qué"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_id"
                                className="text-[13px] font-semibold"
                            >
                                Proveedor *
                            </Label>
                            <Select2Ajax
                                inputId="supplier_id"
                                url={supplierLookupUrl}
                                value={supplierOption}
                                onChange={selectSupplier}
                                error={!!errors.supplier_id}
                                size="md"
                                placeholder="Busca un proveedor"
                            />
                            {errors.supplier_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="purchase_invoice_id"
                                className="text-[13px] font-semibold"
                            >
                                Factura afectada
                            </Label>
                            <Select2Ajax
                                inputId="purchase_invoice_id"
                                url={invoiceLookupUrl}
                                params={{ supplier_id: data.supplier_id }}
                                value={invoiceOption}
                                onChange={selectInvoice}
                                error={!!errors.purchase_invoice_id}
                                isClearable
                                isDisabled={data.supplier_id === ''}
                                size="md"
                                placeholder={
                                    data.supplier_id === ''
                                        ? 'Elige antes el proveedor'
                                        : 'Sin factura concreta'
                                }
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Déjala vacía si la nota no corrige una factura
                            </span>
                            {errors.purchase_invoice_id && (
                                <p className="text-sm text-bad">
                                    {errors.purchase_invoice_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="purchase_return_id"
                                className="text-[13px] font-semibold"
                            >
                                Devolución que la origina
                            </Label>
                            <Select2Ajax
                                inputId="purchase_return_id"
                                url={returnLookupUrl}
                                params={{ supplier_id: data.supplier_id }}
                                value={returnOption}
                                onChange={selectReturn}
                                error={!!errors.purchase_return_id}
                                isClearable
                                isDisabled={data.supplier_id === ''}
                                size="md"
                                placeholder={
                                    data.supplier_id === ''
                                        ? 'Elige antes el proveedor'
                                        : 'Sin devolución'
                                }
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Solo devoluciones confirmadas y aún sin
                                acreditar
                            </span>
                            {errors.purchase_return_id && (
                                <p className="text-sm text-bad">
                                    {errors.purchase_return_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_document_number"
                                className="text-[13px] font-semibold"
                            >
                                Número de la nota
                            </Label>
                            <Input
                                id="supplier_document_number"
                                type="text"
                                value={data.supplier_document_number}
                                onChange={(e) =>
                                    setData(
                                        'supplier_document_number',
                                        e.target.value,
                                    )
                                }
                                placeholder="El número que emitió el proveedor"
                                maxLength={60}
                                className={`h-[42px] rounded-[10px] ${errors.supplier_document_number ? 'border-bad' : ''}`}
                            />
                            {errors.supplier_document_number && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_document_number}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="note_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha *
                            </Label>
                            <Input
                                id="note_date"
                                type="date"
                                value={data.note_date}
                                onChange={(e) =>
                                    setData('note_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.note_date ? 'border-bad' : ''}`}
                            />
                            {errors.note_date && (
                                <p className="text-sm text-bad">
                                    {errors.note_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Motivo *
                            </Label>
                            <Select2
                                options={REASON_OPTIONS}
                                value={
                                    REASON_OPTIONS.find(
                                        (option) =>
                                            option.value === data.reason,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'reason',
                                        (option?.value ??
                                            'return') as PurchaseCreditNoteReason,
                                    )
                                }
                                error={!!errors.reason}
                                size="md"
                                placeholder="Por qué se emite"
                            />
                            {errors.reason && (
                                <p className="text-sm text-bad">
                                    {errors.reason}
                                </p>
                            )}
                        </div>

                        {data.reason === 'other' && (
                            <div className="flex flex-col gap-1.5 sm:col-span-2">
                                <Label
                                    htmlFor="reason_detail"
                                    className="text-[13px] font-semibold"
                                >
                                    Explica el motivo *
                                </Label>
                                <Textarea
                                    id="reason_detail"
                                    value={data.reason_detail}
                                    onChange={(e) =>
                                        setData('reason_detail', e.target.value)
                                    }
                                    rows={2}
                                    maxLength={500}
                                    className="rounded-[10px]"
                                />
                                {errors.reason_detail && (
                                    <p className="text-sm text-bad">
                                        {errors.reason_detail}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Qué se acredita, en qué unidad y a qué precio"
                    />
                    <PurchaseCreditNoteLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Condiciones"
                        sub="Moneda, tasa y notas del documento"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="currency"
                                className="text-[13px] font-semibold"
                            >
                                Moneda *
                            </Label>
                            <CurrencySelect
                                id="currency"
                                value={data.currency}
                                error={errors.currency}
                                onValueChange={selectCurrency}
                            />
                            {errors.currency && (
                                <p className="text-sm text-bad">
                                    {errors.currency}
                                </p>
                            )}
                        </div>

                        <ExchangeRateField
                            value={data.exchange_rate}
                            onValueChange={(value) =>
                                setData('exchange_rate', value)
                            }
                            currency={data.currency}
                            dateLabel="la fecha de la nota"
                            error={errors.exchange_rate}
                        />

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="notes"
                                className="text-[13px] font-semibold"
                            >
                                Notas
                            </Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                className="rounded-[10px]"
                                rows={3}
                            />
                        </div>
                    </div>
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Líneas
                        </span>
                        <b className="font-bold">{data.lines.length}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Bruto
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.gross}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Descuento de líneas
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.discountAmount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Subtotal
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.subtotal}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Impuesto
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.taxAmount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t pt-2.5 text-[15px]">
                        <span className="font-semibold">Total acreditado</span>
                        <b className="font-extrabold tabular-nums">
                            <AmountDual
                                amount={totals.total}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    {totals.withholdingAmount > 0 && (
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Retención
                            </span>
                            <b className="font-bold tabular-nums">
                                <AmountDual
                                    amount={totals.withholdingAmount}
                                    currency={data.currency}
                                    rate={data.exchange_rate || undefined}
                                    className="items-end"
                                />
                            </b>
                        </div>
                    )}
                </div>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear nota'
                          : 'Guardar cambios'}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 w-full justify-center rounded-[11px] bg-card font-semibold"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancelar
                </Button>
            </Card>
        </form>
    );
}
