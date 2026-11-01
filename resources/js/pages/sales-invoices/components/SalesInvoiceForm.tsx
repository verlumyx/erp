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
        sourceType,
        setSourceType,
        sourceableCode,
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

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Empezar registro desde"
                sub="Selecciona si deseas iniciar la factura a partir de una orden de venta o cargarla directamente"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Origen de la factura
                        </Label>
                        {sourceType === 'pos_session' ? (
                            <Select2
                                inputId="source_type"
                                options={[
                                    {
                                        value: 'pos_session',
                                        label: 'Terminal POS',
                                    },
                                ]}
                                value={{
                                    value: 'pos_session',
                                    label: 'Terminal POS',
                                }}
                                onChange={() => {}}
                                isDisabled
                                size="md"
                            />
                        ) : (
                            <Select2
                                inputId="source_type"
                                options={[
                                    {
                                        value: 'none',
                                        label: 'Sin pedido previo / Venta directa',
                                    },
                                    {
                                        value: 'sales_order',
                                        label: 'Orden de venta',
                                    },
                                ]}
                                value={
                                    sourceType === 'sales_order'
                                        ? {
                                              value: 'sales_order',
                                              label: 'Orden de venta',
                                          }
                                        : {
                                              value: 'none',
                                              label: 'Sin pedido previo / Venta directa',
                                          }
                                }
                                onChange={(opt) => {
                                    const val = opt?.value ?? 'none';
                                    setSourceType(val);
                                    if (val === 'none') {
                                        selectSource(null);
                                    }
                                }}
                                isDisabled={mode === 'edit'}
                                size="md"
                            />
                        )}
                    </div>

                    {sourceType === 'pos_session' && (
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Sesión POS origen
                            </Label>
                            <Input
                                value={sourceableCode ? `Sesión POS: ${sourceableCode}` : 'Sesión de caja POS'}
                                disabled
                                className="h-[42px] rounded-[10px] bg-muted"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Factura generada automáticamente desde la terminal POS
                            </span>
                        </div>
                    )}

                    {sourceType === 'sales_order' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Pedido de venta *
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
                                placeholder="Busca una orden de venta"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Al elegir la orden se cargarán el cliente, bodega y líneas pendientes.
                            </span>
                            {(errors.sourceable_id || errors.sourceable_type) && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id ?? errors.sourceable_type}
                                </p>
                            )}
                        </div>
                    )}
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Datos de la factura"
                sub="Cliente, fechas y bodega de la factura"
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
                            isDisabled={sourceType === 'pos_session' || (sourceType === 'sales_order' && !!data.sourceable_id)}
                            size="md"
                            placeholder="Busca un cliente"
                        />
                        {sourceType === 'sales_order' && !!data.sourceable_id && (
                            <span className="text-[12px] text-muted-foreground">
                                Fijado por la orden de venta seleccionada
                            </span>
                        )}
                        {sourceType === 'pos_session' && (
                            <span className="text-[12px] text-muted-foreground">
                                Fijado por la terminal POS
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
                            htmlFor="invoice_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de la factura *
                        </Label>
                        <Input
                            id="invoice_date"
                            type="date"
                            value={data.invoice_date}
                            onChange={(e) => selectInvoiceDate(e.target.value)}
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Líneas"
                sub="Artículos facturados: el precio queda congelado al guardar"
            >
                <SalesInvoiceLinesSection />
            </FormSection>

            <FormSection
                step={4}
                title="Condiciones"
                sub="Moneda, tasa, forma de venta e inventario"
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
                            disabled={true}
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
                                    (option) => option.value === data.sale_type,
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
                            placeholder="Condiciones de pago, referencias al cliente…"
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
                {totals.withholdingAmount > 0 && (
                    <SummaryRow label="Retención del cliente">
                        <AmountDual
                            amount={totals.withholdingAmount}
                            currency={data.currency}
                            rate={data.exchange_rate || undefined}
                            className="items-end"
                        />
                    </SummaryRow>
                )}
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
                    mode === 'create' ? 'Crear factura' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
