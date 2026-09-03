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
import { usePurchaseCreditNoteFormContext } from '../contexts/PurchaseCreditNoteFormContext';
import { lineAmounts } from '../hooks/usePurchaseCreditNoteForm';
import { formatAmount } from '../types/PurchaseCreditNote';

/** Valor del select cuando la línea no lleva impuesto: '' no lo distingue. */
const NO_TAX = 'none';

/** Valor del select cuando la línea no acredita ninguna línea de factura. */
const NO_INVOICE_LINE = 'none';

/**
 * 4.2 Líneas de la nota. Cada fila fija artículo, unidad, cantidad y precio; el
 * importe que se muestra es el mismo que recalcula el backend al guardar.
 */
export function PurchaseCreditNoteLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineTax,
        setLineInvoiceLine,
        invoiceLines,
        copyInvoiceLines,
        options,
    } = usePurchaseCreditNoteFormContext();

    /** El catálogo de impuestos es el mismo para todas las líneas. */
    const taxOptions: OptionType[] = [
        { value: NO_TAX, label: 'Sin impuesto' },
        ...options.taxes.map((tax) => ({
            value: tax.id,
            label: taxOptionLabel(tax),
        })),
    ];

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    /** Las líneas de la factura elegida, para atar cada línea a la suya. */
    const invoiceLineOptions: OptionType[] = [
        { value: NO_INVOICE_LINE, label: 'Sin línea de la factura' },
        ...invoiceLines.map((line) => ({
            value: line.id,
            label: `#${line.line_number} · ${line.item_code ?? ''} ${
                line.item_name ?? ''
            } · ${line.quantity}`.trim(),
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

    const unitsOf = (itemId: string) => catalog.itemOf(itemId)?.units ?? [];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.lines && <p className="text-sm text-bad">{errors.lines}</p>}

            {invoiceLines.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-dashed p-4">
                    <span className="text-[13px] text-muted-foreground">
                        La factura elegida tiene {invoiceLines.length} línea
                        {invoiceLines.length !== 1 ? 's' : ''}. Tráelas y ajusta
                        lo que se acredita.
                    </span>
                    <Button
                        type="button"
                        variant="outline"
                        className="h-9 rounded-[10px] bg-card font-semibold"
                        onClick={copyInvoiceLines}
                    >
                        <ClipboardCopy />
                        Copiar las líneas de la factura
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
                    line.purchase_invoice_line_id !== '';

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
                                    Precio unitario *
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

                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label className="text-[13px] font-semibold">
                                    Bodega
                                </Label>
                                <Select2
                                    options={warehouseOptions}
                                    value={
                                        warehouseOptions.find(
                                            (option) =>
                                                option.value ===
                                                line.warehouse_id,
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        updateLine(
                                            index,
                                            'warehouse_id',
                                            option?.value ?? '',
                                        )
                                    }
                                    error={!!fieldError(index, 'warehouse_id')}
                                    size="md"
                                    placeholder="De dónde sale la mercancía"
                                />
                                {fieldError(index, 'warehouse_id') && (
                                    <p className="text-sm text-bad">
                                        {fieldError(index, 'warehouse_id')}
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

                                {invoiceLines.length > 0 && (
                                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                                        <Label className="text-[13px] font-semibold">
                                            Línea de la factura
                                        </Label>
                                        <Select2
                                            options={invoiceLineOptions}
                                            value={
                                                invoiceLineOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        (line.purchase_invoice_line_id ||
                                                            NO_INVOICE_LINE),
                                                ) ?? null
                                            }
                                            onChange={(option) =>
                                                setLineInvoiceLine(
                                                    index,
                                                    !option ||
                                                        option.value ===
                                                            NO_INVOICE_LINE
                                                        ? ''
                                                        : option.value,
                                                )
                                            }
                                            error={
                                                !!fieldError(
                                                    index,
                                                    'purchase_invoice_line_id',
                                                )
                                            }
                                            size="md"
                                            placeholder="Sin línea de la factura"
                                        />
                                        <span className="text-[12px] text-muted-foreground">
                                            Atarla limita lo acreditado a lo que
                                            se facturó
                                        </span>
                                        {fieldError(
                                            index,
                                            'purchase_invoice_line_id',
                                        ) && (
                                            <p className="text-sm text-bad">
                                                {fieldError(
                                                    index,
                                                    'purchase_invoice_line_id',
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
