import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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

interface FormSectionHeadProps {
    step: number;
    title: string;
    sub: string;
}

function FormSectionHead({ step, title, sub }: FormSectionHeadProps) {
    return (
        <div className="flex items-center gap-3 border-b p-5">
            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary">
                {step}
            </span>
            <div className="mr-auto">
                <div className="text-base font-bold tracking-tight">
                    {title}
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    {sub}
                </div>
            </div>
        </div>
    );
}

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
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="La ruta"
                        sub="Cómo se llama, para qué se recorre y por dónde pasa"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="name"
                                className="text-[13px] font-semibold"
                            >
                                Nombre *
                            </Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                maxLength={150}
                                className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                placeholder="Zona Norte - Lunes"
                            />
                            {errors.name && (
                                <p className="text-sm text-bad">
                                    {errors.name}
                                </p>
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
                                        (option?.value ??
                                            'delivery') as RouteType,
                                    )
                                }
                                error={!!errors.type}
                                size="md"
                                placeholder="Para qué se recorre"
                            />
                            {errors.type && (
                                <p className="text-sm text-bad">
                                    {errors.type}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega de salida
                            </Label>
                            <Select2
                                inputId="warehouse_id"
                                options={warehouseOptions}
                                value={pick(
                                    warehouseOptions,
                                    data.warehouse_id,
                                )}
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
                                onChange={(e) =>
                                    setData('zone', e.target.value)
                                }
                                maxLength={100}
                                className="h-[42px] rounded-[10px]"
                            />
                            {errors.zone && (
                                <p className="text-sm text-bad">
                                    {errors.zone}
                                </p>
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
                                onChange={(e) =>
                                    setData('city', e.target.value)
                                }
                                maxLength={100}
                                className="h-[42px] rounded-[10px]"
                            />
                            {errors.city && (
                                <p className="text-sm text-bad">
                                    {errors.city}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
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
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Cuándo se recorre"
                        sub="Cada cuánto sale la ruta y cuánto dura el viaje"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Frecuencia *
                            </Label>
                            <Select2
                                inputId="frequency"
                                options={FREQUENCY_OPTIONS}
                                value={
                                    FREQUENCY_OPTIONS.find(
                                        (option) =>
                                            option.value === data.frequency,
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
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Clientes de la ruta"
                        sub="A quién se visita y en qué orden. De aquí salen las paradas de cada día"
                    />
                    <RouteClientsSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Vehículo y equipo"
                        sub="Quién recorre la ruta y cuánto aguanta el vehículo"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="notes"
                                className="text-[13px] font-semibold"
                            >
                                Notas
                            </Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                rows={3}
                                className="rounded-[10px]"
                            />
                        </div>
                    </div>
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Clientes fijos
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.clients.length}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Frecuencia
                        </span>
                        <b className="font-bold">
                            {FREQUENCY_LABELS[data.frequency]}
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t pt-2.5 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tipo
                        </span>
                        <b className="font-bold">{TYPE_LABELS[data.type]}</b>
                    </div>
                </div>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    La ruta planifica el viaje, no mueve mercancía. Las paradas
                    de un día se generan después, desde esta lista más los
                    despachos que ya estén asignados a la ruta.
                </p>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear ruta'
                          : 'Guardar cambios'}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    className="h-10 w-full justify-center rounded-[11px] bg-card font-semibold"
                    onClick={() => window.history.back()}
                    disabled={processing}
                >
                    Cancelar
                </Button>
            </Card>
        </form>
    );
}
