import { Check } from 'lucide-react';
import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { usePurchaseInvoiceFormContext } from '../contexts/PurchaseInvoiceFormContext';
import { PurchaseInvoiceLinesSection } from './PurchaseInvoiceLinesSection';

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

export function PurchaseInvoiceForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        totals,
        supplierLookupUrl,
        supplierOption,
        selectSupplier,
        sourceOrderLookupUrl,
        sourceOrderOption,
        selectSourceOrder,
        selectCurrency,
    } = usePurchaseInvoiceFormContext();

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
                        title="Datos de la factura"
                        sub="Quién la emite, con qué número y contra qué documento"
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
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Orden de compra
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={sourceOrderLookupUrl}
                                params={{ supplier_id: data.supplier_id }}
                                value={sourceOrderOption}
                                onChange={selectSourceOrder}
                                error={
                                    !!errors.sourceable_id ||
                                    !!errors.sourceable_type
                                }
                                isClearable
                                isDisabled={data.supplier_id === ''}
                                size="md"
                                placeholder={
                                    data.supplier_id === ''
                                        ? 'Elige antes el proveedor'
                                        : 'Sin orden previa'
                                }
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Déjala vacía si es una compra directa
                            </span>
                            {(errors.sourceable_id ||
                                errors.sourceable_type) && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id ??
                                        errors.sourceable_type}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_invoice_number"
                                className="text-[13px] font-semibold"
                            >
                                Número de la factura *
                            </Label>
                            <Input
                                id="supplier_invoice_number"
                                type="text"
                                value={data.supplier_invoice_number}
                                onChange={(e) =>
                                    setData(
                                        'supplier_invoice_number',
                                        e.target.value,
                                    )
                                }
                                placeholder="El número impreso por el proveedor"
                                maxLength={60}
                                className={`h-[42px] rounded-[10px] ${errors.supplier_invoice_number ? 'border-bad' : ''}`}
                            />
                            {errors.supplier_invoice_number && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_invoice_number}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_invoice_series"
                                className="text-[13px] font-semibold"
                            >
                                Serie fiscal
                            </Label>
                            <Input
                                id="supplier_invoice_series"
                                type="text"
                                value={data.supplier_invoice_series}
                                onChange={(e) =>
                                    setData(
                                        'supplier_invoice_series',
                                        e.target.value,
                                    )
                                }
                                maxLength={20}
                                className={`h-[42px] rounded-[10px] ${errors.supplier_invoice_series ? 'border-bad' : ''}`}
                            />
                            {errors.supplier_invoice_series && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_invoice_series}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega *
                            </Label>
                            <Select2
                                options={warehouseOptions}
                                value={
                                    warehouseOptions.find(
                                        (option) =>
                                            option.value === data.warehouse_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData('warehouse_id', option?.value ?? '')
                                }
                                error={!!errors.warehouse_id}
                                size="md"
                                placeholder="Selecciona una bodega"
                            />
                            {errors.warehouse_id && (
                                <p className="text-sm text-bad">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="invoice_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de emisión *
                            </Label>
                            <Input
                                id="invoice_date"
                                type="date"
                                value={data.invoice_date}
                                onChange={(e) =>
                                    setData('invoice_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.invoice_date ? 'border-bad' : ''}`}
                            />
                            {errors.invoice_date && (
                                <p className="text-sm text-bad">
                                    {errors.invoice_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="received_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de recepción
                            </Label>
                            <Input
                                id="received_date"
                                type="date"
                                value={data.received_date}
                                onChange={(e) =>
                                    setData('received_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.received_date ? 'border-bad' : ''}`}
                            />
                            {errors.received_date && (
                                <p className="text-sm text-bad">
                                    {errors.received_date}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Qué se compró, en qué unidad y a qué costo"
                    />
                    <PurchaseInvoiceLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Condiciones"
                        sub="Moneda, tasa, vencimiento y descuento"
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
                            dateLabel="la fecha de la factura"
                            error={errors.exchange_rate}
                        />

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="due_date"
                                className="text-[13px] font-semibold"
                            >
                                Vencimiento
                            </Label>
                            <Input
                                id="due_date"
                                type="date"
                                value={data.due_date}
                                onChange={(e) =>
                                    setData('due_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.due_date ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Vacío lo calcula con el crédito del proveedor
                            </span>
                            {errors.due_date && (
                                <p className="text-sm text-bad">
                                    {errors.due_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="discount_amount"
                                className="text-[13px] font-semibold"
                            >
                                Descuento global
                            </Label>
                            <CurrencyInput
                                id="discount_amount"
                                value={data.discount_amount}
                                onValueChange={(value) =>
                                    setData('discount_amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.discount_amount ? 'border-bad' : ''}`}
                            />
                            {errors.discount_amount && (
                                <p className="text-sm text-bad">
                                    {errors.discount_amount}
                                </p>
                            )}
                        </div>

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
                            Descuento global
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={data.discount_amount}
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
                        <span className="font-semibold">Total</span>
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
                        <>
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
                            <div className="flex items-center justify-between text-[13.5px]">
                                <span className="font-medium text-muted-foreground">
                                    A pagar al proveedor
                                </span>
                                <b className="font-bold tabular-nums">
                                    <AmountDual
                                        amount={totals.payable}
                                        currency={data.currency}
                                        rate={data.exchange_rate || undefined}
                                        className="items-end"
                                    />
                                </b>
                            </div>
                        </>
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
                          ? 'Crear factura'
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
