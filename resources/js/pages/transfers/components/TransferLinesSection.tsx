import { Plus, X } from 'lucide-react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useTransferFormContext } from '../contexts/TransferFormContext';
import type { WarehouseLocationOption } from '../types/Transfer';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * Líneas del traslado. Cada fila fija artículo, unidad, cantidad y de qué sitio
 * de cada bodega se mueve la mercancía.
 *
 * No hay precio, ni descuento, ni impuesto: entre bodegas propias no hay venta
 * ni compra. El costo con el que la mercancía viaja tampoco se captura, lo pone
 * el kardex al sacarla del origen, y con ese mismo entra en el destino.
 */
export function TransferLinesSection() {
    const {
        data,
        errors,
        catalog,
        lots,
        serials,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineLot,
        setLineSerial,
        originLocations,
        destinationLocations,
    } = useTransferFormContext();

    const locationOptions = (
        locations: WarehouseLocationOption[],
    ): OptionType[] => [
        { value: DEFAULT_LOCATION, label: 'Ubicación por defecto' },
        ...locations.map((location) => ({
            value: location.id,
            label:
                location.is_default === 'yes'
                    ? `${location.name} (por defecto)`
                    : location.name,
        })),
    ];

    const originOptions = locationOptions(originLocations);
    const destinationOptions = locationOptions(destinationLocations);

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const unitsOf = (itemId: string) => catalog.itemOf(itemId)?.units ?? [];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            {data.lines.map((line, index) => {
                const units = unitsOf(line.item_id);
                const unitOptions: OptionType[] = units.map((unit) => ({
                    value: unit.measurement_unit_id,
                    label: unit.name,
                }));

                return (
                    <div
                        key={line.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[2.6fr_1.2fr_1fr_auto]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Artículo *
                                </Label>
                                <Select2Ajax
                                    url={catalog.url}
                                    value={catalog.optionOf(line.item_id)}
                                    onChange={(option) =>
                                        setLineItem(index, option)
                                    }
                                    formatLabel={catalog.labelOf}
                                    error={!!fieldError(index, 'item_id')}
                                    size="md"
                                    placeholder="Busca por código o nombre"
                                />
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

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Cantidad *
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
                                {fieldError(index, 'quantity') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'quantity')}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-end gap-2">
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

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Ubicación de origen
                                </Label>
                                <Select2
                                    options={originOptions}
                                    value={
                                        originOptions.find(
                                            (option) =>
                                                option.value ===
                                                (line.origin_location_id ||
                                                    DEFAULT_LOCATION),
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        updateLine(
                                            index,
                                            'origin_location_id',
                                            !option ||
                                                option.value ===
                                                    DEFAULT_LOCATION
                                                ? ''
                                                : option.value,
                                        )
                                    }
                                    error={
                                        !!fieldError(
                                            index,
                                            'origin_location_id',
                                        )
                                    }
                                    size="md"
                                    placeholder="Ubicación por defecto"
                                />
                                {fieldError(index, 'origin_location_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(
                                            index,
                                            'origin_location_id',
                                        )}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Ubicación de destino
                                </Label>
                                <Select2
                                    options={destinationOptions}
                                    value={
                                        destinationOptions.find(
                                            (option) =>
                                                option.value ===
                                                (line.destination_location_id ||
                                                    DEFAULT_LOCATION),
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        updateLine(
                                            index,
                                            'destination_location_id',
                                            !option ||
                                                option.value ===
                                                    DEFAULT_LOCATION
                                                ? ''
                                                : option.value,
                                        )
                                    }
                                    error={
                                        !!fieldError(
                                            index,
                                            'destination_location_id',
                                        )
                                    }
                                    size="md"
                                    placeholder="Ubicación por defecto"
                                />
                                {fieldError(
                                    index,
                                    'destination_location_id',
                                ) && (
                                    <p className="text-sm text-bad">
                                        {fieldError(
                                            index,
                                            'destination_location_id',
                                        )}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Lote
                                </Label>
                                <Select2Ajax
                                    url={lots.url}
                                    params={{ item_id: line.item_id }}
                                    value={lots.optionOf(line.lot_id)}
                                    onChange={(option) =>
                                        setLineLot(index, option)
                                    }
                                    error={!!fieldError(index, 'lot_id')}
                                    isClearable
                                    isDisabled={line.item_id === ''}
                                    size="md"
                                    placeholder="Sin lote"
                                />
                                {fieldError(index, 'lot_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'lot_id')}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Serie
                                </Label>
                                <Select2Ajax
                                    url={serials.url}
                                    params={{ item_id: line.item_id }}
                                    value={serials.optionOf(line.serial_id)}
                                    onChange={(option) =>
                                        setLineSerial(index, option)
                                    }
                                    error={!!fieldError(index, 'serial_id')}
                                    isClearable
                                    isDisabled={line.item_id === ''}
                                    size="md"
                                    placeholder="Sin serie"
                                />
                                {fieldError(index, 'serial_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'serial_id')}
                                    </p>
                                )}
                            </div>
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
        </div>
    );
}
