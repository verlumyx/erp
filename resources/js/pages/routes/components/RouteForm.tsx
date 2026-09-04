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
import { useRouteFormContext } from '../contexts/RouteFormContext';
import {
    FREQUENCY_LABELS,
    TYPE_LABELS,
    WEEKDAY_LABELS,
    WEEKDAY_ORDER,
    type RouteFrequency,
    type RouteType,
} from '../types/Route';
import { RouteClientsSection } from './RouteClientsSection';

const TYPE_OPTIONS: OptionType[] = (
    Object.keys(TYPE_LABELS) as RouteType[]
).map((value) => ({ value, label: TYPE_LABELS[value] }));

const FREQUENCY_OPTIONS: OptionType[] = (
    Object.keys(FREQUENCY_LABELS) as RouteFrequency[]
).map((value) => ({ value, label: FREQUENCY_LABELS[value] }));

/** Valor del select cuando el campo se deja sin asignar. */
const NONE = 'none';

export function RouteForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        toggleWeekday,
    } = useRouteFormContext();

    const withNone = (
        rows: Array<{ id: string; name: string }>,
        emptyLabel: string,
    ): OptionType[] => [
        { value: NONE, label: emptyLabel },
        ...rows.map((row) => ({ value: row.id, label: row.name })),
    ];

    const warehouseOptions = withNone(options.warehouses, 'Sin bodega fija');
    const driverOptions = withNone(options.users, 'Sin conductor asignado');
    const salespersonOptions = withNone(options.users, 'Sin vendedor asignado');

    const pick = (list: OptionType[], value: string) =>
        list.find((option) => option.value === (value || NONE)) ?? null;

    const chosen = (option: OptionType | null) =>
        !option || option.value === NONE ? '' : option.value;

    /** Los días solo se piden cuando la frecuencia los usa. */
    const usesWeekdays =
        data.frequency === 'weekly' || data.frequency === 'biweekly';

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="La ruta"
                sub="Cómo se llama, para qué se recorre y por dónde pasa"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="name"
                            className="text-[13px] font-semibold"
                        >
                            Nombre *
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            maxLength={150}
                            className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                            placeholder="Zona Norte - Lunes"
                        />
                        {errors.name && (
                            <p className="text-sm text-bad">{errors.name}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo *
                        </Label>
                        <Select2
                            inputId="type"
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (option) => option.value === data.type,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'type',
                                    (option?.value ?? 'delivery') as RouteType,
                                )
                            }
                            error={!!errors.type}
                            size="md"
                            placeholder="Para qué se recorre"
                        />
                        {errors.type && (
                            <p className="text-sm text-bad">{errors.type}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Bodega de salida
                        </Label>
                        <Select2
                            inputId="warehouse_id"
                            options={warehouseOptions}
                            value={pick(warehouseOptions, data.warehouse_id)}
                            onChange={(option) =>
                                setData('warehouse_id', chosen(option))
                            }
                            error={!!errors.warehouse_id}
                            size="md"
                            placeholder="De dónde sale la carga"
                        />
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="zone"
                            className="text-[13px] font-semibold"
                        >
                            Zona
                        </Label>
                        <Input
                            id="zone"
                            value={data.zone}
                            onChange={(e) => setData('zone', e.target.value)}
                            maxLength={100}
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.zone && (
                            <p className="text-sm text-bad">{errors.zone}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="city"
                            className="text-[13px] font-semibold"
                        >
                            Ciudad
                        </Label>
                        <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            maxLength={100}
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.city && (
                            <p className="text-sm text-bad">{errors.city}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="description"
                            className="text-[13px] font-semibold"
                        >
                            Descripción
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={2}
                            className="rounded-[10px]"
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Cuándo se recorre"
                sub="Cada cuánto sale la ruta y cuánto dura el viaje"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Frecuencia *
                        </Label>
                        <Select2
                            inputId="frequency"
                            options={FREQUENCY_OPTIONS}
                            value={
                                FREQUENCY_OPTIONS.find(
                                    (option) => option.value === data.frequency,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'frequency',
                                    (option?.value ??
                                        'weekly') as RouteFrequency,
                                )
                            }
                            error={!!errors.frequency}
                            size="md"
                            placeholder="Cada cuánto se recorre"
                        />
                        {errors.frequency && (
                            <p className="text-sm text-bad">
                                {errors.frequency}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Días de ejecución
                        </Label>
                        <div className="flex flex-wrap gap-1.5">
                            {WEEKDAY_ORDER.map((day) => {
                                const active = data.weekdays.includes(day);

                                return (
                                    <button
                                        key={day}
                                        type="button"
                                        onClick={() => toggleWeekday(day)}
                                        disabled={!usesWeekdays}
                                        className={`h-[34px] min-w-[46px] rounded-[9px] border px-2 text-[13px] font-semibold transition-colors disabled:opacity-50 ${
                                            active
                                                ? 'border-primary bg-primary-soft text-primary'
                                                : 'bg-card text-muted-foreground hover:bg-muted'
                                        }`}
                                    >
                                        {WEEKDAY_LABELS[day]}
                                    </button>
                                );
                            })}
                        </div>
                        <span className="text-[12px] text-muted-foreground">
                            {usesWeekdays
                                ? 'Los días en que sale esta ruta'
                                : 'Esa frecuencia no se fija por días de la semana'}
                        </span>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Duración estimada (minutos)
                        </Label>
                        <NumberInput
                            value={data.estimated_duration_minutes}
                            onValueChange={(value) =>
                                setData(
                                    'estimated_duration_minutes',
                                    Math.round(value),
                                )
                            }
                            min={0}
                            decimals={0}
                            className={`h-[42px] rounded-[10px] ${errors.estimated_duration_minutes ? 'border-bad' : ''}`}
                        />
                        {errors.estimated_duration_minutes && (
                            <p className="text-sm text-bad">
                                {errors.estimated_duration_minutes}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Distancia estimada (km)
                        </Label>
                        <NumberInput
                            value={data.estimated_distance_km}
                            onValueChange={(value) =>
                                setData('estimated_distance_km', value)
                            }
                            min={0}
                            decimals={2}
                            className={`h-[42px] rounded-[10px] ${errors.estimated_distance_km ? 'border-bad' : ''}`}
                        />
                        {errors.estimated_distance_km && (
                            <p className="text-sm text-bad">
                                {errors.estimated_distance_km}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Clientes de la ruta"
                sub="A quién se visita y en qué orden. De aquí salen las paradas de cada día"
            >
                <RouteClientsSection />
            </FormSection>

            <FormSection
                step={4}
                title="Vehículo y equipo"
                sub="Quién recorre la ruta y cuánto aguanta el vehículo"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Conductor
                        </Label>
                        <Select2
                            inputId="driver_id"
                            options={driverOptions}
                            value={pick(driverOptions, data.driver_id)}
                            onChange={(option) =>
                                setData('driver_id', chosen(option))
                            }
                            error={!!errors.driver_id}
                            size="md"
                            placeholder="Quién conduce"
                        />
                        {errors.driver_id && (
                            <p className="text-sm text-bad">
                                {errors.driver_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Vendedor
                        </Label>
                        <Select2
                            inputId="salesperson_id"
                            options={salespersonOptions}
                            value={pick(
                                salespersonOptions,
                                data.salesperson_id,
                            )}
                            onChange={(option) =>
                                setData('salesperson_id', chosen(option))
                            }
                            error={!!errors.salesperson_id}
                            size="md"
                            placeholder="Quién vende en la ruta"
                        />
                        {errors.salesperson_id && (
                            <p className="text-sm text-bad">
                                {errors.salesperson_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="vehicle_plate"
                            className="text-[13px] font-semibold"
                        >
                            Placa del vehículo
                        </Label>
                        <Input
                            id="vehicle_plate"
                            value={data.vehicle_plate}
                            onChange={(e) =>
                                setData('vehicle_plate', e.target.value)
                            }
                            maxLength={20}
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.vehicle_plate && (
                            <p className="text-sm text-bad">
                                {errors.vehicle_plate}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Capacidad en peso
                        </Label>
                        <NumberInput
                            value={data.vehicle_capacity_weight}
                            onValueChange={(value) =>
                                setData('vehicle_capacity_weight', value)
                            }
                            min={0}
                            decimals={4}
                            className={`h-[42px] rounded-[10px] ${errors.vehicle_capacity_weight ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            En cero no se comprueba nada al planificar
                        </span>
                        {errors.vehicle_capacity_weight && (
                            <p className="text-sm text-bad">
                                {errors.vehicle_capacity_weight}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Capacidad en volumen
                        </Label>
                        <NumberInput
                            value={data.vehicle_capacity_volume}
                            onValueChange={(value) =>
                                setData('vehicle_capacity_volume', value)
                            }
                            min={0}
                            decimals={4}
                            className={`h-[42px] rounded-[10px] ${errors.vehicle_capacity_volume ? 'border-bad' : ''}`}
                        />
                        {errors.vehicle_capacity_volume && (
                            <p className="text-sm text-bad">
                                {errors.vehicle_capacity_volume}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="notes"
                            className="text-[13px] font-semibold"
                        >
                            Notas
                        </Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            rows={3}
                            className="rounded-[10px]"
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Clientes fijos">
                    {data.clients.length}
                </SummaryRow>
                <SummaryRow label="Frecuencia">
                    {FREQUENCY_LABELS[data.frequency]}
                </SummaryRow>
                <SummaryRow label="Tipo" divider>
                    {TYPE_LABELS[data.type]}
                </SummaryRow>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    La ruta planifica el viaje, no mueve mercancía. Las paradas
                    de un día se generan después, desde esta lista más los
                    despachos que ya estén asignados a la ruta.
                </p>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear ruta' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
