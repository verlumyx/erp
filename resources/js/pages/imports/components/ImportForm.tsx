import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
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
import { useImportFormContext } from '../contexts/ImportFormContext';
import {
    ALLOCATION_METHOD_LABELS,
    formatAmount,
    type ImportAllocationMethod,
} from '../types/Import';
import { ImportCostsSection } from './ImportCostsSection';
import { ImportEntriesSection } from './ImportEntriesSection';
import { ImportLinesSection } from './ImportLinesSection';

const METHOD_OPTIONS: OptionType[] = Object.entries(
    ALLOCATION_METHOD_LABELS,
).map(([value, label]) => ({ value, label }));

const METHOD_HINTS: Record<ImportAllocationMethod, string> = {
    value: 'Cada dólar que entró carga con la misma parte del gasto',
    quantity: 'Cada unidad carga lo mismo, valga lo que valga',
    weight: 'Exige que el artículo traiga su peso registrado',
    volume: 'Exige que el artículo traiga su volumen registrado',
};

export function ImportForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        options,
        selectWarehouse,
    } = useImportFormContext();

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del expediente"
                sub="Qué embarque se costea, dónde se revaloriza y cómo se reparte"
            >
                <FormFieldGrid>
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
                            placeholder="Dónde se revaloriza"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Una sola por expediente: el ajuste que genera lleva
                            una sola en su cabecera
                        </span>
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="import_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha del expediente *
                        </Label>
                        <Input
                            id="import_date"
                            type="date"
                            value={data.import_date}
                            onChange={(e) =>
                                setData('import_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.import_date ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Es la que lleva el ajuste que genera
                        </span>
                        {errors.import_date && (
                            <p className="text-sm text-bad">
                                {errors.import_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="arrival_date"
                            className="text-[13px] font-semibold"
                        >
                            Llegada del embarque
                        </Label>
                        <Input
                            id="arrival_date"
                            type="date"
                            value={data.arrival_date}
                            onChange={(e) =>
                                setData('arrival_date', e.target.value)
                            }
                            className="h-[42px] rounded-[10px]"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="reference"
                            className="text-[13px] font-semibold"
                        >
                            Referencia
                        </Label>
                        <Input
                            id="reference"
                            value={data.reference}
                            onChange={(e) =>
                                setData('reference', e.target.value)
                            }
                            maxLength={60}
                            placeholder="Conocimiento de embarque o guía aérea"
                            className="h-[42px] rounded-[10px]"
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Reparto *
                        </Label>
                        <Select2
                            inputId="allocation_method"
                            options={METHOD_OPTIONS}
                            value={
                                METHOD_OPTIONS.find(
                                    (option) =>
                                        option.value === data.allocation_method,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'allocation_method',
                                    (option?.value ??
                                        'value') as ImportAllocationMethod,
                                )
                            }
                            error={!!errors.allocation_method}
                            size="md"
                            placeholder="Cómo se reparte el gasto"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            {METHOD_HINTS[data.allocation_method]}
                        </span>
                        {errors.allocation_method && (
                            <p className="text-sm text-bad">
                                {errors.allocation_method}
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
                            onValueChange={(value) =>
                                setData('currency', value)
                            }
                            error={errors.currency}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            A ella se convierte cada costo
                        </span>
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
                        dateLabel="la fecha del expediente"
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
                            rows={3}
                            className="rounded-[10px]"
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Costos"
                sub="Lo que costó traer la mercancía, con el papel que lo respalda"
            >
                <ImportCostsSection />
            </FormSection>

            <FormSection
                step={3}
                title="Recepciones"
                sub="Qué llegó de ese embarque: es lo que va a absorber el gasto"
            >
                <ImportEntriesSection />
            </FormSection>

            <FormSection
                step={4}
                title="Ítems"
                sub="Se derivan de las recepciones; solo se decide cuáles entran"
            >
                <ImportLinesSection />
            </FormSection>

            <FormSummary>
                <SummaryRow label="Cobros">
                    {
                        data.costs.filter((cost) => cost.status === 'active')
                            .length
                    }
                </SummaryRow>
                <SummaryRow label="Recepciones">
                    {data.entries.length}
                </SummaryRow>
                <SummaryRow label="Valor de la mercancía" divider>
                    {formatAmount(totals.baseValue, data.currency)}
                </SummaryRow>
                <SummaryRow label="Gasto a repartir">
                    {formatAmount(totals.charges, data.currency)}
                </SummaryRow>
                <SummaryRow label="Puesto en bodega" emphasis>
                    {formatAmount(totals.landedValue, data.currency)}
                </SummaryRow>
                <SummaryRow
                    label="Va al inventario"
                    divider
                    valueClassName="text-ok"
                >
                    {formatAmount(totals.capitalized, data.currency)}
                </SummaryRow>
                <SummaryRow label="Va a gasto" valueClassName="text-warn">
                    {formatAmount(totals.variance, data.currency)}
                </SummaryRow>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    El expediente nace en borrador y no toca el kardex.
                    Confirmarlo genera un ajuste de revaluación, también en
                    borrador, y es ese ajuste el que cambia lo que vale el
                    inventario.
                </p>
            </FormSummary>

            <FormActionBar
                headlineLabel="Puesto en bodega"
                headline={formatAmount(totals.landedValue, data.currency)}
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear expediente' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
