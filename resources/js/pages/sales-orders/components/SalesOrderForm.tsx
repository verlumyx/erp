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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useSalesOrderFormContext } from '../contexts/SalesOrderFormContext';
import { SalesOrderLinesSection } from './SalesOrderLinesSection';

const NO_ADDRESS = 'none';
const NO_PRICE_LIST = 'none';
const NO_SALESPERSON = 'none';

export function SalesOrderForm() {
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
        selectClient,
        selectPriceList,
        selectCurrency,
    } = useSalesOrderFormContext();

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

    const priceListOptions: OptionType[] = [
        { value: NO_PRICE_LIST, label: 'Lista por defecto de la empresa' },
        ...options.priceLists.map((priceList) => ({
            value: priceList.id,
            label: priceList.name,
        })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Cabecera"
                sub="Cliente, bodega de despacho y fechas del pedido"
            >
                <FormFieldGrid>
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
                            error={!!errors.client_id}
                            size="md"
                            placeholder="Busca un cliente"
                        />
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
                                        (data.client_address_id || NO_ADDRESS),
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
                                        (data.salesperson_id || NO_SALESPERSON),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'salesperson_id',
                                    !option || option.value === NO_SALESPERSON
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
                            htmlFor="order_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha del pedido *
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
                            Fecha comprometida
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

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="client_reference"
                            className="text-[13px] font-semibold"
                        >
                            Orden de compra del cliente
                        </Label>
                        <Input
                            id="client_reference"
                            type="text"
                            value={data.client_reference}
                            onChange={(e) =>
                                setData('client_reference', e.target.value)
                            }
                            placeholder="Ej. OC-2026-0341"
                            maxLength={60}
                            className="h-[42px] rounded-[10px]"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Lista de precio
                        </Label>
                        <Select2
                            options={priceListOptions}
                            value={
                                priceListOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.price_list_id || NO_PRICE_LIST),
                                ) ?? null
                            }
                            onChange={(option) =>
                                selectPriceList(
                                    !option || option.value === NO_PRICE_LIST
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.price_list_id}
                            size="md"
                            placeholder="Lista por defecto de la empresa"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Cambiarla revalúa las líneas sin precio pactado
                        </span>
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Líneas"
                sub="Artículos pedidos: el precio queda congelado al guardar"
            >
                <SalesOrderLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Condiciones"
                sub="Moneda, tasa y crédito"
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
                        dateLabel="la fecha del pedido"
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
                            placeholder="Instrucciones de entrega, acuerdos con el cliente…"
                            className="rounded-[10px]"
                            rows={3}
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow
                    label="Cliente"
                    valueClassName="text-right font-bold"
                >
                    {clientOption?.label ??
                        (mode === 'create' ? 'Sin elegir' : '—')}
                </SummaryRow>
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
                    mode === 'create' ? 'Crear pedido' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
