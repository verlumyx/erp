import { ChevronDown, ClipboardCopy, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { cn } from '@/lib/utils';
import { taxOptionLabel } from '@/types/tax';
import { useEntryFormContext } from '../contexts/EntryFormContext';
import { lineAmounts } from '../hooks/useEntryForm';
import { formatAmount } from '../types/Entry';

/** Valor del select cuando la línea no lleva impuesto: '' no lo distingue. */
const NO_TAX = 'none';

/** Valor del select cuando la línea no sale de ninguna línea de orden. */
const NO_ORDER_LINE = 'none';

/** Valor del select cuando el kardex debe usar la ubicación por defecto. */
const DEFAULT_LOCATION = 'default';

/**
 * Convierte lo que el usuario escribe en el campo de series —separadas por
 * coma, punto y coma o salto de línea— en la lista que viaja al backend.
 */
function parseSerials(value: string): string[] {
    return value
        .split(/[\n,;]/)
        .map((serial) => serial.trim())
        .filter((serial) => serial !== '');
}

/**
 * 3.2 Líneas de la entrada. Cada fila fija artículo, unidad, lo que llegó y a
 * qué costo; lo que la inspección rechaza se descuenta de lo que entra al
 * inventario. El costo con el que la mercancía se registra en el kardex no se
 * captura: lo calcula el backend repartiendo el flete y los gastos.
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
        setLineTax,
        setLineOrderLine,
        orderLines,
        pendingOf,
        copyOrderLines,
        locations,
        options,
    } = useEntryFormContext();

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

    const pendingOrderLines = orderLines.filter(
        (line) => pendingOf(line.id) > 0,
    );

    const [openCharges, setOpenCharges] = useState<Record<string, boolean>>({});

    const toggleCharges = (lineId: string) =>
        setOpenCharges((current) => ({
            ...current,
            [lineId]: !current[lineId],
        }));

    /**
     * Lo que el usuario lleva escrito en el campo de series, tal cual. La línea
     * guarda la lista ya separada, y esa lista no puede alimentar el campo: al
     * volver a unirla se comería el separador que se acaba de teclear.
     */
    const [serialDrafts, setSerialDrafts] = useState<Record<string, string>>(
        {},
    );

    const serialsOf = (lineId: string, serials: string[]): string =>
        serialDrafts[lineId] ?? serials.join(', ');

    const writeSerials = (index: number, lineId: string, value: string) => {
        setSerialDrafts((current) => ({ ...current, [lineId]: value }));
        updateLine(index, 'serial_numbers', parseSerials(value));
    };

    /** Cambiar de artículo suelta las series: eran de otra mercancía. */
    const changeItem = (
        index: number,
        lineId: string,
        option: Parameters<typeof setLineItem>[1],
    ) => {
        setSerialDrafts((current) => ({ ...current, [lineId]: '' }));
        setLineItem(index, option);
    };

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
                        La orden elegida tiene {pendingOrderLines.length} línea
                        {pendingOrderLines.length !== 1 ? 's' : ''} pendiente
                        {pendingOrderLines.length !== 1 ? 's' : ''} de llegar.
                        Tráelas y ajusta lo que realmente entró.
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        className="h-9 rounded-[10px] bg-card font-semibold"
                        onClick={copyOrderLines}
                    >
                        <ClipboardCopy />
                        Copiar las líneas de la orden
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
                    line.sourceable_id !== '' ||
                    line.rejected_quantity > 0;
                const pending = pendingOf(line.sourceable_id);

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
                                        } impuesto, descuento, rechazo y trazabilidad de la línea ${index + 1}`}
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
                                            params={{ is_purchasable: 'yes' }}
                                            value={catalog.optionOf(
                                                line.item_id,
                                            )}
                                            onChange={(option) =>
                                                changeItem(
                                                    index,
                                                    line.id,
                                                    option,
                                                )
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
                                <Label className="text-[13px] font-semibold">
                                    Costo unitario *
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
                                    Lote del proveedor
                                </Label>
                                <Input
                                    value={line.lot_number}
                                    onChange={(e) =>
                                        updateLine(
                                            index,
                                            'lot_number',
                                            e.target.value,
                                        )
                                    }
                                    maxLength={60}
                                    placeholder="Sin lote"
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'lot_number')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                <span className="text-[12px] text-muted-foreground">
                                    Se registra al confirmar la entrada
                                </span>
                                {fieldError(index, 'lot_number') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'lot_number')}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Vencimiento del lote
                                </Label>
                                <Input
                                    type="date"
                                    value={line.expires_at}
                                    onChange={(e) =>
                                        updateLine(
                                            index,
                                            'expires_at',
                                            e.target.value,
                                        )
                                    }
                                    disabled={line.lot_number === ''}
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'expires_at')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {fieldError(index, 'expires_at') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'expires_at')}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5 sm:col-span-3">
                                <Label className="text-[13px] font-semibold">
                                    Series recibidas
                                </Label>
                                <Input
                                    value={serialsOf(
                                        line.id,
                                        line.serial_numbers,
                                    )}
                                    onChange={(e) =>
                                        writeSerials(
                                            index,
                                            line.id,
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Solo en artículos serializados: una serie por unidad aceptada"
                                    className={`h-[42px] rounded-[10px] ${
                                        fieldError(index, 'serial_numbers')
                                            ? 'border-bad'
                                            : ''
                                    }`}
                                />
                                {line.serial_numbers.length > 0 && (
                                    <span className="text-[12px] text-muted-foreground">
                                        {line.serial_numbers.length} serie
                                        {line.serial_numbers.length !== 1
                                            ? 's'
                                            : ''}{' '}
                                        para {amounts.receivedQuantity} unidad
                                        {amounts.receivedQuantity !== 1
                                            ? 'es'
                                            : ''}{' '}
                                        aceptada
                                        {amounts.receivedQuantity !== 1
                                            ? 's'
                                            : ''}
                                    </span>
                                )}
                                {fieldError(index, 'serial_numbers') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'serial_numbers')}
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
                                    {line.withholding_percent > 0 && (
                                        <span className="text-[12px] text-muted-foreground">
                                            Retiene {line.withholding_percent}%
                                            del impuesto
                                        </span>
                                    )}
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
                                            queda pendiente
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
                                    {amounts.receivedQuantity}
                                </b>
                            </span>
                            <span className="text-muted-foreground">
                                Base{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(
                                        amounts.subtotal,
                                        data.currency,
                                    )}
                                </b>
                            </span>
                            <span className="text-muted-foreground">
                                Impuesto{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(
                                        amounts.taxAmount,
                                        data.currency,
                                    )}
                                </b>
                            </span>
                            {amounts.withholdingAmount > 0 && (
                                <span className="text-muted-foreground">
                                    Retención{' '}
                                    <b className="font-bold text-foreground tabular-nums">
                                        {formatAmount(
                                            amounts.withholdingAmount,
                                            data.currency,
                                        )}
                                    </b>
                                </span>
                            )}
                            <span className="text-muted-foreground">
                                Total{' '}
                                <b className="font-bold text-foreground tabular-nums">
                                    {formatAmount(amounts.total, data.currency)}
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
