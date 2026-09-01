import { Check } from 'lucide-react';
import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useEntryFormContext } from '../contexts/EntryFormContext';
import {
    INSPECTION_LABELS,
    TYPE_LABELS,
    type EntryInspectionStatus,
    type EntryType,
} from '../types/Entry';
import { EntryLinesSection } from './EntryLinesSection';

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

const TYPE_OPTIONS: OptionType[] = Object.entries(TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

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
        landedRatio,
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
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la entrada"
                        sub="Qué llega, de dónde viene y a qué bodega entra"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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
                                        (option?.value ??
                                            'purchase') as EntryType,
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
                                Atarla limita lo recibido a lo que queda
                                pendiente
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
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Qué llegó, qué se acepta, dónde se guarda y con qué lote"
                    />
                    <EntryLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Transporte e inspección"
                        sub="Quién trajo la carga, quién la recibió y cómo salió el control"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label className="text-[13px] font-semibold">
                                Resultado del control *
                            </Label>
                            <Select2
                                inputId="inspection_status"
                                options={INSPECTION_OPTIONS}
                                value={
                                    INSPECTION_OPTIONS.find(
                                        (option) =>
                                            option.value ===
                                            data.inspection_status,
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
                                Tiene que decir lo mismo que las líneas:
                                aprobada no rechaza nada y rechazada no acepta
                                nada
                            </span>
                            {errors.inspection_status && (
                                <p className="text-sm text-bad">
                                    {errors.inspection_status}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Costos y moneda"
                        sub="Flete y gastos que se suman al costo, moneda y tasa"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Flete
                            </Label>
                            <CurrencyInput
                                value={data.freight_amount}
                                onValueChange={(value) =>
                                    setData('freight_amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.freight_amount ? 'border-bad' : ''}`}
                            />
                            {errors.freight_amount && (
                                <p className="text-sm text-bad">
                                    {errors.freight_amount}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Otros gastos
                            </Label>
                            <CurrencyInput
                                value={data.other_charges}
                                onValueChange={(value) =>
                                    setData('other_charges', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.other_charges ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Aduana, seguro y todo lo que se capitaliza al
                                costo
                            </span>
                            {errors.other_charges && (
                                <p className="text-sm text-bad">
                                    {errors.other_charges}
                                </p>
                            )}
                        </div>

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
                            Bruto
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.gross}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Descuento de líneas
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.discountAmount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Subtotal
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.subtotal}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Impuesto
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.taxAmount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    {totals.withholdingAmount > 0 && (
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Retención
                            </span>
                            <b className="font-bold tabular-nums">
                                <AmountDual
                                    amount={totals.withholdingAmount}
                                    currency={data.currency}
                                    rate={data.exchange_rate || undefined}
                                    className="items-end"
                                />
                            </b>
                        </div>
                    )}
                    <div className="flex items-center justify-between border-t pt-2.5 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Valor aceptado
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.receivedValue}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[15px]">
                        <span className="font-semibold">Valor ingresado</span>
                        <b className="font-extrabold tabular-nums">
                            <AmountDual
                                amount={totals.landedTotal}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                </div>
                <p className="text-[12px] leading-relaxed text-muted-foreground">
                    {landedRatio > 0
                        ? `El flete y los gastos suben el costo de cada unidad un ${landedRatio} %, repartidos por valor de línea.`
                        : 'Al confirmarla, la mercancía aceptada entra al inventario con el flete y los gastos ya dentro del costo.'}
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
                          ? 'Crear entrada'
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
