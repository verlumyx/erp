import { Plus, Star, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useItemFormContext } from '../contexts/ItemFormContext';

/**
 * 1.1 Unidades del artículo. Una fila se marca como base (factor fijo en 1) y
 * el resto define cuántas unidades base contiene.
 */
export function ItemUnitsSection() {
    const {
        data,
        errors,
        options,
        addUnit,
        removeUnit,
        updateUnit,
        setBaseUnit,
    } = useItemFormContext();

    const unitLabel = (id: string) => {
        const unit = options.measurementUnits.find((u) => u.id === id);
        return unit ? `${unit.name} (${unit.abbreviation})` : 'unidad base';
    };

    const baseUnitId =
        data.units.find((unit) => unit.is_base === 'yes')
            ?.measurement_unit_id ?? '';

    const measurementUnitOptions: OptionType[] = options.measurementUnits.map(
        (measurementUnit) => ({
            value: measurementUnit.id,
            label: `${measurementUnit.name} (${measurementUnit.abbreviation})`,
        }),
    );

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.units && <p className="text-sm text-bad">{errors.units}</p>}

            {data.units.map((unit, index) => {
                const isBase = unit.is_base === 'yes';
                const unitError = (
                    errors as Record<string, string | undefined>
                )[`units.${index}.measurement_unit_id`];

                return (
                    <div
                        key={index}
                        className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-[1.6fr_1fr_auto_auto]"
                    >
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Unidad de medida *
                            </Label>
                            <Select2
                                options={measurementUnitOptions}
                                value={
                                    measurementUnitOptions.find(
                                        (option) =>
                                            option.value ===
                                            unit.measurement_unit_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    updateUnit(
                                        index,
                                        'measurement_unit_id',
                                        option?.value ?? '',
                                    )
                                }
                                error={!!unitError}
                                size="md"
                                placeholder="Selecciona una unidad"
                            />
                            {unitError && (
                                <p className="text-sm text-bad">{unitError}</p>
                            )}
                            <span className="text-[12px] text-muted-foreground">
                                <br />
                            </span>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Factor de conversión
                            </Label>
                            <NumberInput
                                value={isBase ? 1 : unit.conversion_factor}
                                onValueChange={(value) =>
                                    updateUnit(
                                        index,
                                        'conversion_factor',
                                        value,
                                    )
                                }
                                min={0}
                                decimals={8}
                                disabled={isBase}
                                className="h-[42px] rounded-[10px]"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                {isBase
                                    ? 'La unidad base siempre vale 1'
                                    : `1 de esta unidad = X ${unitLabel(baseUnitId)}`}
                            </span>
                        </div>

                        <Button
                            type="button"
                            variant={isBase ? 'default' : 'outline'}
                            className="h-[42px] rounded-[10px] px-3 font-semibold"
                            onClick={() => setBaseUnit(index)}
                            title="Marcar como unidad base"
                        >
                            <Star
                                className={isBase ? 'fill-current' : undefined}
                            />
                            Base
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-[42px] rounded-[10px] bg-card"
                            onClick={() => removeUnit(index)}
                            disabled={data.units.length === 1}
                            aria-label="Quitar unidad"
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                );
            })}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addUnit}
            >
                <Plus />
                Agregar unidad
            </Button>
        </div>
    );
}
