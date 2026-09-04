import { ChevronDown, ClipboardCopy, Plus, X } from 'lucide-react';
import { useState } from 'react';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { cn } from '@/lib/utils';
import { usePurchaseReturnFormContext } from '../contexts/PurchaseReturnFormContext';
import { REASON_LABELS, type PurchaseReturnReason } from '../types/PurchaseReturn';

/** Valor del select cuando la línea no sale de ninguna línea de factura. */
const NO_INVOICE_LINE = 'none';

/** Valor del select cuando la línea hereda el motivo de la cabecera. */
const HEADER_REASON = 'header';

/**
 * 7.2 Líneas de la devolución. Cada fila dice qué vuelve, cuánto y de qué
 * bodega sale; el precio con el que se acredita lo pone el backend copiándolo
 * de la línea facturada, y el lote y la serie los pide el despacho que la
 * devolución genera al confirmarse.
 */
export function PurchaseReturnLinesSection() {
    const {
        data,
        errors,
        catalog,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineInvoiceLine,
        invoiceLines,
        remainingOf,
        copyInvoiceLines,
        warehouses,
    } = usePurchaseReturnFormContext();

    const warehouseOptions: OptionType[] = warehouses.map((warehouse) => ({
        value: warehouse.id,
        label: warehouse.name,
    }));

    const reasonOptions: OptionType[] = [
        { value: HEADER_REASON, label: 'El de la devolución' },
        ...Object.entries(REASON_LABELS).map(([value, label]) => ({
            value,
            label,
        })),
    ];

    /** Las líneas de la factura elegida, para atar cada línea a la suya. */
    const invoiceLineOptions: OptionType[] = [
        { value: NO_INVOICE_LINE, label: 'Sin línea de la factura' },
        ...invoiceLines.map((line) => ({
            value: line.id,
            label: `#${line.line_number} · ${line.item_code ?? ''} ${
                line.item_name ?? ''
            } · quedan ${remainingOf(line.id)}`.trim(),
        })),
    ];

    const pendingInvoiceLines = invoiceLines.filter(
        (line) => remainingOf(line.id) > 0,
    );

    const [openDetail, setOpenDetail] = useState<Record<string, boolean>>({});

    const toggleDetail = (lineId: string) =>
        setOpenDetail((current) => ({
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

            {pendingInvoiceLines.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-dashed p-4">
                    <span className="text-[13px] text-muted-foreground">
                        La factura elegida tiene {pendingInvoiceLines.length}{' '}
                        línea
                        {pendingInvoiceLines.length !== 1 ? 's' : ''} con saldo
                        por devolver. Tráelas y ajusta lo que vuelve.
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
                const units = unitsOf(line.item_id);
                const unitOptions: OptionType[] = units.map((unit) => ({
                    value: unit.measurement_unit_id,
                    label: unit.name,
                }));
                const detailOpen = openDetail[line.id] === true;
                const hasDetail =
                    line.purchase_invoice_line_id !== '' || line.reason !== '';
                const remaining = remainingOf(line.purchase_invoice_line_id);

                return (
                    <div
                        key={line.id}
                        className="flex flex-col gap-3 rounded-[12px] border p-4"
                    >
                        <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-[2.2fr_1fr_1.2fr_1.4fr_auto]">
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
                                        } motivo y línea de la factura de la línea ${index + 1}`}
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
                                        Quedan {remaining} por devolver
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
                                    Bodega *
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

                        {detailOpen && (
                            <div className="grid grid-cols-1 items-end gap-3 sm:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-[13px] font-semibold">
                                        Motivo de la línea
                                    </Label>
                                    <Select2
                                        options={reasonOptions}
                                        value={
                                            reasonOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    (line.reason ||
                                                        HEADER_REASON),
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            updateLine(
                                                index,
                                                'reason',
                                                !option ||
                                                    option.value ===
                                                        HEADER_REASON
                                                    ? ''
                                                    : (option.value as PurchaseReturnReason),
                                            )
                                        }
                                        error={!!fieldError(index, 'reason')}
                                        size="md"
                                        placeholder="El de la devolución"
                                    />
                                    {fieldError(index, 'reason') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'reason')}
                                        </p>
                                    )}
                                </div>

                                {invoiceLines.length > 0 && (
                                    <div className="flex flex-col gap-1.5">
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
                                            Atarla limita lo devuelto a lo que
                                            se compró y trae su precio
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
