import { Plus, X } from 'lucide-react';
import { CurrencySelect } from '@/components/currency-select';
import { LineNotePopover } from '@/components/line-note-popover';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import purchaseInvoices from '@/routes/purchase-invoices';
import suppliers from '@/routes/suppliers';
import { useImportFormContext } from '../contexts/ImportFormContext';
import {
    CONCEPT_LABELS,
    FREE_CONCEPT,
    formatAmount,
    type ImportCostConcept,
} from '../types/Import';

const CONCEPT_OPTIONS: OptionType[] = Object.entries(CONCEPT_LABELS).map(
    ([value, label]) => ({ value, label }),
);

/**
 * 6.2 Los cobros que se reparten. Una fila por cada uno, con el documento que
 * lo respalda y quién lo cobró —que no tiene por qué ser el proveedor de la
 * mercancía: el transportista factura el flete y el agente la aduana—.
 *
 * El importe queda editable en los dos casos. Una misma factura puede
 * repartirse entre dos expedientes cuando el embarque llegó en dos viajes, y
 * entonces ninguno de los dos toma el documento completo.
 */
export function ImportCostsSection() {
    const {
        data,
        errors,
        companyId,
        addCost,
        updateCost,
        removeCost,
        setCostSupplier,
        setCostInvoice,
        supplierOptions,
        invoiceOptions,
        totals,
    } = useImportFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `costs.${index}.${field}`
        ];

    const rows = data.costs
        .map((cost, index) => ({ cost, index }))
        .filter(({ cost }) => cost.status === 'active');

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.costs && <p className="text-sm text-bad">{errors.costs}</p>}

            {rows.map(({ cost, index }) => (
                <div
                    key={cost.id}
                    className="flex flex-col gap-3 rounded-[12px] border p-4"
                >
                    <div className="grid grid-cols-1 items-start gap-3 sm:grid-cols-2 lg:grid-cols-[1.3fr_2fr_1.6fr_1fr_1.2fr_auto]">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Concepto *
                            </Label>
                            <Select2
                                options={CONCEPT_OPTIONS}
                                value={
                                    CONCEPT_OPTIONS.find(
                                        (option) =>
                                            option.value === cost.concept,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    updateCost(
                                        index,
                                        'concept',
                                        (option?.value ??
                                            'freight') as ImportCostConcept,
                                    )
                                }
                                error={!!fieldError(index, 'concept')}
                                size="md"
                                placeholder="Por qué se cobró"
                            />
                            {fieldError(index, 'concept') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'concept')}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Documento
                            </Label>
                            <Select2Ajax
                                url={purchaseInvoices.lookup(companyId).url}
                                value={invoiceOptions.optionOf(
                                    cost.sourceable_id,
                                )}
                                onChange={(option) =>
                                    setCostInvoice(index, option)
                                }
                                error={!!fieldError(index, 'sourceable_id')}
                                size="md"
                                placeholder="Factura que lo respalda"
                                isClearable
                            />
                            <span className="text-[12px] text-muted-foreground">
                                La del transportista va entera; la del proveedor
                                de la mercancía, solo sus líneas de servicio
                            </span>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Quién cobra
                            </Label>
                            <Select2Ajax
                                url={suppliers.lookup(companyId).url}
                                value={supplierOptions.optionOf(
                                    cost.supplier_id,
                                )}
                                onChange={(option) =>
                                    setCostSupplier(index, option)
                                }
                                error={!!fieldError(index, 'supplier_id')}
                                size="md"
                                placeholder="Proveedor del cargo"
                                isClearable
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Moneda *
                            </Label>
                            <CurrencySelect
                                value={cost.currency}
                                onValueChange={(value) =>
                                    updateCost(index, 'currency', value)
                                }
                                error={fieldError(index, 'currency')}
                            />
                            {fieldError(index, 'currency') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'currency')}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Importe *
                            </Label>
                            <CurrencyInput
                                value={cost.amount}
                                onValueChange={(value) =>
                                    updateCost(index, 'amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${
                                    fieldError(index, 'amount')
                                        ? 'border-bad'
                                        : ''
                                }`}
                            />
                            {fieldError(index, 'amount') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'amount')}
                                </p>
                            )}
                        </div>

                        <div className="flex items-end gap-1.5 pb-0.5">
                            <LineNotePopover
                                value={cost.notes}
                                onValueChange={(value) =>
                                    updateCost(index, 'notes', value)
                                }
                                ariaLabel={`Nota del cobro ${index + 1}`}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-[42px] shrink-0 rounded-[10px] text-muted-foreground hover:text-bad"
                                onClick={() => removeCost(index)}
                                aria-label={`Quitar el cobro ${index + 1}`}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    </div>

                    {cost.concept === FREE_CONCEPT && (
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Descripción *
                            </Label>
                            <Input
                                value={cost.description}
                                onChange={(e) =>
                                    updateCost(
                                        index,
                                        'description',
                                        e.target.value,
                                    )
                                }
                                maxLength={500}
                                placeholder="Qué se cobró exactamente"
                                className={`h-[42px] rounded-[10px] ${
                                    fieldError(index, 'description')
                                        ? 'border-bad'
                                        : ''
                                }`}
                            />
                            {fieldError(index, 'description') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'description')}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            ))}

            <div className="flex items-center justify-between">
                <span className="text-[13px] text-muted-foreground">
                    Total a repartir:{' '}
                    <b className="tabular-nums">
                        {formatAmount(totals.charges, data.currency)}
                    </b>
                </span>
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 rounded-[11px] bg-card px-4 font-semibold"
                    onClick={addCost}
                >
                    <Plus className="size-4" />
                    Agregar cobro
                </Button>
            </div>
        </div>
    );
}
