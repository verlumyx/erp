import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
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
import { useEntryFormContext } from '../contexts/EntryFormContext';
import {
    INSPECTION_LABELS,
    SELECTABLE_TYPES,
    TYPE_LABELS,
    type EntryInspectionStatus,
} from '../types/Entry';
import { EntryLinesSection } from './EntryLinesSection';

const SOURCE_TYPE_OPTIONS: OptionType[] = [
    { value: 'none', label: 'Seleccione' },
    { value: 'purchase_order', label: 'Orden de compra' },
    { value: 'sales_return', label: 'Devolución de cliente' },
    { value: 'transfer', label: 'Traslado entre bodegas' },
];

const INSPECTION_OPTIONS: OptionType[] = Object.entries(INSPECTION_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function EntryForm() {
    const {
        data,
        setData,
        processing,
        errors,
        clearErrors,
        handleSubmit,
        mode,
        totals,
        sourceType,
        setSourceType,
        supplierLookupUrl,
        supplierOption,
        selectSupplier,
        clientLookupUrl,
        clientOption,
        orderLookupUrl,
        orderOption,
        selectOrder,
        salesReturnLookupUrl,
        salesReturnOption,
        selectSalesReturn,
        transferLookupUrl,
        transferOption,
        selectTransfer,
        selectCurrency,
        selectWarehouse,
        allowsSupplier,
        requiresSupplier,
        options,
    } = useEntryFormContext();

    const handleSourceTypeChange = (opt: OptionType | null) => {
        const val = opt?.value ?? 'none';
        setSourceType(val);
        clearErrors('sourceable_type', 'sourceable_id');
        if (val === 'none') {
            selectOrder(null);
            selectSalesReturn(null);
            selectTransfer(null);
        } else if (val === 'purchase_order') {
            selectSalesReturn(null);
            selectTransfer(null);
        } else if (val === 'sales_return') {
            selectOrder(null);
            selectTransfer(null);
        } else if (val === 'transfer') {
            selectOrder(null);
            selectSalesReturn(null);
        }
    };

    const warehouseOptions: OptionType[] = options.warehouses.map(
        (warehouse) => ({ value: warehouse.id, label: warehouse.name }),
    );

    const peopleOptions: OptionType[] = options.receivers.map((receiver) => ({
        value: receiver.id,
        label: receiver.name,
    }));

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Empezar registro desde"
                sub="Selecciona si deseas iniciar la entrada a partir de una orden de compra, devolución o traslado"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="source_type"
                            className="text-[13px] font-semibold"
                        >
                            Origen de la entrada *
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

                    {sourceType === 'purchase_order' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Orden de compra *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={orderLookupUrl}
                                value={orderOption}
                                onChange={selectOrder}
                                error={!!errors.sourceable_id}
                                isClearable
                                size="md"
                                placeholder="Busca una orden de compra"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente el proveedor, bodega y líneas pendientes
                            </span>
                            {errors.sourceable_id && (
                                <p className="text-sm text-bad">
                                    {errors.sourceable_id}
                                </p>
                            )}
                        </div>
                    )}

                    {sourceType === 'sales_return' && (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Devolución de origen *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={salesReturnLookupUrl}
                                value={salesReturnOption}
                                onChange={selectSalesReturn}
                                error={!!errors.sourceable_id}
                                isClearable
                                size="md"
                                placeholder="Busca una devolución de venta"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente el cliente y los artículos devueltos
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
                                htmlFor="sourceable_id"
                                className="text-[13px] font-semibold"
                            >
                                Traslado de origen *
                            </Label>
                            <Select2Ajax
                                inputId="sourceable_id"
                                url={transferLookupUrl}
                                value={transferOption}
                                onChange={selectTransfer}
                                error={!!errors.sourceable_id}
                                isClearable
                                size="md"
                                placeholder="Busca un traslado"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Carga automáticamente la bodega de destino y los artículos transferidos
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
                title="Datos de la entrada"
                sub="Qué llega, de dónde viene y a qué bodega entra"
            >
                <FormFieldGrid>
                    {sourceType === 'sales_return' ? (
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
                                onChange={() => {}}
                                isDisabled
                                size="md"
                                placeholder="Cliente de la devolución"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Fijado por la devolución seleccionada
                            </span>
                        </div>
                    ) : sourceType === 'transfer' ? (
                        <div className="flex flex-col gap-1.5 justify-center">
                            <span className="text-[13px] font-semibold text-muted-foreground">
                                Destinatario / Tercero
                            </span>
                            <span className="text-[13px] text-muted-foreground">
                                Sin tercero (movimiento interno entre bodegas)
                            </span>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_id"
                                className="text-[13px] font-semibold"
                            >
                                Proveedor {requiresSupplier ? '*' : ''}
                            </Label>
                            <Select2Ajax
                                inputId="supplier_id"
                                url={supplierLookupUrl}
                                value={supplierOption}
                                onChange={selectSupplier}
                                error={!!errors.supplier_id}
                                isClearable
                                isDisabled={!allowsSupplier || (sourceType === 'purchase_order' && !!data.sourceable_id)}
                                size="md"
                                placeholder={
                                    allowsSupplier
                                        ? 'Busca un proveedor'
                                        : 'El inventario inicial no tiene proveedor'
                                }
                            />
                            {sourceType === 'purchase_order' && !!data.sourceable_id && (
                                <span className="text-[12px] text-muted-foreground">
                                    Fijado por la orden de compra seleccionada
                                </span>
                            )}
                            {errors.supplier_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_id}
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
                            placeholder="A dónde entra la mercancía"
                        />
                        {errors.warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="entry_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de recepción *
                        </Label>
                        <Input
                            id="entry_date"
                            type="date"
                            value={data.entry_date}
                            onChange={(e) =>
                                setData('entry_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.entry_date ? 'border-bad' : ''}`}
                        />
                        {errors.entry_date && (
                            <p className="text-sm text-bad">
                                {errors.entry_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="supplier_document"
                            className="text-[13px] font-semibold"
                        >
                            Remisión del proveedor
                        </Label>
                        <Input
                            id="supplier_document"
                            value={data.supplier_document}
                            onChange={(e) =>
                                setData('supplier_document', e.target.value)
                            }
                            maxLength={60}
                            placeholder="Guía o nota de entrega"
                            className="h-[42px] rounded-[10px]"
                        />
                        {errors.supplier_document && (
                            <p className="text-sm text-bad">
                                {errors.supplier_document}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Líneas"
                sub="Qué llegó, qué se acepta, dónde se guarda y con qué lote"
            >
                <EntryLinesSection />
            </FormSection>

            <FormSection
                step={4}
                title="Transporte e inspección"
                sub="Quién trajo la carga, quién la recibió y cómo salió el control"
            >
                <FormFieldGrid>
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
                        />
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
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Recibido por
                        </Label>
                        <Select2
                            inputId="received_by"
                            options={peopleOptions}
                            value={
                                peopleOptions.find(
                                    (option) =>
                                        option.value === data.received_by,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData('received_by', option?.value ?? '')
                            }
                            error={!!errors.received_by}
                            isClearable
                            size="md"
                            placeholder="Quién recibió la mercancía"
                        />
                        {errors.received_by && (
                            <p className="text-sm text-bad">
                                {errors.received_by}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Inspeccionado por
                        </Label>
                        <Select2
                            inputId="inspected_by"
                            options={peopleOptions}
                            value={
                                peopleOptions.find(
                                    (option) =>
                                        option.value === data.inspected_by,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData('inspected_by', option?.value ?? '')
                            }
                            error={!!errors.inspected_by}
                            isClearable
                            size="md"
                            placeholder="Quién hizo el control de calidad"
                        />
                        {errors.inspected_by && (
                            <p className="text-sm text-bad">
                                {errors.inspected_by}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label className="text-[13px] font-semibold">
                            Resultado del control *
                        </Label>
                        <Select2
                            inputId="inspection_status"
                            options={INSPECTION_OPTIONS}
                            value={
                                INSPECTION_OPTIONS.find(
                                    (option) =>
                                        option.value === data.inspection_status,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'inspection_status',
                                    (option?.value ??
                                        'pending') as EntryInspectionStatus,
                                )
                            }
                            error={!!errors.inspection_status}
                            size="md"
                            placeholder="Cómo salió la inspección"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Tiene que decir lo mismo que las líneas: aprobada no
                            rechaza nada y rechazada no acepta nada
                        </span>
                        {errors.inspection_status && (
                            <p className="text-sm text-bad">
                                {errors.inspection_status}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={5}
                title="Moneda"
                sub="La moneda del documento y su tasa de cambio"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="currency"
                            className="text-[13px] font-semibold"
                        >
                            Moneda *
                        </Label>
                        <CurrencySelect
                            id="currency"
                            value={data.currency}
                            error={errors.currency}
                            onValueChange={selectCurrency}
                            disabled={true}
                        />
                        {errors.currency && (
                            <p className="text-sm text-bad">
                                {errors.currency}
                            </p>
                        )}
                    </div>

                    <ExchangeRateField
                        value={data.exchange_rate}
                        onValueChange={(value) =>
                            setData('exchange_rate', value)
                        }
                        currency={data.currency}
                        dateLabel="la fecha de la entrada"
                        error={errors.exchange_rate}
                    />

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
                <SummaryRow label="Por recibir">
                    {totals.receivedQuantity + totals.rejectedQuantity}
                </SummaryRow>
                {totals.rejectedQuantity > 0 && (
                    <SummaryRow label="Rechazado">
                        {totals.rejectedQuantity}
                    </SummaryRow>
                )}
                <SummaryRow label="Entra al inventario" divider emphasis>
                    {totals.receivedQuantity}
                </SummaryRow>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    La entrada no captura el costo: sale de la orden de compra
                    o, sin orden, del promedio del artículo. Lo que cuesta traer
                    la mercancía se reparte después, con un expediente de
                    importación.
                </p>
            </FormSummary>

            <FormActionBar
                headlineLabel="Entra al inventario"
                headline={totals.receivedQuantity}
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear entrada' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
