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
import { usePurchaseReturnFormContext } from '../contexts/PurchaseReturnFormContext';
import {
    REASON_LABELS,
    type PurchaseReturnReason,
} from '../types/PurchaseReturn';
import { PurchaseReturnLinesSection } from './PurchaseReturnLinesSection';

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

export function PurchaseReturnForm() {
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
        selectCurrency,
        selectWarehouse,
        options,
    } = usePurchaseReturnFormContext();

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la devolución"
                        sub="A quién se devuelve, de qué factura sale y por qué"
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
                                Factura de origen
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
                                Atarla limita lo devuelto a lo que se compró
                            </span>
                            {errors.purchase_invoice_id && (
                                <p className="text-sm text-bad">
                                    {errors.purchase_invoice_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega *
                            </Label>
                            <Select2
                                inputId="warehouse_id"
                                options={warehouseOptions}
                                value={
                                    warehouseOptions.find(
                                        (option) =>
                                            option.value === data.warehouse_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    selectWarehouse(option?.value ?? '')
                                }
                                error={!!errors.warehouse_id}
                                size="md"
                                placeholder="De dónde sale la mercancía"
                            />
                            {errors.warehouse_id && (
                                <p className="text-sm text-bad">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="return_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha *
                            </Label>
                            <Input
                                id="return_date"
                                type="date"
                                value={data.return_date}
                                onChange={(e) =>
                                    setData('return_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.return_date ? 'border-bad' : ''}`}
                            />
                            {errors.return_date && (
                                <p className="text-sm text-bad">
                                    {errors.return_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
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
                                            'damaged') as PurchaseReturnReason,
                                    )
                                }
                                error={!!errors.reason}
                                size="md"
                                placeholder="Por qué vuelve la mercancía"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Cada línea puede llevar el suyo si difiere
                            </span>
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
                        sub="Qué vuelve, de qué ubicación sale y a qué precio se compró"
                    />
                    <PurchaseReturnLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Traslado y condiciones"
                        sub="Quién retira, moneda, tasa y notas del documento"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="carrier"
                                className="text-[13px] font-semibold"
                            >
                                Transportista
                            </Label>
                            <Input
                                id="carrier"
                                type="text"
                                value={data.carrier}
                                onChange={(e) =>
                                    setData('carrier', e.target.value)
                                }
                                placeholder="Quién retira la mercancía"
                                maxLength={150}
                                className={`h-[42px] rounded-[10px] ${errors.carrier ? 'border-bad' : ''}`}
                            />
                            {errors.carrier && (
                                <p className="text-sm text-bad">
                                    {errors.carrier}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="tracking_number"
                                className="text-[13px] font-semibold"
                            >
                                Guía de retorno
                            </Label>
                            <Input
                                id="tracking_number"
                                type="text"
                                value={data.tracking_number}
                                onChange={(e) =>
                                    setData('tracking_number', e.target.value)
                                }
                                maxLength={60}
                                className={`h-[42px] rounded-[10px] ${errors.tracking_number ? 'border-bad' : ''}`}
                            />
                            {errors.tracking_number && (
                                <p className="text-sm text-bad">
                                    {errors.tracking_number}
                                </p>
                            )}
                        </div>

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
                            dateLabel="la fecha de la devolución"
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
                        <span className="font-semibold">Total devuelto</span>
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
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    Al confirmarla, la mercancía sale del inventario al costo de
                    la compra original.
                </p>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear devolución'
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
