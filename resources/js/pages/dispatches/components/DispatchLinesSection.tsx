import { ChevronDown, ClipboardCopy, Layers, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { cn } from '@/lib/utils';
import { useDispatchFormContext } from '../contexts/DispatchFormContext';
import { DispatchLineTraceabilityDialog } from './DispatchLineTraceabilityDialog';

/** Valor del select cuando la línea no sale de ninguna línea del pedido. */
const NO_ORDER_LINE = 'none';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * 1.2 Líneas del despacho. Cada fila dice qué artículo sale, en qué unidad,
 * cuánto se pidió y cuánto se despacha. Nada más: el precio y sus cargos se
 * decidieron en el pedido y los pone el backend —la guía no factura—, y la
 * ubicación y la trazabilidad viven en el detalle de la línea.
 */
export function DispatchLinesSection() {
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
        remainingOf,
        orderedQuantityOf,
        copyOrderLines,
        locations,
    } = useDispatchFormContext();

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

    /** Las líneas del pedido elegido, para atar cada línea a la suya. */
    const orderLineOptions: OptionType[] = [
        { value: NO_ORDER_LINE, label: 'Sin línea del pedido' },
        ...orderLines.map((line) => ({
            value: line.id,
            label: `${line.item_sku ?? ''} ${line.item_name ?? ''} · quedan ${remainingOf(
                line.id,
            )}`.trim(),
        })),
    ];

    const pendingOrderLines = orderLines.filter(
        (line) => remainingOf(line.id) > 0,
    );

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

            {pendingOrderLines.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-dashed p-4">
                    <span className="text-[13px] text-muted-foreground">
                        El pedido elegido tiene {pendingOrderLines.length} línea
                        {pendingOrderLines.length !== 1 ? 's' : ''} con saldo
                        por despachar. Tráelas y ajusta lo que sale.
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        className="h-9 rounded-[10px] bg-card font-semibold"
                        onClick={copyOrderLines}
                    >
                        <ClipboardCopy />
                        Copiar las líneas del pedido
                    </Button>
                </div>
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

                const detailOpen = openDetail[line.id] === true;
                const hasDetail =
                    line.location_id !== '' ||
                    line.sourceable_id !== '' ||
                    lots.length > 0 ||
                    serials.length > 0;

                const ordered = orderedQuantityOf(line);
                const remaining = remainingOf(line.sourceable_id);

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
                                            params={{ is_sellable: 'yes' }}
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
                             * Lo que pidió el cliente. No se edita aquí: se
                             * cambia en el pedido, no al despachar.
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
                                        ? 'Sin pedido'
                                        : 'Lo pedido'}
                                </span>
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Cantidad a despachar *
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
                                {remaining > 0 && (
                                    <span className="text-[12px] text-muted-foreground">
                                        Quedan {remaining} por despachar
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

                                {orderLines.length > 0 && (
                                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                                        <Label className="text-[13px] font-semibold">
                                            Línea del pedido
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
                                            placeholder="Sin línea del pedido"
                                        />
                                        <span className="text-[12px] text-muted-foreground">
                                            Atarla limita lo despachado al saldo
                                            del pedido, y trae el precio con el
                                            que se vendió
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

            <DispatchLineTraceabilityDialog
                index={traceabilityOf}
                onClose={() => setTraceabilityOf(null)}
            />
        </div>
    );
}
