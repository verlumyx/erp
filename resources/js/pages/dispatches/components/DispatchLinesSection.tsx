import { ChevronDown, ClipboardCopy, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { cn } from '@/lib/utils';
import { taxOptionLabel } from '@/types/tax';
import { useDispatchFormContext } from '../contexts/DispatchFormContext';
import { lineAmounts } from '../hooks/useDispatchForm';
import { formatAmount } from '../types/Dispatch';

/** Valor del select cuando la línea no lleva impuesto: '' no lo distingue. */
const NO_TAX = 'none';

/** Valor del select cuando la línea no sale de ninguna línea del pedido. */
const NO_ORDER_LINE = 'none';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * 1.2 Líneas del despacho. Cada fila fija artículo, unidad, cantidad y de dónde
 * sale la mercancía. El precio y sus cargos se copian del pedido y son
 * informativos: la guía enseña lo mismo que se vendió, pero no factura. El
 * costo con el que la mercancía sale tampoco se captura: lo pone el kardex.
 */
export function DispatchLinesSection() {
    const {
        data,
        errors,
        currency,
        catalog,
        lots,
        serials,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineLot,
        setLineSerial,
        setLineOrderLine,
        orderLines,
        remainingOf,
        copyOrderLines,
        locations,
        options,
    } = useDispatchFormContext();

    /** El catálogo de impuestos es el mismo para todas las líneas. */
    const taxOptions: OptionType[] = [
        { value: NO_TAX, label: 'Sin impuesto' },
        ...options.taxes.map((tax) => ({
            value: tax.id,
            label: taxOptionLabel(tax),
        })),
    ];

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

    const [openCharges, setOpenCharges] = useState<Record<string, boolean>>({});

    const toggleCharges = (lineId: string) =>
        setOpenCharges((current) => ({
            ...current,
            [lineId]: !current[lineId],
        }));

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
                const amounts = lineAmounts(line);
                const units = unitsOf(line.item_id);
                const unitOptions: OptionType[] = units.map((unit) => ({
                    value: unit.measurement_unit_id,
                    label: unit.name,
                }));
                const chargesOpen = openCharges[line.id] === true;
                const hasCharges =
                    line.discount_percent > 0 ||
                    line.tax_id !== '' ||
                    line.sourceable_id !== '';
                const remaining = remainingOf(line.sourceable_id);

                return (
                    <div
                        key={line.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[2.2fr_1.2fr_1fr_1.2fr_auto]">
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
                                            hasCharges && 'text-primary',
                                        )}
                                        onClick={() => toggleCharges(line.id)}
                                        aria-expanded={chargesOpen}
                                        aria-label={`${
                                            chargesOpen ? 'Ocultar' : 'Mostrar'
                                        } impuesto, descuento y trazabilidad de la línea ${index + 1}`}
                                    >
                                        <ChevronDown
                                            className={cn(
                                                'size-4 transition-transform',
                                                chargesOpen && 'rotate-180',
                                            )}
                                        />
                                        {hasCharges && !chargesOpen && (
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
                                <Label className="text-[13px] font-semibold">
                                    Precio de venta
                                </Label>
                                <CurrencyInput
                                    value={line.unit_price}
                                    onValueChange={(value) =>
                                        updateLine(index, 'unit_price', value)
                                    }
                                    min={0}
                                    decimals={6}
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'unit_price')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {fieldError(index, 'unit_price') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'unit_price')}
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

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-3">
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
                                    error={!!fieldError(index, 'location_id')}
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

                        {chargesOpen && (
                            <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Impuesto
                                    </Label>
                                    <Select2
                                        options={taxOptions}
                                        value={
                                            taxOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    (line.tax_id || NO_TAX),
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            setLineTax(
                                                index,
                                                !option ||
                                                    option.value === NO_TAX
                                                    ? ''
                                                    : option.value,
                                            )
                                        }
                                        error={!!fieldError(index, 'tax_id')}
                                        size="md"
                                        placeholder="Sin impuesto"
                                    />
                                    {fieldError(index, 'tax_id') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'tax_id')}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Descuento %
                                    </Label>
                                    <NumberInput
                                        value={line.discount_percent}
                                        onValueChange={(value) =>
                                            updateLine(
                                                index,
                                                'discount_percent',
                                                value,
                                            )
                                        }
                                        min={0}
                                        max={100}
                                        decimals={4}
                                        className="h-[42px] rounded-[10px]"
                                    />
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
                                            Atarla limita lo despachado a lo que
                                            se pidió
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
                                Base{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(amounts.subtotal, currency)}
                                </b>
                            </span>
                            <span className="text-muted-foreground">
                                Impuesto{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(amounts.taxAmount, currency)}
                                </b>
                            </span>
                            <span className="text-muted-foreground">
                                Total{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(amounts.total, currency)}
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
        </div>
    );
}
