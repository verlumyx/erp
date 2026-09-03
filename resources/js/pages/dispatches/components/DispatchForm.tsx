import { Check } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useDispatchFormContext } from '../contexts/DispatchFormContext';
import { DispatchLinesSection } from './DispatchLinesSection';

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

export function DispatchForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        currency,
        totals,
        addresses,
        clientLookupUrl,
        clientOption,
        selectClient,
        sourceLookupUrl,
        sourceOption,
        selectSource,
        selectWarehouse,
        routeLookupUrl,
        routeOption,
        selectRoute,
        options,
    } = useDispatchFormContext();

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    const driverOptions: OptionType[] = options.drivers.map((driver) => ({
        value: driver.id,
        label: driver.name,
    }));

    const addressOptions: OptionType[] = addresses.map((address) => ({
        value: address.id,
        label: address.address
            ? `${address.name} — ${address.address}`
            : address.name,
    }));

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del despacho"
                        sub="A quién se le lleva, de qué pedido sale y de qué bodega"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="client_id"
                                className="text-[13px] font-semibold"
                            >
                                Cliente *
                            </Label>
                            <Select2Ajax
                                inputId="client_id"
                                url={clientLookupUrl}
                                value={clientOption}
                                onChange={selectClient}
                                error={!!errors.client_id}
                                size="md"
                                placeholder="Busca un cliente"
                            />
                            {errors.client_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Pedido de origen
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={sourceLookupUrl}
                                params={{
                                    client_id: data.client_id,
                                    dispatchable: 'yes',
                                }}
                                value={sourceOption}
                                onChange={selectSource}
                                error={!!errors.sourceable_id}
                                isClearable
                                isDisabled={data.client_id === ''}
                                size="md"
                                placeholder={
                                    data.client_id === ''
                                        ? 'Elige antes el cliente'
                                        : 'Despacho directo, sin pedido'
                                }
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Atarlo limita lo despachado a lo que se pidió
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega *
                            </Label>
                            <Select2
                                inputId="warehouse_id"
                                options={warehouseOptions}
                                value={
                                    warehouseOptions.find(
                                        (option) =>
                                            option.value === data.warehouse_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    selectWarehouse(option?.value ?? '')
                                }
                                error={!!errors.warehouse_id}
                                size="md"
                                placeholder="De dónde sale la mercancía"
                            />
                            {errors.warehouse_id && (
                                <p className="text-sm text-bad">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="dispatch_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de salida *
                            </Label>
                            <Input
                                id="dispatch_date"
                                type="date"
                                value={data.dispatch_date}
                                onChange={(e) =>
                                    setData('dispatch_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.dispatch_date ? 'border-bad' : ''}`}
                            />
                            {errors.dispatch_date && (
                                <p className="text-sm text-bad">
                                    {errors.dispatch_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label className="text-[13px] font-semibold">
                                Dirección de entrega
                            </Label>
                            <Select2
                                inputId="client_address_id"
                                options={addressOptions}
                                value={
                                    addressOptions.find(
                                        (option) =>
                                            option.value ===
                                            data.client_address_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'client_address_id',
                                        option?.value ?? '',
                                    )
                                }
                                error={!!errors.client_address_id}
                                isClearable
                                isDisabled={addresses.length === 0}
                                size="md"
                                placeholder={
                                    addresses.length === 0
                                        ? 'El cliente no tiene direcciones'
                                        : 'Elige a dónde se entrega'
                                }
                            />
                            {errors.client_address_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_address_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Carga"
                        sub="Qué sale, de qué ubicación y con qué lote o serie"
                    />
                    <DispatchLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Transporte"
                        sub="Por qué ruta sale, quién la lleva y con qué guía"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="route_id"
                                className="text-[13px] font-semibold"
                            >
                                Ruta
                            </Label>
                            <Select2Ajax
                                inputId="route_id"
                                url={routeLookupUrl}
                                params={{ status: 'active' }}
                                value={routeOption}
                                onChange={selectRoute}
                                error={!!errors.route_id}
                                isClearable
                                size="md"
                                placeholder="Sin ruta asignada"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Asignarlo mete el despacho en el recorrido: al
                                planificar el día, la ruta le crea su parada
                            </span>
                            {errors.route_id && (
                                <p className="text-sm text-bad">
                                    {errors.route_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Conductor
                            </Label>
                            <Select2
                                inputId="driver_id"
                                options={driverOptions}
                                value={
                                    driverOptions.find(
                                        (option) =>
                                            option.value === data.driver_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData('driver_id', option?.value ?? '')
                                }
                                error={!!errors.driver_id}
                                isClearable
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
                            <Label
                                htmlFor="carrier"
                                className="text-[13px] font-semibold"
                            >
                                Transportista
                            </Label>
                            <Input
                                id="carrier"
                                value={data.carrier}
                                onChange={(e) =>
                                    setData('carrier', e.target.value)
                                }
                                maxLength={150}
                                className="h-[42px] rounded-[10px]"
                                placeholder="Si lo lleva un tercero"
                            />
                            {errors.carrier && (
                                <p className="text-sm text-bad">
                                    {errors.carrier}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="tracking_number"
                                className="text-[13px] font-semibold"
                            >
                                Guía de transporte
                            </Label>
                            <Input
                                id="tracking_number"
                                value={data.tracking_number}
                                onChange={(e) =>
                                    setData('tracking_number', e.target.value)
                                }
                                maxLength={60}
                                className="h-[42px] rounded-[10px]"
                            />
                            {errors.tracking_number && (
                                <p className="text-sm text-bad">
                                    {errors.tracking_number}
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
                                className="rounded-[10px]"
                                rows={3}
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
                            Líneas
                        </span>
                        <b className="font-bold">{data.lines.length}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Unidades
                        </span>
                        <b className="font-bold tabular-nums">
                            {totals.quantity}
                        </b>
                    </div>
                </div>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    El despacho solo decide qué sale y cuánto: el precio lo trae
                    el pedido y es informativo —la guía no factura—. Al
                    confirmarlo, la mercancía sale del inventario al costo
                    promedio vigente.
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
                          ? 'Crear despacho'
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
