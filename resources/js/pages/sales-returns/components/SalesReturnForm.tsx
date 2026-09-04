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
import { useSalesReturnFormContext } from '../contexts/SalesReturnFormContext';
import {
    CONDITION_LABELS,
    REASON_LABELS,
    type SalesReturnCondition,
    type SalesReturnReason,
} from '../types/SalesReturn';
import { SalesReturnLinesSection } from './SalesReturnLinesSection';

const REASON_OPTIONS: OptionType[] = Object.entries(REASON_LABELS).map(
    ([value, label]) => ({ value, label }),
);

const CONDITION_OPTIONS: OptionType[] = Object.entries(CONDITION_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function SalesReturnForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        clientLookupUrl,
        clientOption,
        selectClient,
        invoiceLookupUrl,
        invoiceOption,
        selectInvoice,
        selectCurrency,
        selectCondition,
        selectWarehouse,
        warehouses,
        options,
    } = useSalesReturnFormContext();

    /** Solo las bodegas que la condición permite: lo dañado va a cuarentena. */
    const warehouseOptions: OptionType[] = warehouses.map((warehouse) => ({
        value: warehouse.id,
        label: warehouse.name,
    }));

    const receiverOptions: OptionType[] = options.receivers.map((receiver) => ({
        value: receiver.id,
        label: receiver.name,
    }));

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la devolución"
                sub="Quién devuelve, de qué factura sale y por qué"
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
                        {errors.client_id && (
                            <p className="text-sm text-bad">
                                {errors.client_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="sales_invoice_id"
                            className="text-[13px] font-semibold"
                        >
                            Factura de origen
                        </Label>
                        <Select2Ajax
                            inputId="sales_invoice_id"
                            url={invoiceLookupUrl}
                            params={{ client_id: data.client_id }}
                            value={invoiceOption}
                            onChange={selectInvoice}
                            error={!!errors.sales_invoice_id}
                            isClearable
                            isDisabled={data.client_id === ''}
                            size="md"
                            placeholder={
                                data.client_id === ''
                                    ? 'Elige antes el cliente'
                                    : 'Sin factura concreta'
                            }
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Atarla limita lo devuelto a lo que se facturó
                        </span>
                        {errors.sales_invoice_id && (
                            <p className="text-sm text-bad">
                                {errors.sales_invoice_id}
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
                            placeholder="A dónde entra la mercancía"
                        />
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Estado de la mercancía *
                        </Label>
                        <Select2
                            inputId="condition"
                            options={CONDITION_OPTIONS}
                            value={
                                CONDITION_OPTIONS.find(
                                    (option) => option.value === data.condition,
                                ) ?? null
                            }
                            onChange={(option) =>
                                selectCondition(
                                    (option?.value ??
                                        'resalable') as SalesReturnCondition,
                                )
                            }
                            error={!!errors.condition}
                            size="md"
                            placeholder="En qué estado vuelve"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Lo dañado reingresa a cuarentena; lo que se destruye
                            no reingresa
                        </span>
                        {errors.condition && (
                            <p className="text-sm text-bad">
                                {errors.condition}
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

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label className="text-[13px] font-semibold">
                            Motivo *
                        </Label>
                        <Select2
                            options={REASON_OPTIONS}
                            value={
                                REASON_OPTIONS.find(
                                    (option) => option.value === data.reason,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'reason',
                                    (option?.value ??
                                        'damaged') as SalesReturnReason,
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
                            <p className="text-sm text-bad">{errors.reason}</p>
                        )}
                    </div>

                    {data.reason === 'other' && (
                        <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Líneas"
                sub="Qué vuelve, cuánto y a qué bodega reingresa"
            >
                <SalesReturnLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Recepción y condiciones"
                sub="Quién recibió, moneda, tasa y notas del documento"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label className="text-[13px] font-semibold">
                            Recibido por
                        </Label>
                        <Select2
                            inputId="received_by"
                            options={receiverOptions}
                            value={
                                receiverOptions.find(
                                    (option) =>
                                        option.value === data.received_by,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData('received_by', option?.value ?? '')
                            }
                            error={!!errors.received_by}
                            isClearable
                            size="md"
                            placeholder="Quién recibió la mercancía"
                        />
                        {errors.received_by && (
                            <p className="text-sm text-bad">
                                {errors.received_by}
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
                <SummaryRow label="Unidades">{totals.quantity}</SummaryRow>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    La devolución solo decide qué vuelve y cuánto: el precio con
                    el que se acredita y el costo con el que reingresa los trae
                    la venta original. Al confirmarla se genera la entrada que
                    recibe la mercancía, y es esa entrada la que toca el
                    inventario.
                </p>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear devolución' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
