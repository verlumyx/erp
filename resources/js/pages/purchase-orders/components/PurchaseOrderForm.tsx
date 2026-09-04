import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { usePurchaseOrderFormContext } from '../contexts/PurchaseOrderFormContext';
import { PurchaseOrderLinesSection } from './PurchaseOrderLinesSection';

export function PurchaseOrderForm() {
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
        selectCurrency,
    } = usePurchaseOrderFormContext();

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la orden"
                sub="A quién se le pide, dónde se recibe y cuándo"
            >
                <FormFieldGrid>
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
                        <Label className="text-[13px] font-semibold">
                            Bodega de recepción *
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
                            htmlFor="order_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de emisión *
                        </Label>
                        <Input
                            id="order_date"
                            type="date"
                            value={data.order_date}
                            onChange={(e) =>
                                setData('order_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.order_date ? 'border-bad' : ''}`}
                        />
                        {errors.order_date && (
                            <p className="text-sm text-bad">
                                {errors.order_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="expected_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha estimada de entrega
                        </Label>
                        <Input
                            id="expected_date"
                            type="date"
                            value={data.expected_date}
                            onChange={(e) =>
                                setData('expected_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.expected_date ? 'border-bad' : ''}`}
                        />
                        {errors.expected_date && (
                            <p className="text-sm text-bad">
                                {errors.expected_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="supplier_reference"
                            className="text-[13px] font-semibold"
                        >
                            Referencia del proveedor
                        </Label>
                        <Input
                            id="supplier_reference"
                            type="text"
                            value={data.supplier_reference}
                            onChange={(e) =>
                                setData('supplier_reference', e.target.value)
                            }
                            placeholder="Número de cotización o pedido del proveedor"
                            maxLength={60}
                            className={`h-[42px] rounded-[10px] ${errors.supplier_reference ? 'border-bad' : ''}`}
                        />
                        {errors.supplier_reference && (
                            <p className="text-sm text-bad">
                                {errors.supplier_reference}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Líneas"
                sub="Qué se pide, en qué unidad y a qué costo"
            >
                <PurchaseOrderLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Condiciones"
                sub="Moneda, tasa, crédito y descuento global"
            >
                <FormFieldGrid>
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
                        dateLabel="la fecha de la orden"
                        error={errors.exchange_rate}
                    />

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="payment_term_days"
                            className="text-[13px] font-semibold"
                        >
                            Días de crédito
                        </Label>
                        <NumberInput
                            id="payment_term_days"
                            value={data.payment_term_days}
                            onValueChange={(value) =>
                                setData('payment_term_days', value)
                            }
                            min={0}
                            decimals={0}
                            className={`h-[42px] rounded-[10px] ${errors.payment_term_days ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            0 = contado
                        </span>
                        {errors.payment_term_days && (
                            <p className="text-sm text-bad">
                                {errors.payment_term_days}
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

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="notes"
                            className="text-[13px] font-semibold"
                        >
                            Notas
                        </Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            className="rounded-[10px]"
                            rows={3}
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Líneas">{data.lines.length}</SummaryRow>
                <SummaryRow label="Bruto">
                    <AmountDual
                        amount={totals.gross}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <SummaryRow label="Descuento de líneas">
                    <AmountDual
                        amount={totals.discountAmount}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <SummaryRow label="Subtotal">
                    <AmountDual
                        amount={totals.subtotal}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <SummaryRow label="Descuento global">
                    <AmountDual
                        amount={data.discount_amount}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <SummaryRow label="Impuesto">
                    <AmountDual
                        amount={totals.taxAmount}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <SummaryRow label="Total" divider emphasis>
                    <AmountDual
                        amount={totals.total}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                headlineLabel="Total"
                headline={
                    <AmountDual
                        amount={totals.total}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-start"
                    />
                }
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear orden' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
