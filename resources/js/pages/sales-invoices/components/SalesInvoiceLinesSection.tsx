import { ChevronDown, Link2, Plus, X } from 'lucide-react';
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
import { useSalesInvoiceFormContext } from '../contexts/SalesInvoiceFormContext';
import { lineAmounts } from '../hooks/useSalesInvoiceForm';
import { formatAmount } from '../types/SalesInvoice';

/** Valor del select cuando la línea no lleva impuesto: '' no lo distingue. */
const NO_TAX = 'none';

/**
 * 3.2 Líneas de la factura. El precio se congela al guardar y el costo al
 * emitir: cambios posteriores en el catálogo no reescriben el documento.
 */
export function SalesInvoiceLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        selectLineItem,
        selectLineTax,
        options,
    } = useSalesInvoiceFormContext();

    /** El catálogo de impuestos es el mismo para todas las líneas. */
    const taxOptions: OptionType[] = [
        { value: NO_TAX, label: 'Sin impuesto' },
        ...options.taxes.map((tax) => ({
            value: tax.id,
            label: taxOptionLabel(tax),
        })),
    ];

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
                const chargesOpen = openCharges[line.id] === true;
                const hasCharges =
                    line.discount_percent > 0 || line.tax_id !== '';
                const fromOrder = line.sourceable_id !== '';
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
                                <Label className="flex items-center gap-1.5 text-[13px] font-semibold">
                                    Artículo *
                                    {fromOrder && (
                                        <span
                                            className="inline-flex items-center gap-1 text-[12px] font-medium text-muted-foreground"
                                            title="Esta línea viene del pedido de origen"
                                        >
                                            <Link2 className="size-3.5" />
                                            del pedido
                                        </span>
                                    )}
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
                                        } descuento, impuesto y retención de la línea ${index + 1}`}
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
                                                selectLineItem(index, option)
                                            }
                                            formatLabel={catalog.labelOf}
                                            error={!!itemError}
                                            size="md"
                                            placeholder="Busca por sku, código o nombre"
                                        />
                                    </div>
                                </div>
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
                                    /** La unidad de una línea del pedido no se cambia. */
                                    isDisabled={
                                        unitOptions.length === 0 || fromOrder
                                    }
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
                                            Sugerido:{' '}
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
                                    placeholder="Lote, referencia de entrega…"
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
                                            selectLineTax(
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
                                            El cliente retiene{' '}
                                            {line.withholding_percent}% del
                                            impuesto (
                                            {formatAmount(
                                                amounts.withholdingAmount,
                                                data.currency,
                                            )}
                                            )
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
                                    {fieldError(index, 'discount_percent') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(
                                                index,
                                                'discount_percent',
                                            )}
                                        </p>
                                    )}
                                </div>
                            </div>
                        )}

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
