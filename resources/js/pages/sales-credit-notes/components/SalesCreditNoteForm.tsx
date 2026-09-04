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
import { useSalesCreditNoteFormContext } from '../contexts/SalesCreditNoteFormContext';
import {
    REASON_LABELS,
    type SalesCreditNoteReason,
} from '../types/SalesCreditNote';
import { SalesCreditNoteLinesSection } from './SalesCreditNoteLinesSection';

const REASON_OPTIONS: OptionType[] = Object.entries(REASON_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function SalesCreditNoteForm() {
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
    } = useSalesCreditNoteFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la nota"
                sub="A quién se le acredita, contra qué factura y por qué"
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
                            Factura afectada
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
                            Déjala vacía si la nota no corrige una factura
                        </span>
                        {errors.sales_invoice_id && (
                            <p className="text-sm text-bad">
                                {errors.sales_invoice_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="note_series"
                            className="text-[13px] font-semibold"
                        >
                            Serie fiscal
                        </Label>
                        <Input
                            id="note_series"
                            type="text"
                            value={data.note_series}
                            onChange={(e) =>
                                setData('note_series', e.target.value)
                            }
                            placeholder="La serie autorizada"
                            maxLength={20}
                            className={`h-[42px] rounded-[10px] ${errors.note_series ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            El correlativo se asigna al confirmar la nota
                        </span>
                        {errors.note_series && (
                            <p className="text-sm text-bad">
                                {errors.note_series}
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
                                    (option) => option.value === data.reason,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'reason',
                                    (option?.value ??
                                        'return') as SalesCreditNoteReason,
                                )
                            }
                            error={!!errors.reason}
                            size="md"
                            placeholder="Por qué se emite"
                        />
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
                sub="Qué se acredita, en qué unidad y a qué precio"
            >
                <SalesCreditNoteLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Condiciones"
                sub="Moneda, tasa y notas del documento"
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
                        dateLabel="la fecha de la nota"
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
                <SummaryRow label="Total acreditado" divider emphasis>
                    <AmountDual
                        amount={totals.total}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                {totals.withholdingAmount > 0 && (
                    <SummaryRow label="Retención">
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
                headlineLabel="Total acreditado"
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
                    mode === 'create' ? 'Crear nota' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
