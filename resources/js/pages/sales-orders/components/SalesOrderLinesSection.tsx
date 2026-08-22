import { Plus, X } from 'lucide-react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useSalesOrderFormContext } from '../contexts/SalesOrderFormContext';
import { lineAmounts } from '../hooks/useSalesOrderForm';
import { formatAmount } from '../types/SalesOrder';

/**
 * 2.2 Líneas del pedido. El precio arranca en el de la lista aplicada y se
 * congela al guardar: cambios posteriores en la lista no afectan al pedido.
 */
export function SalesOrderLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        selectLineItem,
    } = useSalesOrderFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            {data.lines.map((line, index) => {
                const item = catalog.itemOf(line.item_id);
                const amounts = lineAmounts(line);
                const itemError = fieldError(index, 'item_id');
                const unitError = fieldError(index, 'measurement_unit_id');
                const quantityError = fieldError(index, 'quantity');
                const priceError = fieldError(index, 'unit_price');
                const unitOptions: OptionType[] = (item?.units ?? []).map(
                    (unit) => ({
                        value: unit.measurement_unit_id,
                        label: `${unit.name ?? 'Unidad'}${
                            unit.is_base === 'yes' ? ' (base)' : ''
                        }`,
                    }),
                );
                const belowMinPrice =
                    item !== undefined &&
                    Number(item.min_price) > 0 &&
                    line.unit_price < Number(item.min_price);

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
                                <Select2Ajax
                                    url={catalog.url}
                                    params={{ is_sellable: 'yes' }}
                                    value={catalog.optionOf(line.item_id)}
                                    onChange={(option) =>
                                        selectLineItem(index, option)
                                    }
                                    formatLabel={catalog.labelOf}
                                    error={!!itemError}
                                    size="md"
                                    placeholder="Busca por sku, código o nombre"
                                />
                                {itemError && (
                                    <p className="text-sm text-bad">
                                        {itemError}
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
                                    isDisabled={unitOptions.length === 0}
                                    error={!!unitError}
                                    size="md"
                                    placeholder="Unidad"
                                />
                                {unitError && (
                                    <p className="text-sm text-bad">
                                        {unitError}
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
                                    className={`h-[42px] rounded-[10px] ${quantityError ? 'border-bad' : ''}`}
                                />
                                {quantityError && (
                                    <p className="text-sm text-bad">
                                        {quantityError}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Precio *
                                </Label>
                                <CurrencyInput
                                    value={line.unit_price}
                                    onValueChange={(value) =>
                                        updateLine(index, 'unit_price', value)
                                    }
                                    min={0}
                                    decimals={6}
                                    className={`h-[42px] rounded-[10px] ${priceError ? 'border-bad' : ''}`}
                                />
                                {line.list_price > 0 &&
                                    line.unit_price !== line.list_price && (
                                        <span className="text-[12px] text-muted-foreground">
                                            Lista:{' '}
                                            {formatAmount(
                                                line.list_price,
                                                data.currency,
                                            )}
                                        </span>
                                    )}
                                {belowMinPrice && (
                                    <span className="text-[12px] font-semibold text-warn">
                                        Por debajo del precio mínimo (
                                        {formatAmount(
                                            item.min_price,
                                            data.currency,
                                        )}
                                        )
                                    </span>
                                )}
                                {priceError && (
                                    <p className="text-sm text-bad">
                                        {priceError}
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
                                    placeholder="Instrucción de empaque, referencia…"
                                />

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-[42px] rounded-[10px] bg-card"
                                    onClick={() => removeLine(index)}
                                    aria-label={`Quitar línea ${index + 1}`}
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
                                {fieldError(index, 'discount_percent') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'discount_percent')}
                                    </p>
                                )}
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
                className="h-[42px] w-max rounded-[10px] bg-card font-semibold"
                onClick={addLine}
            >
                <Plus className="size-4" />
                Agregar línea
            </Button>
        </div>
    );
}
