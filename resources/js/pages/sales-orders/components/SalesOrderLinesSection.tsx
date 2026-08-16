import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
        options,
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
                const item = options.items.find(
                    (candidate) => candidate.id === line.item_id,
                );
                const amounts = lineAmounts(line);
                const itemError = fieldError(index, 'item_id');
                const unitError = fieldError(index, 'measurement_unit_id');
                const quantityError = fieldError(index, 'quantity');
                const priceError = fieldError(index, 'unit_price');
                const belowMinPrice =
                    item !== undefined &&
                    Number(item.min_price) > 0 &&
                    line.unit_price < Number(item.min_price);

                return (
                    <div
                        key={line.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-[13px] font-bold text-muted-foreground">
                                Línea {index + 1}
                            </span>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="rounded-[10px] bg-card"
                                onClick={() => removeLine(index)}
                                aria-label={`Quitar línea ${index + 1}`}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[2.4fr_1.2fr_1fr_1.2fr_1fr]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Artículo *
                                </Label>
                                <Select
                                    value={line.item_id}
                                    onValueChange={(value) =>
                                        selectLineItem(index, value)
                                    }
                                >
                                    <SelectTrigger
                                        className={`h-[42px] w-full rounded-[10px] ${itemError ? 'border-bad' : ''}`}
                                    >
                                        <SelectValue placeholder="Selecciona un artículo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.items.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={option.id}
                                            >
                                                {option.sku} — {option.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
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
                                <Select
                                    value={line.measurement_unit_id}
                                    onValueChange={(value) =>
                                        updateLine(
                                            index,
                                            'measurement_unit_id',
                                            value,
                                        )
                                    }
                                    disabled={item === undefined}
                                >
                                    <SelectTrigger
                                        className={`h-[42px] w-full rounded-[10px] ${unitError ? 'border-bad' : ''}`}
                                    >
                                        <SelectValue placeholder="Unidad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(item?.units ?? []).map((unit) => (
                                            <SelectItem
                                                key={unit.measurement_unit_id}
                                                value={unit.measurement_unit_id}
                                            >
                                                {unit.name ?? 'Unidad'}
                                                {unit.is_base === 'yes'
                                                    ? ' (base)'
                                                    : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
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
                                            {formatAmount(line.list_price)}
                                        </span>
                                    )}
                                {belowMinPrice && (
                                    <span className="text-[12px] font-semibold text-warn">
                                        Por debajo del precio mínimo (
                                        {formatAmount(item.min_price)})
                                    </span>
                                )}
                                {priceError && (
                                    <p className="text-sm text-bad">
                                        {priceError}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Descuento (%)
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
                        </div>

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_2.4fr_1.4fr]">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Impuesto (%)
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
                                    Nota de la línea
                                </Label>
                                <Input
                                    type="text"
                                    value={line.notes}
                                    onChange={(e) =>
                                        updateLine(
                                            index,
                                            'notes',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Instrucción de empaque, referencia…"
                                    maxLength={500}
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>

                            <div className="flex flex-col gap-1 rounded-[10px] bg-muted px-3.5 py-2.5">
                                <div className="flex items-center justify-between text-[12.5px]">
                                    <span className="font-medium text-muted-foreground">
                                        Subtotal
                                    </span>
                                    <b className="font-bold tabular-nums">
                                        {formatAmount(amounts.subtotal)}
                                    </b>
                                </div>
                                <div className="flex items-center justify-between text-[12.5px]">
                                    <span className="font-medium text-muted-foreground">
                                        Impuesto
                                    </span>
                                    <b className="font-bold tabular-nums">
                                        {formatAmount(amounts.taxAmount)}
                                    </b>
                                </div>
                                <div className="flex items-center justify-between text-[13.5px]">
                                    <span className="font-semibold">Total</span>
                                    <b className="font-extrabold tabular-nums">
                                        {formatAmount(amounts.total)}
                                    </b>
                                </div>
                            </div>
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
