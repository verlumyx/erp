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
    type EntryType,
} from '../types/Entry';
import { EntryLinesSection } from './EntryLinesSection';

const TYPE_OPTIONS: OptionType[] = SELECTABLE_TYPES.map((type) => ({
    value: type,
    label: TYPE_LABELS[type],
}));

const INSPECTION_OPTIONS: OptionType[] = Object.entries(INSPECTION_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function EntryForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        supplierLookupUrl,
        supplierOption,
        selectSupplier,
        orderLookupUrl,
        orderOption,
        selectOrder,
        selectCurrency,
        selectWarehouse,
        selectEntryType,
        allowsSupplier,
        requiresSupplier,
        options,
    } = useEntryFormContext();

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
                title="Datos de la entrada"
                sub="Qué llega, de dónde viene y a qué bodega entra"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo de entrada *
                        </Label>
                        <Select2
                            inputId="entry_type"
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (option) =>
                                        option.value === data.entry_type,
                                ) ?? null
                            }
                            onChange={(option) =>
                                selectEntryType(
                                    (option?.value ?? 'purchase') as EntryType,
                                )
                            }
                            error={!!errors.entry_type}
                            size="md"
                            placeholder="De dónde viene la mercancía"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            El inventario inicial se carga una sola vez por
                            artículo y bodega
                        </span>
                        {errors.entry_type && (
                            <p className="text-sm text-bad">
                                {errors.entry_type}
                            </p>
                        )}
                    </div>

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
                            isDisabled={!allowsSupplier}
                            size="md"
                            placeholder={
                                allowsSupplier
                                    ? 'Busca un proveedor'
                                    : 'El inventario inicial no tiene proveedor'
                            }
                        />
                        {errors.supplier_id && (
                            <p className="text-sm text-bad">
                                {errors.supplier_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="sourceable_id"
                            className="text-[13px] font-semibold"
                        >
                            Orden de compra
                        </Label>
                        <Select2Ajax
                            inputId="sourceable_id"
                            url={orderLookupUrl}
                            params={{ supplier_id: data.supplier_id }}
                            value={orderOption}
                            onChange={selectOrder}
                            error={!!errors.sourceable_id}
                            isClearable
                            isDisabled={
                                !allowsSupplier || data.supplier_id === ''
                            }
                            size="md"
                            placeholder={
                                data.supplier_id === ''
                                    ? 'Elige antes el proveedor'
                                    : 'Sin orden previa'
                            }
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Atarla limita lo recibido a lo que queda pendiente
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
                step={2}
                title="Líneas"
                sub="Qué llegó, qué se acepta, dónde se guarda y con qué lote"
            >
                <EntryLinesSection />
            </FormSection>

            <FormSection
                step={3}
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
                step={4}
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
