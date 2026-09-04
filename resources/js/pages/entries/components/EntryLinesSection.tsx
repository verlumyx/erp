import { ChevronDown, Layers, Loader2, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useLineDetailPanels } from '@/hooks/use-line-detail-panels';
import { cn } from '@/lib/utils';
import { useEntryFormContext } from '../contexts/EntryFormContext';
import { acceptedQuantity } from '../hooks/useEntryForm';
import { EntryLineTraceabilityDialog } from './EntryLineTraceabilityDialog';

/** Valor del select cuando la línea no sale de ninguna línea de orden. */
const NO_ORDER_LINE = 'none';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * 3.2 Líneas de la entrada. Cada fila dice qué artículo llega, en qué unidad,
 * cuánto se pidió y cuánto se recibe. Nada más: el costo, el impuesto y el
 * descuento se decidieron en la orden de compra y los pone el backend, y la
 * ubicación, el rechazo y la trazabilidad viven en el detalle de la línea.
 */
export function EntryLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineOrderLine,
        orderLines,
        pendingOf,
        loadingOrderLines,
        orderLinesFailed,
        orderedQuantityOf,
        locations,
    } = useEntryFormContext();

    const locationOptions: OptionType[] = [
        { value: DEFAULT_LOCATION, label: 'Ubicación por defecto' },
        ...locations.map((location) => ({
            value: location.id,
            label:
                location.is_default === 'yes'
                    ? `${location.name} (por defecto)`
                    : location.name,
        })),
    ];

    /** Las líneas de la orden elegida, para atar cada línea a la suya. */
    const orderLineOptions: OptionType[] = [
        { value: NO_ORDER_LINE, label: 'Sin línea de la orden' },
        ...orderLines.map((line) => ({
            value: line.id,
            label: `#${line.line_number} · ${line.item_code ?? ''} ${
                line.item_name ?? ''
            } · quedan ${pendingOf(line.id)}`.trim(),
        })),
    ];

    const detail = useLineDetailPanels(
        data.lines,
        errors as Record<string, string | undefined>,
    );

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

            {loadingOrderLines && (
                <p className="flex items-center gap-2 text-[13px] text-muted-foreground">
                    <Loader2 className="size-4 animate-spin" />
                    Trayendo lo que la orden tiene por llegar…
                </p>
            )}

            {orderLinesFailed && (
                <p className="text-[13px] text-warn">
                    No se pudieron traer las líneas de la orden. Vuelve a
                    elegirla o captúralas a mano.
                </p>
            )}

            {data.lines.map((line, index) => {
                const units = unitsOf(line.item_id);
                const unitOptions: OptionType[] = units.map((unit) => ({
                    value: unit.measurement_unit_id,
                    label: unit.name,
                }));

                const lots = line.lots.filter((lot) => lot.status === 'active');
                const serials = line.serials.filter(
                    (serial) => serial.status === 'active',
                );

                const detailOpen = detail.isOpen(line.id);
                const hasDetail =
                    line.location_id !== '' ||
                    line.rejected_quantity > 0 ||
                    line.sourceable_id !== '' ||
                    lots.length > 0 ||
                    serials.length > 0;

                const ordered = orderedQuantityOf(line);
                const pending = pendingOf(line.sourceable_id);

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
                                        onClick={() => detail.toggle(line.id)}
                                        aria-expanded={detailOpen}
                                        aria-label={`${
                                            detailOpen ? 'Ocultar' : 'Mostrar'
                                        } ubicación, rechazo y trazabilidad de la línea ${index + 1}`}
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
                                            params={{ is_purchasable: 'yes' }}
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
                             * Lo que pidió la orden. No se edita aquí: se
                             * cambia en la orden de compra, no al recibir.
                             */}
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Cantidad
                                </Label>
                                <Input
                                    value={
                                        ordered ??
                                        (line.sourceable_id === '' ? '—' : '…')
                                    }
                                    readOnly
                                    disabled
                                    className="h-[42px] rounded-[10px] tabular-nums"
                                />
                                <span className="text-[12px] text-muted-foreground">
                                    {ordered === null &&
                                    line.sourceable_id === ''
                                        ? 'Sin orden'
                                        : 'Lo pedido'}
                                </span>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Cantidad a recibir *
                                </Label>
                                <NumberInput
                                    value={line.quantity}
                                    onValueChange={(value) =>
                                        updateLine(index, 'quantity', value)
                                    }
                                    min={0}
                                    decimals={4}
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'quantity')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {pending > 0 && (
                                    <span className="text-[12px] text-muted-foreground">
                                        Quedan {pending} por recibir
                                    </span>
                                )}
                                {fieldError(index, 'quantity') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'quantity')}
                                    </p>
                                )}
                            </div>

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
                                        placeholder="Ubicación por defecto"
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
                                        Cantidad rechazada
                                    </Label>
                                    <NumberInput
                                        value={line.rejected_quantity}
                                        onValueChange={(value) =>
                                            updateLine(
                                                index,
                                                'rejected_quantity',
                                                value,
                                            )
                                        }
                                        min={0}
                                        max={line.quantity}
                                        decimals={4}
                                        className={`h-[42px] rounded-[10px] ${
                                            fieldError(
                                                index,
                                                'rejected_quantity',
                                            )
                                                ? 'border-bad'
                                                : ''
                                        }`}
                                    />
                                    <span className="text-[12px] text-muted-foreground">
                                        Lo rechazado no entra al inventario
                                    </span>
                                    {fieldError(index, 'rejected_quantity') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(
                                                index,
                                                'rejected_quantity',
                                            )}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Motivo del rechazo
                                    </Label>
                                    <Input
                                        value={line.rejection_reason}
                                        onChange={(e) =>
                                            updateLine(
                                                index,
                                                'rejection_reason',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={500}
                                        disabled={line.rejected_quantity === 0}
                                        className={`h-[42px] rounded-[10px] ${
                                            fieldError(
                                                index,
                                                'rejection_reason',
                                            )
                                                ? 'border-bad'
                                                : ''
                                        }`}
                                    />
                                    {fieldError(index, 'rejection_reason') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(
                                                index,
                                                'rejection_reason',
                                            )}
                                        </p>
                                    )}
                                </div>

                                {orderLines.length > 0 && (
                                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                                        <Label className="text-[13px] font-semibold">
                                            Línea de la orden
                                        </Label>
                                        <Select2
                                            options={orderLineOptions}
                                            value={
                                                orderLineOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        (line.sourceable_id ||
                                                            NO_ORDER_LINE),
                                                ) ?? null
                                            }
                                            onChange={(option) =>
                                                setLineOrderLine(
                                                    index,
                                                    !option ||
                                                        option.value ===
                                                            NO_ORDER_LINE
                                                        ? ''
                                                        : option.value,
                                                )
                                            }
                                            error={
                                                !!fieldError(
                                                    index,
                                                    'sourceable_id',
                                                )
                                            }
                                            size="md"
                                            placeholder="Sin línea de la orden"
                                        />
                                        <span className="text-[12px] text-muted-foreground">
                                            Atarla limita lo recibido a lo que
                                            queda pendiente, y trae el costo con
                                            el que se pidió
                                        </span>
                                        {fieldError(index, 'sourceable_id') && (
                                            <p className="text-sm text-bad">
                                                {fieldError(
                                                    index,
                                                    'sourceable_id',
                                                )}
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex flex-wrap justify-end gap-x-5 gap-y-1 border-t pt-3 text-[13px]">
                            <span className="text-muted-foreground">
                                Aceptado{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {acceptedQuantity(line)}
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

            <EntryLineTraceabilityDialog
                index={traceabilityOf}
                onClose={() => setTraceabilityOf(null)}
            />
        </div>
    );
}
