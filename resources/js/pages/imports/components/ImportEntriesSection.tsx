import { PackageCheck, X } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import imports from '@/routes/imports';
import { useImportFormContext } from '../contexts/ImportFormContext';

/**
 * 6.3 Qué llegó. Es lo que va a absorber el gasto.
 *
 * El ancla es la entrada y no la factura: la factura dice lo que el proveedor
 * cobró, la entrada dice lo que de verdad llegó y es la que escribió el kardex.
 * Solo se ofrecen las confirmadas de la bodega del expediente y sin otro
 * expediente vivo detrás.
 */
export function ImportEntriesSection() {
    const {
        data,
        errors,
        companyId,
        initialData,
        addEntry,
        removeEntry,
        entryOptions,
    } = useImportFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `entries.${index}.${field}`
        ];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.entries && (
                <p className="text-sm text-bad">{errors.entries}</p>
            )}

            <div className="flex flex-col gap-1.5">
                <Label className="text-[13px] font-semibold">
                    Agregar recepción
                </Label>
                <Select2Ajax
                    url={imports.entries(companyId).url}
                    params={{
                        warehouse_id: data.warehouse_id,
                        import_id: initialData?.id ?? '',
                    }}
                    value={null}
                    onChange={addEntry}
                    isDisabled={data.warehouse_id === ''}
                    size="md"
                    placeholder={
                        data.warehouse_id === ''
                            ? 'Elige primero la bodega'
                            : 'Busca la entrada por su código'
                    }
                />
                <span className="text-[12px] text-muted-foreground">
                    Solo entradas confirmadas de esa bodega: un borrador todavía
                    no valoró nada, y no hay costo que corregir
                </span>
            </div>

            {data.entries.length === 0 ? (
                <div className="flex flex-col items-center gap-2 rounded-[12px] border border-dashed p-8 text-center">
                    <PackageCheck className="size-6 text-muted-foreground" />
                    <p className="text-[13.5px] text-muted-foreground">
                        Sin recepciones no hay nada que costear: el gasto se
                        queda fuera del inventario.
                    </p>
                </div>
            ) : (
                <div className="flex flex-col gap-2">
                    {data.entries.map((entry, index) => {
                        const option = entryOptions.optionOf(entry.entry_id);
                        const meta = (option?.meta ?? {}) as Record<
                            string,
                            unknown
                        >;

                        return (
                            <div
                                key={entry.id}
                                className="flex items-center gap-3 rounded-[12px] border p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-[14px] font-bold">
                                        {option?.label ?? entry.entry_id}
                                    </div>
                                    <div className="text-[12.5px] text-muted-foreground">
                                        {[
                                            meta.supplier_name,
                                            meta.entry_date,
                                            meta.warehouse_name,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ') ||
                                            'Recepción del embarque'}
                                    </div>
                                    {fieldError(index, 'entry_id') && (
                                        <p className="text-sm text-bad">
                                            {fieldError(index, 'entry_id')}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-[38px] shrink-0 rounded-[10px] text-muted-foreground hover:text-bad"
                                    onClick={() => removeEntry(index)}
                                    aria-label={`Quitar la recepción ${index + 1}`}
                                >
                                    <X className="size-4" />
                                </Button>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
