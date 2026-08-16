import { Plus, X } from 'lucide-react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { usePurchaseOrderFormContext } from '../contexts/PurchaseOrderFormContext';
import { lineAmounts } from '../hooks/usePurchaseOrderForm';
import { formatAmount } from '../types/PurchaseOrder';

/**
 * 2.2 Líneas de la orden. Cada fila fija artículo, unidad, cantidad y costo; el
 * importe que se muestra es el mismo que recalcula el backend al guardar.
 */
export function PurchaseOrderLinesSection() {
    const {
        data,
        errors,
        options,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
    } = usePurchaseOrderFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const unitsOf = (itemId: string) =>
        options.items.find((item) => item.id === itemId)?.units ?? [];

    const itemOptions: OptionType[] = options.items.map((item) => ({
        value: item.id,
        label: `${item.code} · ${item.name}`,
    }));

    const handleItemChange = (index: number, itemId: string) => {
        const item = options.items.find((option) => option.id === itemId);
        const baseUnit =
            item?.units.find((unit) => unit.is_base === 'yes') ??
            item?.units[0];

        setLineItem(
            index,
            itemId,
            baseUnit?.measurement_unit_id ?? '',
            Number(item?.standard_cost ?? 0),
        );
    };

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            {data.lines.map((line, index) => {
                const amounts = lineAmounts(line);
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
                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[2.2fr_1.2fr_1fr_1.2fr_auto]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Artículo *
                                </Label>
                                <Select2
                                    options={itemOptions}
                                    value={
                                        itemOptions.find(
                                            (option) =>
                                                option.value === line.item_id,
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        handleItemChange(
                                            index,
                                            option?.value ?? '',
                                        )
                                    }
                                    error={!!fieldError(index, 'item_id')}
                                    size="md"
                                    placeholder="Selecciona un artículo"
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

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1.3fr]">
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
                                    Impuesto %
                                </Label>
                                <NumberInput
                                    value={line.tax_percent}
                                    onValueChange={(value) =>
                                        updateLine(index, 'tax_percent', value)
                                    }
                                    min={0}
                                    max={100}
                                    decimals={4}
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Retención %
                                </Label>
                                <NumberInput
                                    value={line.withholding_percent}
                                    onValueChange={(value) =>
                                        updateLine(
                                            index,
                                            'withholding_percent',
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
                                    Entrega esperada
                                </Label>
                                <Input
                                    type="date"
                                    value={line.expected_date}
                                    onChange={(e) =>
                                        updateLine(
                                            index,
                                            'expected_date',
                                            e.target.value,
                                        )
                                    }
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>
                        </div>

                        <div className="flex flex-wrap justify-end gap-x-5 gap-y-1 border-t pt-3 text-[13px]">
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
