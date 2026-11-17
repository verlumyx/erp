import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useVehicleFormContext } from '../contexts/VehicleFormContext';
import {
    VEHICLE_TYPE_LABELS,
    type VehicleType,
} from '../types/Vehicle';

const VEHICLE_TYPE_OPTIONS: OptionType[] = Object.entries(
    VEHICLE_TYPE_LABELS,
).map(([value, label]) => ({ value, label }));

export function VehicleForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useVehicleFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Identificación del vehículo"
                sub="Placa, modelo, marca y tipo de unidad de reparto"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="plate"
                            className="text-[13px] font-semibold"
                        >
                            Placa *
                        </Label>
                        <Input
                            id="plate"
                            value={data.plate}
                            onChange={(e) =>
                                setData('plate', e.target.value.toUpperCase())
                            }
                            maxLength={20}
                            className={`h-[42px] rounded-[10px] uppercase font-mono font-bold ${
                                errors.plate ? 'border-bad' : ''
                            }`}
                            placeholder="A12BC3D"
                        />
                        {errors.plate && (
                            <p className="text-sm text-bad">{errors.plate}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <Label
                            htmlFor="name"
                            className="text-[13px] font-semibold"
                        >
                            Nombre o Descripción corta *
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={150}
                            className={`h-[42px] rounded-[10px] ${
                                errors.name ? 'border-bad' : ''
                            }`}
                            placeholder="Ford Cargo 1721 Blanco / NPR Cava 01"
                        />
                        {errors.name && (
                            <p className="text-sm text-bad">{errors.name}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo de vehículo *
                        </Label>
                        <Select2
                            inputId="vehicle_type"
                            options={VEHICLE_TYPE_OPTIONS}
                            value={
                                VEHICLE_TYPE_OPTIONS.find(
                                    (opt) => opt.value === data.vehicle_type,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'vehicle_type',
                                    (option?.value ?? 'truck') as VehicleType,
                                )
                            }
                            error={!!errors.vehicle_type}
                            size="md"
                            placeholder="Selecciona el tipo"
                        />
                        {errors.vehicle_type && (
                            <p className="text-sm text-bad">
                                {errors.vehicle_type}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="brand"
                            className="text-[13px] font-semibold"
                        >
                            Marca
                        </Label>
                        <Input
                            id="brand"
                            value={data.brand}
                            onChange={(e) => setData('brand', e.target.value)}
                            maxLength={80}
                            className="h-[42px] rounded-[10px]"
                            placeholder="Chevrolet, Ford, Iveco..."
                        />
                        {errors.brand && (
                            <p className="text-sm text-bad">{errors.brand}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="model"
                            className="text-[13px] font-semibold"
                        >
                            Modelo
                        </Label>
                        <Input
                            id="model"
                            value={data.model}
                            onChange={(e) => setData('model', e.target.value)}
                            maxLength={80}
                            className="h-[42px] rounded-[10px]"
                            placeholder="NPR, Cargo, Super Duty..."
                        />
                        {errors.model && (
                            <p className="text-sm text-bad">{errors.model}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="year"
                            className="text-[13px] font-semibold"
                        >
                            Año
                        </Label>
                        <Input
                            id="year"
                            type="number"
                            value={data.year}
                            onChange={(e) => setData('year', e.target.value)}
                            min={1950}
                            max={2100}
                            className="h-[42px] rounded-[10px]"
                            placeholder="2018"
                        />
                        {errors.year && (
                            <p className="text-sm text-bad">{errors.year}</p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Capacidad física de carga"
                sub="Límites máximos de peso y volumen utilizados para contrastar en los planes de carga"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Capacidad en peso (Kg)
                        </Label>
                        <NumberInput
                            value={data.capacity_weight}
                            onValueChange={(value) =>
                                setData('capacity_weight', value)
                            }
                            min={0}
                            decimals={2}
                            className={`h-[42px] rounded-[10px] ${
                                errors.capacity_weight ? 'border-bad' : ''
                            }`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            En cero significa sin límite estricto de peso
                        </span>
                        {errors.capacity_weight && (
                            <p className="text-sm text-bad">
                                {errors.capacity_weight}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Capacidad en volumen (m³)
                        </Label>
                        <NumberInput
                            value={data.capacity_volume}
                            onValueChange={(value) =>
                                setData('capacity_volume', value)
                            }
                            min={0}
                            decimals={2}
                            className={`h-[42px] rounded-[10px] ${
                                errors.capacity_volume ? 'border-bad' : ''
                            }`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Espacio cúbico disponible en la cava o plataforma
                        </span>
                        {errors.capacity_volume && (
                            <p className="text-sm text-bad">
                                {errors.capacity_volume}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="notes"
                            className="text-[13px] font-semibold"
                        >
                            Notas / Observaciones
                        </Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="rounded-[10px]"
                            placeholder="Revisiones mecánicas, asignación habitual de chofer, vencimiento de seguros..."
                        />
                        {errors.notes && (
                            <p className="text-sm text-bad">{errors.notes}</p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow
                    label="Placa"
                    value={data.plate ? data.plate.toUpperCase() : '—'}
                />
                <SummaryRow
                    label="Nombre"
                    value={data.name || '—'}
                />
                <SummaryRow
                    label="Tipo"
                    value={
                        VEHICLE_TYPE_LABELS[data.vehicle_type] ||
                        data.vehicle_type
                    }
                />
                <SummaryRow
                    label="Capacidad Peso"
                    value={
                        data.capacity_weight > 0
                            ? `${data.capacity_weight} kg`
                            : 'Sin declarar'
                    }
                />
                <SummaryRow
                    label="Capacidad Volumen"
                    value={
                        data.capacity_volume > 0
                            ? `${data.capacity_volume} m³`
                            : 'Sin declarar'
                    }
                />
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Guardar camión' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
