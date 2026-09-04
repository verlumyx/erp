import { CurrencySelect } from '@/components/currency-select';
import { FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useExchangeRateFormContext } from '../contexts/ExchangeRateFormContext';
import type { ExchangeRateType } from '../types/ExchangeRate';
import { RATE_DECIMALS, TYPE_LABELS } from '../types/ExchangeRate';

const TYPE_OPTIONS: OptionType[] = (
    Object.keys(TYPE_LABELS) as ExchangeRateType[]
).map((type) => ({ value: type, label: TYPE_LABELS[type] }));

export function ExchangeRateForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useExchangeRateFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la tasa"
                sub="Moneda, fecha de vigencia y valor"
            >
                <div className="flex flex-col gap-4 p-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                                onValueChange={(value) =>
                                    setData('currency', value)
                                }
                            />
                            {errors.currency && (
                                <p className="text-sm text-bad">
                                    {errors.currency}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="rate_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de vigencia *
                            </Label>
                            <Input
                                id="rate_date"
                                type="date"
                                value={data.rate_date}
                                onChange={(e) =>
                                    setData('rate_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.rate_date ? 'border-bad' : ''}`}
                                required
                            />
                            {errors.rate_date && (
                                <p className="text-sm text-bad">
                                    {errors.rate_date}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="type"
                                className="text-[13px] font-semibold"
                            >
                                Tipo *
                            </Label>
                            <Select2
                                inputId="type"
                                options={TYPE_OPTIONS}
                                value={
                                    TYPE_OPTIONS.find(
                                        (option) => option.value === data.type,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData('type', option?.value ?? '')
                                }
                                error={!!errors.type}
                                size="md"
                                placeholder="Tipo"
                            />
                            {errors.type && (
                                <p className="text-sm text-bad">
                                    {errors.type}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="rate"
                                className="text-[13px] font-semibold"
                            >
                                Valor de la tasa *
                            </Label>
                            <CurrencyInput
                                id="rate"
                                value={
                                    data.rate === '' ? null : Number(data.rate)
                                }
                                onValueChange={(value) =>
                                    setData('rate', String(value))
                                }
                                decimals={RATE_DECIMALS}
                                min={0}
                                placeholder="Ej. 36,50000000"
                                className={`h-[42px] rounded-[10px] ${errors.rate ? 'border-bad' : ''}`}
                                required
                            />
                            {errors.rate && (
                                <p className="text-sm text-bad">
                                    {errors.rate}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="source"
                                className="text-[13px] font-semibold"
                            >
                                Fuente (opcional)
                            </Label>
                            <Input
                                id="source"
                                type="text"
                                value={data.source}
                                onChange={(e) =>
                                    setData('source', e.target.value)
                                }
                                placeholder="Ej. Banco Central"
                                className={`h-[42px] rounded-[10px] ${errors.source ? 'border-bad' : ''}`}
                                maxLength={150}
                            />
                            {errors.source && (
                                <p className="text-sm text-bad">
                                    {errors.source}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="description"
                            className="text-[13px] font-semibold"
                        >
                            Descripción (opcional)
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Notas sobre esta tasa…"
                            className={`rounded-[10px] ${errors.description ? 'border-bad' : ''}`}
                            rows={3}
                        />
                        {errors.description && (
                            <p className="text-sm text-bad">
                                {errors.description}
                            </p>
                        )}
                    </div>
                    <p className="text-[13px] text-muted-foreground">
                        Solo existe una tasa por moneda, fecha y tipo: si cargas
                        de nuevo la misma combinación se actualiza la existente.
                    </p>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Moneda">{data.currency}</SummaryRow>
                <SummaryRow label="Fecha">{data.rate_date || '—'}</SummaryRow>
                <SummaryRow label="Tipo">
                    {TYPE_LABELS[data.type as ExchangeRateType] ?? '—'}
                </SummaryRow>
                <SummaryRow label="Tasa">{data.rate || '—'}</SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear tasa' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
