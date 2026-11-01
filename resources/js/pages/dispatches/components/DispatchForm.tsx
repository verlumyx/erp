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
        sourceType,
        setSourceType,
        clientLookupUrl,
        clientOption,
        selectClient,
        supplierLookupUrl,
        supplierOption,
        sourceLookupUrl,
        sourceOption,
        selectSource,
        salesInvoiceLookupUrl,
        salesInvoiceOption,
        selectSalesInvoice,
        purchaseReturnLookupUrl,
        purchaseReturnOption,
        selectPurchaseReturn,
        transferLookupUrl,
        transferOption,
        selectTransfer,
        selectWarehouse,
        routeLookupUrl,
        routeOption,
        selectRoute,
        options,
        clearErrors,
    } = useDispatchFormContext();

    const SOURCE_TYPE_OPTIONS: OptionType[] = [
        { value: 'none', label: 'Seleccione' },
        { value: 'sales_order', label: 'Orden de venta' },
        { value: 'sales_invoice', label: 'Factura de venta' },
        { value: 'purchase_return', label: 'Devolución de compra' },
        { value: 'transfer', label: 'Traslado entre bodegas' },
    ];

    const handleSourceTypeChange = (opt: OptionType | null) => {
        const next = opt?.value ?? 'none';
        setSourceType(next);
        clearErrors('sourceable_type', 'sourceable_id');
        if (next === 'none') {
            selectSource(null);
            selectSalesInvoice(null);
            selectPurchaseReturn(null);
            selectTransfer(null);
        } else if (next === 'sales_order') {
            selectSalesInvoice(null);
            selectPurchaseReturn(null);
            selectTransfer(null);
        } else if (next === 'sales_invoice') {
            selectSource(null);
            selectPurchaseReturn(null);
            selectTransfer(null);
        } else if (next === 'purchase_return') {
            selectSource(null);
            selectSalesInvoice(null);
            selectTransfer(null);
        } else if (next === 'transfer') {
            selectSource(null);
            selectSalesInvoice(null);
            selectPurchaseReturn(null);
        }
    };

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
                title="Empezar registro desde"
                sub="Selecciona si deseas iniciar el despacho a partir de una orden de venta, factura, devolución o traslado"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="source_type"
                            className="text-[13px] font-semibold"
                        >
                            Origen del despacho *
                        </Label>
                        <Select2
                            inputId="source_type"
                            options={SOURCE_TYPE_OPTIONS}
                            value={
                                SOURCE_TYPE_OPTIONS.find(
                                    (opt) => opt.value === sourceType,
                                ) ?? SOURCE_TYPE_OPTIONS[0]
                            }
                            onChange={handleSourceTypeChange}
                            error={!!errors.sourceable_type}
                            isDisabled={mode === 'edit'}
                            size="md"
                        />
                        {errors.sourceable_type && (
                            <p className="text-sm text-bad">
                                {errors.sourceable_type}
                            </p>
                        )}
                    </div>

                    {sourceType === 'sales_order' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_sales_order"
                                className="text-[13px] font-semibold"
                            >
                                Orden de venta *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_sales_order"
                                url={sourceLookupUrl}
                                params={{
                                    dispatchable: 'yes',
                                    ...(data.client_id ? { client_id: data.client_id } : {}),
                                }}
                                value={sourceOption}
                                onChange={selectSource}
                                error={!!errors.sourceable_id}
                                isDisabled={mode === 'edit'}
                                size="md"
                                placeholder="Busca una orden de venta"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente el cliente, bodega y artículos pendientes
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    )}

                    {sourceType === 'sales_invoice' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_sales_invoice"
                                className="text-[13px] font-semibold"
                            >
                                Factura de venta *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_sales_invoice"
                                url={salesInvoiceLookupUrl}
                                value={salesInvoiceOption}
                                onChange={selectSalesInvoice}
                                error={!!errors.sourceable_id}
                                isDisabled={mode === 'edit'}
                                size="md"
                                placeholder="Busca una factura de venta"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente el cliente, bodega y artículos de la factura
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    )}

                    {sourceType === 'purchase_return' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_purchase_return"
                                className="text-[13px] font-semibold"
                            >
                                Devolución de compra *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_purchase_return"
                                url={purchaseReturnLookupUrl}
                                value={purchaseReturnOption}
                                onChange={selectPurchaseReturn}
                                error={!!errors.sourceable_id}
                                isDisabled={mode === 'edit'}
                                size="md"
                                placeholder="Busca una devolución de compra"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente el proveedor, bodega y artículos a devolver
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    )}

                    {sourceType === 'transfer' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_transfer"
                                className="text-[13px] font-semibold"
                            >
                                Traslado entre bodegas *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_transfer"
                                url={transferLookupUrl}
                                value={transferOption}
                                onChange={selectTransfer}
                                error={!!errors.sourceable_id}
                                isDisabled={mode === 'edit'}
                                size="md"
                                placeholder="Busca un traslado"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga la bodega de origen y los artículos a trasladar
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    )}
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Datos del despacho"
                sub={
                    flow === 'supplier' || sourceType === 'purchase_return'
                        ? 'A qué proveedor se le regresa y de qué bodega'
                        : sourceType === 'transfer'
                          ? 'Información del traslado entre bodegas'
                          : 'A quién se le lleva y de qué bodega'
                }
            >
                <FormFieldGrid>
                    {flow === 'supplier' || sourceType === 'purchase_return' ? (
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
                    ) : sourceType === 'transfer' ? (
                        <div className="flex flex-col justify-center gap-1 rounded-lg border border-border/60 bg-muted/20 p-3 sm:col-span-2">
                            <span className="text-[13px] font-semibold text-foreground">
                                Traslado interno entre bodegas
                            </span>
                            <span className="text-[12px] text-muted-foreground">
                                Este despacho corresponde a un movimiento entre bodegas propias, por lo que no requiere destinatario comercial (cliente ni proveedor).
                            </span>
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
                                isDisabled={
                                    mode === 'edit' ||
                                    sourceType === 'sales_order' ||
                                    sourceType === 'sales_invoice'
                                }
                                size="md"
                                placeholder="Busca un cliente"
                            />
                            {(sourceType === 'sales_order' || sourceType === 'sales_invoice') && (
                                <span className="text-[12px] text-muted-foreground">
                                    El cliente se cargó del documento de origen
                                </span>
                            )}
                            {errors.client_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_id}
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

                    {sourceType !== 'purchase_return' &&
                        flow !== 'supplier' &&
                        sourceType !== 'transfer' && (
                            <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
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
                        )}
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Carga"
                sub="Qué sale, de qué ubicación y con qué lote o serie"
            >
                <DispatchLinesSection />
            </FormSection>

            <FormSection
                step={4}
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
