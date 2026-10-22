import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Select2Ajax } from '@/components/select2-ajax';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useDispatchFormContext } from '../contexts/DispatchFormContext';
import { TRANSFER_REASONS } from '../types/Dispatch';
import { DispatchLinesSection } from './DispatchLinesSection';

export function DispatchForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        addresses,
        flow,
        clientLookupUrl,
        clientOption,
        selectClient,
        supplierLookupUrl,
        supplierOption,
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
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos del despacho"
                sub={
                    flow === 'supplier'
                        ? 'A qué proveedor se le regresa, de qué devolución sale y de qué bodega'
                        : 'A quién se le lleva, de qué pedido sale y de qué bodega'
                }
            >
                <FormFieldGrid>
                    {flow === 'supplier' ? (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="recipient_id"
                                className="text-[13px] font-semibold"
                            >
                                Proveedor *
                            </Label>
                            <Select2Ajax
                                inputId="recipient_id"
                                url={supplierLookupUrl}
                                value={supplierOption}
                                onChange={() => {}}
                                error={!!errors.recipient_id}
                                isDisabled
                                size="md"
                                placeholder="Proveedor de la devolución"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                La devolución fija a quién vuelve la mercancía
                            </span>
                            {errors.recipient_id && (
                                <p className="text-sm text-bad">
                                    {errors.recipient_id}
                                </p>
                            )}
                        </div>
                    ) : (
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
                    )}

                    {flow === 'supplier' ? (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Devolución de origen
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={sourceLookupUrl}
                                value={sourceOption}
                                onChange={() => {}}
                                error={!!errors.sourceable_id}
                                isDisabled
                                size="md"
                                placeholder="Devolución de compra"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Lo que se saca sale de esta devolución
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    ) : (
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
                    )}

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

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="control_number"
                            className="text-[13px] font-semibold"
                        >
                            N° de Control fiscal
                        </Label>
                        <Input
                            id="control_number"
                            value={data.control_number}
                            onChange={(e) =>
                                setData('control_number', e.target.value)
                            }
                            maxLength={30}
                            placeholder="Auto (00-00000001)"
                            className="h-[42px] rounded-[10px]"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Correlativo legal SENIAT (Art. 13.3)
                        </span>
                        {errors.control_number && (
                            <p className="text-sm text-bad">
                                {errors.control_number}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            ¿Representa venta?
                        </Label>
                        <Select2
                            inputId="is_sale"
                            options={[
                                { value: 'yes', label: 'Sí (Ampara venta de bienes)' },
                                { value: 'no', label: 'No (Traslado no comercial)' },
                            ]}
                            value={{
                                value: data.is_sale ? 'yes' : 'no',
                                label: data.is_sale
                                    ? 'Sí (Ampara venta de bienes)'
                                    : 'No (Traslado no comercial)',
                            }}
                            onChange={(opt) => {
                                const isSale = opt?.value === 'yes';
                                setData('is_sale', isSale);
                                if (isSale) {
                                    setData('transfer_reason', 'sale');
                                } else if (data.transfer_reason === 'sale') {
                                    setData('transfer_reason', 'internal_transfer');
                                }
                            }}
                            size="md"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Art. 20 y 21 PA 00071
                        </span>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Motivo del traslado
                        </Label>
                        <Select2
                            inputId="transfer_reason"
                            options={Object.entries(TRANSFER_REASONS).map(([value, label]) => ({
                                value,
                                label,
                            }))}
                            value={
                                Object.entries(TRANSFER_REASONS)
                                    .map(([value, label]) => ({ value, label }))
                                    .find((opt) => opt.value === data.transfer_reason) ?? null
                            }
                            onChange={(opt) => {
                                const reason = opt?.value ?? 'sale';
                                setData('transfer_reason', reason);
                                setData('is_sale', reason === 'sale');
                            }}
                            size="md"
                        />
                        {errors.transfer_reason && (
                            <p className="text-sm text-bad">
                                {errors.transfer_reason}
                            </p>
                        )}
                    </div>

                    {!data.is_sale && (
                        <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                            <Label
                                htmlFor="transfer_reason_detail"
                                className="text-[13px] font-semibold"
                            >
                                Justificación del traslado no comercial (Art. 21)
                            </Label>
                            <Input
                                id="transfer_reason_detail"
                                value={data.transfer_reason_detail}
                                onChange={(e) =>
                                    setData('transfer_reason_detail', e.target.value)
                                }
                                maxLength={500}
                                placeholder="Ej: Traslado a sucursal para exhibición, reparación de equipos, etc."
                                className="h-[42px] rounded-[10px]"
                            />
                            {errors.transfer_reason_detail && (
                                <p className="text-sm text-bad">
                                    {errors.transfer_reason_detail}
                                </p>
                            )}
                        </div>
                    )}

                    <div
                        className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3"
                        hidden={flow === 'supplier'}
                    >
                        <Label className="text-[13px] font-semibold">
                            Dirección de entrega
                        </Label>
                        <Select2
                            inputId="client_address_id"
                            options={addressOptions}
                            value={
                                addressOptions.find(
                                    (option) =>
                                        option.value === data.client_address_id,
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Carga"
                sub="Qué sale, de qué ubicación y con qué lote o serie"
            >
                <DispatchLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Transporte"
                sub="Por qué ruta sale, quién la lleva y con qué guía"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
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
                                    (option) => option.value === data.driver_id,
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
                            onChange={(e) => setData('carrier', e.target.value)}
                            maxLength={150}
                            className="h-[42px] rounded-[10px]"
                            placeholder="Si lo lleva un tercero"
                        />
                        {errors.carrier && (
                            <p className="text-sm text-bad">{errors.carrier}</p>
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
                            className="rounded-[10px]"
                            rows={3}
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Líneas">{data.lines.length}</SummaryRow>
                <SummaryRow label="Unidades">{totals.quantity}</SummaryRow>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    El despacho solo decide qué sale y cuánto: el precio lo trae
                    el pedido y es informativo —la guía no factura—. Al
                    confirmarlo, la mercancía sale del inventario al costo
                    promedio vigente.
                </p>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear despacho' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
