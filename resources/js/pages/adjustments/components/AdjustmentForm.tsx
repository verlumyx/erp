import { Check, TriangleAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del ajuste"
                        sub="Qué bodega se corrige, por qué y en qué dirección"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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
                                <p className="text-sm text-bad">
                                    {errors.type}
                                </p>
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
                                        (option) =>
                                            option.value === data.direction,
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
                                Acotarla impide que una línea deje lo contrario
                                de lo declarado
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

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="reason"
                                className="text-[13px] font-semibold"
                            >
                                Motivo *
                            </Label>
                            <Textarea
                                id="reason"
                                value={data.reason}
                                onChange={(e) =>
                                    setData('reason', e.target.value)
                                }
                                rows={2}
                                maxLength={500}
                                placeholder="Por qué se ajusta la existencia"
                                className={`rounded-[10px] ${errors.reason ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Es el único documento que mueve inventario sin
                                una operación comercial detrás: sin motivo no se
                                registra
                            </span>
                            {errors.reason && (
                                <p className="text-sm text-bad">
                                    {errors.reason}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Qué existencia se cuenta, dónde y cuánto se encontró"
                    />
                    <AdjustmentLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Respaldo"
                        sub="Con qué papel se sostiene el ajuste"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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
                    {!isRevaluation && (
                        <>
                            <div className="flex items-center justify-between text-[13.5px]">
                                <span className="font-medium text-muted-foreground">
                                    Unidades que entran
                                </span>
                                <b className="font-bold tabular-nums">
                                    {totals.quantityIn}
                                </b>
                            </div>
                            <div className="flex items-center justify-between text-[13.5px]">
                                <span className="font-medium text-muted-foreground">
                                    Unidades que salen
                                </span>
                                <b className="font-bold tabular-nums">
                                    {totals.quantityOut}
                                </b>
                            </div>
                        </>
                    )}
                    <div className="flex items-center justify-between border-t pt-2.5 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Valor que entra
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.costIn, currency)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Valor que sale
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.costOut, currency)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[15px]">
                        <span className="font-semibold">Impacto neto</span>
                        <b
                            className={`font-extrabold tabular-nums ${
                                totals.net < 0
                                    ? 'text-bad'
                                    : totals.net > 0
                                      ? 'text-ok'
                                      : ''
                            }`}
                        >
                            {formatAmount(totals.net, currency)}
                        </b>
                    </div>
                </div>
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
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear ajuste'
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
