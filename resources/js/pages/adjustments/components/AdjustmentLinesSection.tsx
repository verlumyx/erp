import { ChevronDown, Layers, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useConfiguration } from '@/hooks/use-configuration';
import { cn } from '@/lib/utils';
import { useAdjustmentFormContext } from '../contexts/AdjustmentFormContext';
import { formatAmount } from '../types/Adjustment';
import { AdjustmentLineTraceabilityDialog } from './AdjustmentLineTraceabilityDialog';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * 5.2 Líneas del ajuste. Cada fila fija qué existencia se cuenta —artículo,
 * unidad y ubicación— y cuánto se encontró. La existencia del sistema no se
 * captura: la trae el servidor, y contra ella se calcula la diferencia. En una
 * revaluación no se cuenta nada: lo que se escribe es el costo nuevo.
 *
 * El lote y la serie no caben en la fila: un mismo artículo se cuenta repartido
 * en varias cajas y con varias unidades identificadas, así que viven en el
 * detalle de la línea, en su propio modal.
 */
export function AdjustmentLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        amountsOf,
        isRevaluation,
        locations,
        options,
    } = useAdjustmentFormContext();

    const currency = useConfiguration()?.base_currency ?? 'USD';

    const locationOptions: OptionType[] = [
        { value: DEFAULT_LOCATION, label: 'Toda la bodega' },
        ...locations.map((location) => ({
            value: location.id,
            label:
                location.is_default === 'yes'
                    ? `${location.name} (por defecto)`
                    : location.name,
        })),
    ];

    const counterOptions: OptionType[] = options.counters.map((counter) => ({
        value: counter.id,
        label: counter.name,
    }));

    const [openDetail, setOpenDetail] = useState<Record<string, boolean>>({});

    const toggleDetail = (lineId: string) =>
        setOpenDetail((current) => ({
            ...current,
            [lineId]: !current[lineId],
        }));

    /** La línea cuyo detalle de lotes y series está abierto en el modal. */
    const [traceabilityOf, setTraceabilityOf] = useState<number | null>(null);

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const unitsOf = (itemId: string) => catalog.itemOf(itemId)?.units ?? [];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            {data.lines.map((line, index) => {
                const amounts = amountsOf(line);
                const units = unitsOf(line.item_id);
                const unitOptions: OptionType[] = units.map((unit) => ({
                    value: unit.measurement_unit_id,
                    label: unit.name,
                }));

                const lots = line.lots.filter((lot) => lot.status === 'active');
                const serials = line.serials.filter(
                    (serial) => serial.status === 'active',
                );

                const detailOpen = openDetail[line.id] === true;
                const hasDetail =
                    line.location_id !== '' ||
                    line.reason !== '' ||
                    line.counted_by !== '' ||
                    lots.length > 0 ||
                    serials.length > 0;

                return (
                    <div
                        key={line.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="grid grid-cols-1 items-start gap-3 sm:grid-cols-2 lg:grid-cols-[2.4fr_1.2fr_1fr_1.2fr_auto]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Artículo *
                                </Label>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className={cn(
                                            'relative size-[42px] shrink-0 rounded-[10px] bg-card',
                                            hasDetail && 'text-primary',
                                        )}
                                        onClick={() => toggleDetail(line.id)}
                                        aria-expanded={detailOpen}
                                        aria-label={`${
                                            detailOpen ? 'Ocultar' : 'Mostrar'
                                        } ubicación y trazabilidad de la línea ${index + 1}`}
                                    >
                                        <ChevronDown
                                            className={cn(
                                                'size-4 transition-transform',
                                                detailOpen && 'rotate-180',
                                            )}
                                        />
                                        {hasDetail && !detailOpen && (
                                            <span className="absolute top-1.5 right-1.5 size-[7px] rounded-full bg-primary ring-2 ring-card" />
                                        )}
                                    </Button>

                                    <div className="min-w-0 flex-1">
                                        <Select2Ajax
                                            url={catalog.url}
                                            value={catalog.optionOf(
                                                line.item_id,
                                            )}
                                            onChange={(option) =>
                                                setLineItem(index, option)
                                            }
                                            formatLabel={catalog.labelOf}
                                            error={
                                                !!fieldError(index, 'item_id')
                                            }
                                            size="md"
                                            placeholder="Busca por código o nombre"
                                        />
                                    </div>
                                </div>
                                {fieldError(index, 'item_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'item_id')}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Unidad *
                                </Label>
                                <Select2
                                    options={unitOptions}
                                    value={
                                        unitOptions.find(
                                            (option) =>
                                                option.value ===
                                                line.measurement_unit_id,
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        updateLine(
                                            index,
                                            'measurement_unit_id',
                                            option?.value ?? '',
                                        )
                                    }
                                    isDisabled={units.length === 0}
                                    error={
                                        !!fieldError(
                                            index,
                                            'measurement_unit_id',
                                        )
                                    }
                                    size="md"
                                    placeholder="Unidad"
                                />
                                {fieldError(index, 'measurement_unit_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(
                                            index,
                                            'measurement_unit_id',
                                        )}
                                    </p>
                                )}
                            </div>

                            {/**
                             * Lo que el sistema cree tener. No se edita aquí:
                             * lo resuelve el servidor, y un ajuste que dejara
                             * declararlo no probaría nada.
                             */}
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Sistema
                                </Label>
                                <Input
                                    value={
                                        line.item_id === ''
                                            ? '—'
                                            : amounts.system
                                    }
                                    readOnly
                                    disabled
                                    className="h-[42px] rounded-[10px] tabular-nums"
                                />
                                <span className="text-[12px] text-muted-foreground">
                                    {lots.length > 0
                                        ? 'Suma de sus lotes'
                                        : 'Lo que dice el sistema'}
                                </span>
                            </div>

                            {isRevaluation ? (
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Costo nuevo *
                                    </Label>
                                    <CurrencyInput
                                        value={line.unit_cost}
                                        onValueChange={(value) =>
                                            updateLine(
                                                index,
                                                'unit_cost',
                                                value,
                                            )
                                        }
                                        min={0}
                                        decimals={6}
                                        className={`h-[42px] rounded-[10px] ${
                                            fieldError(index, 'unit_cost')
                                                ? 'border-bad'
                                                : ''
                                        }`}
                                    />
                                    <span className="text-[12px] text-muted-foreground">
                                        Hoy vale{' '}
                                        {formatAmount(
                                            amounts.average,
                                            currency,
                                        )}{' '}
                                        por unidad base
                                    </span>
                                    {fieldError(index, 'unit_cost') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'unit_cost')}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Contado *
                                    </Label>
                                    <NumberInput
                                        value={line.counted_quantity}
                                        onValueChange={(value) =>
                                            updateLine(
                                                index,
                                                'counted_quantity',
                                                value,
                                            )
                                        }
                                        min={0}
                                        decimals={4}
                                        className={`h-[42px] rounded-[10px] ${
                                            fieldError(
                                                index,
                                                'counted_quantity',
                                            )
                                                ? 'border-bad'
                                                : ''
                                        }`}
                                    />
                                    {fieldError(index, 'counted_quantity') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(
                                                index,
                                                'counted_quantity',
                                            )}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-col gap-1.5">
                                <Label
                                    aria-hidden
                                    className="text-[13px] font-semibold opacity-0 select-none"
                                >
                                    &nbsp;
                                </Label>
                                <div className="flex gap-2">
                                    <LineNotePopover
                                        value={line.notes}
                                        onValueChange={(value) =>
                                            updateLine(index, 'notes', value)
                                        }
                                        ariaLabel={`Nota de la línea ${index + 1}`}
                                    />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-[42px] rounded-[10px] bg-card"
                                        onClick={() => removeLine(index)}
                                        aria-label="Quitar línea"
                                    >
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {detailOpen && (
                            <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Ubicación
                                    </Label>
                                    <Select2
                                        options={locationOptions}
                                        value={
                                            locationOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    (line.location_id ||
                                                        DEFAULT_LOCATION),
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            updateLine(
                                                index,
                                                'location_id',
                                                !option ||
                                                    option.value ===
                                                        DEFAULT_LOCATION
                                                    ? ''
                                                    : option.value,
                                            )
                                        }
                                        error={
                                            !!fieldError(index, 'location_id')
                                        }
                                        size="md"
                                        placeholder="Toda la bodega"
                                    />
                                    {fieldError(index, 'location_id') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'location_id')}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Lotes y series
                                    </Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-[42px] justify-start rounded-[10px] bg-card font-semibold"
                                        onClick={() => setTraceabilityOf(index)}
                                    >
                                        <Layers />
                                        {lots.length === 0 &&
                                        serials.length === 0
                                            ? 'Sin trazabilidad'
                                            : `${lots.length} lote${
                                                  lots.length !== 1 ? 's' : ''
                                              } · ${serials.length} serie${
                                                  serials.length !== 1
                                                      ? 's'
                                                      : ''
                                              }`}
                                    </Button>
                                    {(fieldError(index, 'lots') ||
                                        fieldError(index, 'serials')) && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'lots') ??
                                                fieldError(index, 'serials')}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Contado por
                                    </Label>
                                    <Select2
                                        options={counterOptions}
                                        value={
                                            counterOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    line.counted_by,
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            updateLine(
                                                index,
                                                'counted_by',
                                                option?.value ?? '',
                                            )
                                        }
                                        error={
                                            !!fieldError(index, 'counted_by')
                                        }
                                        isClearable
                                        size="md"
                                        placeholder="Quién contó la línea"
                                    />
                                    {fieldError(index, 'counted_by') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'counted_by')}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Motivo de la línea
                                    </Label>
                                    <Input
                                        value={line.reason}
                                        onChange={(e) =>
                                            updateLine(
                                                index,
                                                'reason',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={500}
                                        placeholder="Cuando difiere del motivo del ajuste"
                                        className={`h-[42px] rounded-[10px] ${
                                            fieldError(index, 'reason')
                                                ? 'border-bad'
                                                : ''
                                        }`}
                                    />
                                    {fieldError(index, 'reason') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'reason')}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="flex flex-wrap justify-end gap-x-5 gap-y-1 border-t pt-3 text-[13px]">
                            {!isRevaluation && (
                                <span className="text-muted-foreground">
                                    Diferencia{' '}
                                    <b
                                        className={cn(
                                            'font-bold tabular-nums',
                                            amounts.difference < 0
                                                ? 'text-bad'
                                                : amounts.difference > 0
                                                  ? 'text-ok'
                                                  : 'text-foreground',
                                        )}
                                    >
                                        {amounts.difference > 0 ? '+' : ''}
                                        {amounts.difference}
                                    </b>
                                </span>
                            )}
                            <span className="text-muted-foreground">
                                Costo{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(amounts.unitCost, currency)}
                                </b>
                            </span>
                            <span className="text-muted-foreground">
                                Impacto{' '}
                                <b
                                    className={cn(
                                        'font-bold tabular-nums',
                                        amounts.impact < 0
                                            ? 'text-bad'
                                            : amounts.impact > 0
                                              ? 'text-ok'
                                              : 'text-foreground',
                                    )}
                                >
                                    {formatAmount(amounts.impact, currency)}
                                </b>
                            </span>
                        </div>
                    </div>
                );
            })}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addLine}
            >
                <Plus />
                Agregar línea
            </Button>

            <AdjustmentLineTraceabilityDialog
                index={traceabilityOf}
                onClose={() => setTraceabilityOf(null)}
            />
        </div>
    );
}
