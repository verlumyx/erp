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
import { useSalesInvoiceFormContext } from '../contexts/SalesInvoiceFormContext';
import { SALE_TYPE_LABELS, type SaleType } from '../types/SalesInvoice';
import { SalesInvoiceLinesSection } from './SalesInvoiceLinesSection';

const NO_ADDRESS = 'none';
const NO_SALESPERSON = 'none';

const SALE_TYPE_OPTIONS: OptionType[] = Object.entries(SALE_TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

const INVENTORY_OPTIONS: OptionType[] = [
    { value: 'yes', label: 'Sí: la factura descarga el inventario' },
    { value: 'no', label: 'No: el stock ya salió con un despacho' },
];

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

export function SalesInvoiceForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        totals,
        client,
        clientLookupUrl,
        clientOption,
        sourceLookupUrl,
        sourceOption,
        selectClient,
        selectSource,
        selectInvoiceDate,
        selectCurrency,
    } = useSalesInvoiceFormContext();

    const addressOptions: OptionType[] = [
        { value: NO_ADDRESS, label: 'Dirección fiscal del cliente' },
        ...(client?.addresses ?? []).map((address) => ({
            value: address.id,
            label: `${address.name} — ${address.address}`,
        })),
    ];

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    const salespersonOptions: OptionType[] = [
        { value: NO_SALESPERSON, label: 'Sin vendedor' },
        ...options.salespeople.map((salesperson) => ({
            value: salesperson.id,
            label: salesperson.name,
        })),
    ];

    /** El pedido de origen manda el cliente: elegirlo aparte lo contradiría. */
    const fromOrder = data.sourceable_id !== '';

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Cabecera"
                        sub="Documento origen, cliente y fechas de la factura"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Pedido de origen
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={sourceLookupUrl}
                                value={sourceOption}
                                onChange={selectSource}
                                error={
                                    !!errors.sourceable_id ||
                                    !!errors.sourceable_type
                                }
                                size="md"
                                isClearable
                                placeholder="Busca un pedido confirmado, o déjalo vacío para una factura directa"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Elegirlo trae el cliente y las líneas que le
                                quedan por facturar
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
                                htmlFor="client_id"
                                className="text-[13px] font-semibold"
                            >
                                Cliente *
                            </Label>
                            <Select2Ajax
                                inputId="client_id"
                                url={clientLookupUrl}
                                value={clientOption}
                                onChange={selectClient}
                                isDisabled={fromOrder}
                                error={!!errors.client_id}
                                size="md"
                                placeholder="Busca un cliente"
                            />
                            {fromOrder && (
                                <span className="text-[12px] text-muted-foreground">
                                    Lo fija el pedido de origen
                                </span>
                            )}
                            {client?.credit_blocked === 'yes' && (
                                <span className="text-[12px] font-semibold text-warn">
                                    Este cliente tiene el crédito bloqueado
                                </span>
                            )}
                            {errors.client_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Dirección de entrega
                            </Label>
                            <Select2
                                options={addressOptions}
                                value={
                                    addressOptions.find(
                                        (option) =>
                                            option.value ===
                                            (data.client_address_id ||
                                                NO_ADDRESS),
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'client_address_id',
                                        !option || option.value === NO_ADDRESS
                                            ? ''
                                            : option.value,
                                    )
                                }
                                isDisabled={clientOption === null}
                                error={!!errors.client_address_id}
                                size="md"
                                placeholder="Dirección fiscal del cliente"
                            />
                            {errors.client_address_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_address_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega de despacho *
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
                            <Label className="text-[13px] font-semibold">
                                Vendedor
                            </Label>
                            <Select2
                                options={salespersonOptions}
                                value={
                                    salespersonOptions.find(
                                        (option) =>
                                            option.value ===
                                            (data.salesperson_id ||
                                                NO_SALESPERSON),
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'salesperson_id',
                                        !option ||
                                            option.value === NO_SALESPERSON
                                            ? ''
                                            : option.value,
                                    )
                                }
                                error={!!errors.salesperson_id}
                                size="md"
                                placeholder="Sin vendedor"
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="invoice_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de la factura *
                            </Label>
                            <Input
                                id="invoice_date"
                                type="date"
                                value={data.invoice_date}
                                onChange={(e) =>
                                    selectInvoiceDate(e.target.value)
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
                                htmlFor="due_date"
                                className="text-[13px] font-semibold"
                            >
                                Vence el *
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
                                Sale de los días de crédito del cliente
                            </span>
                            {errors.due_date && (
                                <p className="text-sm text-bad">
                                    {errors.due_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="invoice_series"
                                className="text-[13px] font-semibold"
                            >
                                Serie fiscal
                            </Label>
                            <Input
                                id="invoice_series"
                                type="text"
                                value={data.invoice_series}
                                onChange={(e) =>
                                    setData('invoice_series', e.target.value)
                                }
                                placeholder="Ej. A"
                                maxLength={20}
                                className="h-[42px] rounded-[10px]"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                El número fiscal se asigna al emitirla
                            </span>
                            {errors.invoice_series && (
                                <p className="text-sm text-bad">
                                    {errors.invoice_series}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Artículos facturados: el precio queda congelado al guardar"
                    />
                    <SalesInvoiceLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Condiciones"
                        sub="Moneda, tasa, forma de venta e inventario"
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
                                onValueChange={selectCurrency}
                                error={errors.currency}
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
                            <Label className="text-[13px] font-semibold">
                                Forma de venta
                            </Label>
                            <Select2
                                options={SALE_TYPE_OPTIONS}
                                value={
                                    SALE_TYPE_OPTIONS.find(
                                        (option) =>
                                            option.value === data.sale_type,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'sale_type',
                                        (option?.value ?? 'credit') as SaleType,
                                    )
                                }
                                error={!!errors.sale_type}
                                size="md"
                                placeholder="Crédito"
                            />
                            {errors.sale_type && (
                                <p className="text-sm text-bad">
                                    {errors.sale_type}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                ¿Descarga inventario?
                            </Label>
                            <Select2
                                options={INVENTORY_OPTIONS}
                                value={
                                    INVENTORY_OPTIONS.find(
                                        (option) =>
                                            option.value ===
                                            data.affects_inventory,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'affects_inventory',
                                        (option?.value ?? 'yes') as
                                            | 'yes'
                                            | 'no',
                                    )
                                }
                                error={!!errors.affects_inventory}
                                size="md"
                                placeholder="Sí"
                            />
                            {errors.affects_inventory && (
                                <p className="text-sm text-bad">
                                    {errors.affects_inventory}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="freight_amount"
                                className="text-[13px] font-semibold"
                            >
                                Flete cobrado
                            </Label>
                            <CurrencyInput
                                id="freight_amount"
                                value={data.freight_amount}
                                onValueChange={(value) =>
                                    setData('freight_amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.freight_amount ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Suma al total de la factura
                            </span>
                            {errors.freight_amount && (
                                <p className="text-sm text-bad">
                                    {errors.freight_amount}
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
                                placeholder="Condiciones de pago, referencias al cliente…"
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
                            Cliente
                        </span>
                        <b className="text-right font-bold">
                            {clientOption?.label ??
                                (mode === 'create' ? 'Sin elegir' : '—')}
                        </b>
                    </div>
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
                    {totals.freight > 0 && (
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Flete
                            </span>
                            <b className="font-bold tabular-nums">
                                <AmountDual
                                    amount={totals.freight}
                                    currency={data.currency}
                                    rate={data.exchange_rate || undefined}
                                    className="items-end"
                                />
                            </b>
                        </div>
                    )}
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
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Retención del cliente
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
