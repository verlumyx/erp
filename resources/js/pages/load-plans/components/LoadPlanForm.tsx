import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useLoadPlanFormContext } from '../contexts/LoadPlanFormContext';
import { LoadPlanDispatchesSection } from './LoadPlanDispatchesSection';

export function LoadPlanForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        currentVehicle,
        totalWeight,
        totalVolume,
        totalDispatches,
        weightPercentage,
        volumePercentage,
        handleRouteChange,
    } = useLoadPlanFormContext();

    const routeOptions: OptionType[] = [
        { value: '', label: 'Seleccionar ruta de entrega...' },
        ...options.routes.map((r) => ({
            value: r.id,
            label: r.code ? `${r.code} — ${r.name}` : r.name,
        })),
    ];

    const warehouseOptions: OptionType[] = [
        { value: '', label: 'Seleccionar bodega de salida...' },
        ...options.warehouses.map((w) => ({
            value: w.id,
            label: w.name,
        })),
    ];

    const vehicleOptions: OptionType[] = [
        { value: '', label: 'Seleccionar camión / unidad...' },
        ...options.vehicles.map((v) => ({
            value: v.id,
            label: `${v.plate} — ${v.name} (${Number(v.capacity_weight).toLocaleString('es-VE')} kg / ${Number(v.capacity_volume).toLocaleString('es-VE')} m³)`,
        })),
    ];

    const userDriverOptions: OptionType[] = [
        { value: '', label: 'Elegir usuario del sistema (opcional)...' },
        ...options.drivers.map((u) => ({
            value: u.id,
            label: u.name,
        })),
    ];

    const selectedRoute = options.routes.find((r) => r.id === data.route_id);

    return (
        <FormLayout onSubmit={handleSubmit}>
            {/* Step 1: Header */}
            <FormSection
                step={1}
                title="Cabecera del Plan de Carga"
                sub="Fecha, ruta comercial, camión y tripulación asignada"
            >
                <FormFieldGrid>
                    {/* Plan Date */}
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="plan_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha del Plan *
                        </Label>
                        <Input
                            id="plan_date"
                            type="date"
                            value={data.plan_date}
                            onChange={(e) => setData('plan_date', e.target.value)}
                            className={`h-[42px] rounded-[10px] ${
                                errors.plan_date ? 'border-bad' : ''
                            }`}
                        />
                        {errors.plan_date && (
                            <p className="text-sm text-bad">{errors.plan_date}</p>
                        )}
                    </div>

                    {/* Route */}
                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <Label
                            htmlFor="route_id"
                            className="text-[13px] font-semibold"
                        >
                            Ruta o Flete *
                        </Label>
                        <Select2
                            inputId="route_id"
                            options={routeOptions}
                            value={
                                routeOptions.find(
                                    (opt) => opt.value === data.route_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                handleRouteChange(option?.value ?? '')
                            }
                            error={!!errors.route_id}
                            size="md"
                        />
                        {errors.route_id && (
                            <p className="text-sm text-bad">{errors.route_id}</p>
                        )}
                    </div>

                    {/* Warehouse */}
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="warehouse_id"
                            className="text-[13px] font-semibold"
                        >
                            Bodega de Origen *
                        </Label>
                        <Select2
                            inputId="warehouse_id"
                            options={warehouseOptions}
                            value={
                                warehouseOptions.find(
                                    (opt) => opt.value === data.warehouse_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData('warehouse_id', option?.value ?? '')
                            }
                            error={!!errors.warehouse_id}
                            size="md"
                        />
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    {/* Vehicle */}
                    <div className="flex flex-col gap-1.5 sm:col-span-2">
                        <Label
                            htmlFor="vehicle_id"
                            className="text-[13px] font-semibold"
                        >
                            Camión / Vehículo Asignado *
                        </Label>
                        <Select2
                            inputId="vehicle_id"
                            options={vehicleOptions}
                            value={
                                vehicleOptions.find(
                                    (opt) => opt.value === data.vehicle_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData('vehicle_id', option?.value ?? '')
                            }
                            error={!!errors.vehicle_id}
                            size="md"
                        />
                        {errors.vehicle_id && (
                            <p className="text-sm text-bad">
                                {errors.vehicle_id}
                            </p>
                        )}
                    </div>

                    {/* Driver Name & Driver User Select */}
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="driver_name"
                            className="text-[13px] font-semibold"
                        >
                            Nombre del Conductor *
                        </Label>
                        <Input
                            id="driver_name"
                            value={data.driver_name}
                            onChange={(e) =>
                                setData('driver_name', e.target.value)
                            }
                            maxLength={150}
                            className={`h-[42px] rounded-[10px] ${
                                errors.driver_name ? 'border-bad' : ''
                            }`}
                            placeholder="Cédula y Nombre del chofer"
                        />
                        {errors.driver_name && (
                            <p className="text-sm text-bad">
                                {errors.driver_name}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="driver_id"
                            className="text-[13px] font-semibold"
                        >
                            Usuario Conductor (Vínculo)
                        </Label>
                        <Select2
                            inputId="driver_id"
                            options={userDriverOptions}
                            value={
                                userDriverOptions.find(
                                    (opt) => opt.value === data.driver_id,
                                ) ?? null
                            }
                            onChange={(option) => {
                                setData('driver_id', option?.value ?? '');
                                if (option?.value && !data.driver_name) {
                                    setData('driver_name', option.label);
                                }
                            }}
                            size="md"
                        />
                    </div>

                    {/* Helper Name */}
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="helper_name"
                            className="text-[13px] font-semibold"
                        >
                            Ayudante / Acompañante
                        </Label>
                        <Input
                            id="helper_name"
                            value={data.helper_name}
                            onChange={(e) =>
                                setData('helper_name', e.target.value)
                            }
                            maxLength={255}
                            className="h-[42px] rounded-[10px]"
                            placeholder="Cédula y Nombre del ayudante"
                        />
                    </div>

                    {/* Description / Notes */}
                    <div className="flex flex-col gap-1.5 sm:col-span-3">
                        <Label
                            htmlFor="description"
                            className="text-[13px] font-semibold"
                        >
                            Descripción u Observaciones
                        </Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={2}
                            className="rounded-[10px]"
                            placeholder="Instrucciones especiales de viaje, precintos de seguridad, flete de retorno..."
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            {/* Step 2: Dispatches Details */}
            <FormSection
                step={2}
                title="Detalles del Plan de Carga"
                sub="Inclusión de facturas/despachos generados para esta ruta"
            >
                <LoadPlanDispatchesSection />
            </FormSection>

            {/* Summary */}
            <FormSummary>
                <SummaryRow
                    label="Ruta / Flete"
                    value={selectedRoute?.name ?? '—'}
                />
                <SummaryRow
                    label="Camión"
                    value={
                        currentVehicle
                            ? `${currentVehicle.plate} (${currentVehicle.name})`
                            : '—'
                    }
                />
                <SummaryRow
                    label="Conductor"
                    value={data.driver_name || '—'}
                />
                <SummaryRow
                    label="Despachos"
                    value={`${totalDispatches} despacho(s)`}
                />
                <SummaryRow
                    label="Carga Peso"
                    value={`${totalWeight.toLocaleString('es-VE', { maximumFractionDigits: 1 })} kg (${weightPercentage}%)`}
                />
                <SummaryRow
                    label="Carga Volumen"
                    value={`${totalVolume.toLocaleString('es-VE', { maximumFractionDigits: 2 })} m³ (${volumePercentage}%)`}
                />
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create'
                        ? 'Crear plan de carga'
                        : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
