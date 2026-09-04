import { TriangleAlert } from 'lucide-react';
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
import { useConfiguration } from '@/hooks/use-configuration';
import { useAdjustmentFormContext } from '../contexts/AdjustmentFormContext';
import {
    DIRECTION_LABELS,
    formatAmount,
    TYPE_LABELS,
    type AdjustmentDirection,
    type AdjustmentType,
} from '../types/Adjustment';
import { AdjustmentLinesSection } from './AdjustmentLinesSection';

const TYPE_OPTIONS: OptionType[] = Object.entries(TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

const DIRECTION_OPTIONS: OptionType[] = Object.entries(DIRECTION_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function AdjustmentForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        needsSecondApproval,
        isRevaluation,
        selectWarehouse,
        options,
    } = useAdjustmentFormContext();

    const currency = useConfiguration()?.base_currency ?? 'USD';

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del ajuste"
                sub="Qué bodega se corrige, por qué y en qué dirección"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo de ajuste *
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
                                setData(
                                    'type',
                                    (option?.value ??
                                        'physical_count') as AdjustmentType,
                                )
                            }
                            error={!!errors.type}
                            size="md"
                            placeholder="Por qué no cuadra la existencia"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            {isRevaluation
                                ? 'La revaluación no mueve cantidad: solo cambia lo que vale lo que ya está'
                                : 'La diferencia contra lo que dice el sistema es lo que entra o sale'}
                        </span>
                        {errors.type && (
                            <p className="text-sm text-bad">{errors.type}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Dirección *
                        </Label>
                        <Select2
                            inputId="direction"
                            options={DIRECTION_OPTIONS}
                            value={
                                DIRECTION_OPTIONS.find(
                                    (option) => option.value === data.direction,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'direction',
                                    (option?.value ??
                                        'mixed') as AdjustmentDirection,
                                )
                            }
                            error={!!errors.direction}
                            isDisabled={isRevaluation}
                            size="md"
                            placeholder="Qué se admite en las líneas"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Acotarla impide que una línea deje lo contrario de
                            lo declarado
                        </span>
                        {errors.direction && (
                            <p className="text-sm text-bad">
                                {errors.direction}
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
                            placeholder="Qué bodega se ajusta"
                        />
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="adjustment_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha del ajuste *
                        </Label>
                        <Input
                            id="adjustment_date"
                            type="date"
                            value={data.adjustment_date}
                            onChange={(e) =>
                                setData('adjustment_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.adjustment_date ? 'border-bad' : ''}`}
                        />
                        {errors.adjustment_date && (
                            <p className="text-sm text-bad">
                                {errors.adjustment_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="reason"
                            className="text-[13px] font-semibold"
                        >
                            Motivo *
                        </Label>
                        <Textarea
                            id="reason"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            rows={2}
                            maxLength={500}
                            placeholder="Por qué se ajusta la existencia"
                            className={`rounded-[10px] ${errors.reason ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Es el único documento que mueve inventario sin una
                            operación comercial detrás: sin motivo no se
                            registra
                        </span>
                        {errors.reason && (
                            <p className="text-sm text-bad">{errors.reason}</p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Líneas"
                sub="Qué existencia se cuenta, dónde y cuánto se encontró"
            >
                <AdjustmentLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Respaldo"
                sub="Con qué papel se sostiene el ajuste"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="count_id"
                            className="text-[13px] font-semibold"
                        >
                            Identificador del conteo
                        </Label>
                        <Input
                            id="count_id"
                            value={data.count_id}
                            onChange={(e) =>
                                setData('count_id', e.target.value)
                            }
                            maxLength={60}
                            placeholder="Número del conteo físico asociado"
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.count_id && (
                            <p className="text-sm text-bad">
                                {errors.count_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="attachment_path"
                            className="text-[13px] font-semibold"
                        >
                            Acta o evidencia
                        </Label>
                        <Input
                            id="attachment_path"
                            value={data.attachment_path}
                            onChange={(e) =>
                                setData('attachment_path', e.target.value)
                            }
                            maxLength={500}
                            placeholder="Ruta del acta de conteo"
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.attachment_path && (
                            <p className="text-sm text-bad">
                                {errors.attachment_path}
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
                {!isRevaluation && (
                    <>
                        <SummaryRow label="Unidades que entran">
                            {totals.quantityIn}
                        </SummaryRow>
                        <SummaryRow label="Unidades que salen">
                            {totals.quantityOut}
                        </SummaryRow>
                    </>
                )}
                <SummaryRow label="Valor que entra" divider>
                    {formatAmount(totals.costIn, currency)}
                </SummaryRow>
                <SummaryRow label="Valor que sale">
                    {formatAmount(totals.costOut, currency)}
                </SummaryRow>
                <SummaryRow
                    label="Impacto neto"
                    emphasis
                    valueClassName={`font-extrabold tabular-nums ${
                        totals.net < 0
                            ? 'text-bad'
                            : totals.net > 0
                              ? 'text-ok'
                              : ''
                    }`}
                >
                    {formatAmount(totals.net, currency)}
                </SummaryRow>
                {needsSecondApproval ? (
                    <p className="flex items-start gap-2 rounded-[10px] bg-warn-soft p-3 text-[12px] leading-relaxed text-warn">
                        <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                        Este impacto pasa del umbral de{' '}
                        {formatAmount(
                            Number(options.approval_threshold ?? 0),
                            currency,
                        )}
                        : lo tendrá que aprobar alguien distinto de ti.
                    </p>
                ) : (
                    <p className="text-[12px] leading-relaxed text-muted-foreground">
                        El ajuste nace en borrador. La existencia no cambia
                        hasta que alguien lo apruebe, y ahí se vuelve a
                        comprobar que el conteo siga vigente.
                    </p>
                )}
            </FormSummary>

            <FormActionBar
                headlineLabel="Impacto neto"
                headline={formatAmount(totals.net, currency)}
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear ajuste' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
