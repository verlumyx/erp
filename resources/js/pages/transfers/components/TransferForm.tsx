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
import { useTransferFormContext } from '../contexts/TransferFormContext';
import { REASON_LABELS, type TransferReason } from '../types/Transfer';
import { TransferLinesSection } from './TransferLinesSection';

const REASON_OPTIONS: OptionType[] = (
    Object.keys(REASON_LABELS) as TransferReason[]
).map((value) => ({ value, label: REASON_LABELS[value] }));

export function TransferForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        selectOriginWarehouse,
        selectDestinationWarehouse,
        warehousesExcept,
        options,
    } = useTransferFormContext();

    const toOption = (warehouse: { id: string; name: string }): OptionType => ({
        value: warehouse.id,
        label: warehouse.name,
    });

    /** Una bodega no puede ser dos extremos del mismo viaje. */
    const originOptions = warehousesExcept(data.destination_warehouse_id).map(
        toOption,
    );

    const destinationOptions = warehousesExcept(data.origin_warehouse_id).map(
        toOption,
    );

    const driverOptions: OptionType[] = options.drivers.map((driver) => ({
        value: driver.id,
        label: driver.name,
    }));

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Ruta del traslado"
                sub="De qué bodega sale la mercancía, a cuál llega y por qué"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Bodega de origen *
                        </Label>
                        <Select2
                            inputId="origin_warehouse_id"
                            options={originOptions}
                            value={
                                originOptions.find(
                                    (option) =>
                                        option.value ===
                                        data.origin_warehouse_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                selectOriginWarehouse(option?.value ?? '')
                            }
                            error={!!errors.origin_warehouse_id}
                            size="md"
                            placeholder="De dónde sale la mercancía"
                        />
                        {errors.origin_warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.origin_warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Bodega de destino *
                        </Label>
                        <Select2
                            inputId="destination_warehouse_id"
                            options={destinationOptions}
                            value={
                                destinationOptions.find(
                                    (option) =>
                                        option.value ===
                                        data.destination_warehouse_id,
                                ) ?? null
                            }
                            onChange={(option) =>
                                selectDestinationWarehouse(option?.value ?? '')
                            }
                            error={!!errors.destination_warehouse_id}
                            size="md"
                            placeholder="A dónde llega la mercancía"
                        />
                        {errors.destination_warehouse_id && (
                            <p className="text-sm text-bad">
                                {errors.destination_warehouse_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Motivo *
                        </Label>
                        <Select2
                            inputId="reason"
                            options={REASON_OPTIONS}
                            value={
                                REASON_OPTIONS.find(
                                    (option) => option.value === data.reason,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'reason',
                                    (option?.value ??
                                        'restock') as TransferReason,
                                )
                            }
                            error={!!errors.reason}
                            size="md"
                            placeholder="Por qué se mueve"
                        />
                        {errors.reason && (
                            <p className="text-sm text-bad">{errors.reason}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="transfer_date"
                            className="text-[13px] font-semibold"
                        >
                            Fecha de salida *
                        </Label>
                        <Input
                            id="transfer_date"
                            type="date"
                            value={data.transfer_date}
                            onChange={(e) =>
                                setData('transfer_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.transfer_date ? 'border-bad' : ''}`}
                        />
                        {errors.transfer_date && (
                            <p className="text-sm text-bad">
                                {errors.transfer_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="expected_date"
                            className="text-[13px] font-semibold"
                        >
                            Llegada estimada
                        </Label>
                        <Input
                            id="expected_date"
                            type="date"
                            value={data.expected_date}
                            onChange={(e) =>
                                setData('expected_date', e.target.value)
                            }
                            className={`h-[42px] rounded-[10px] ${errors.expected_date ? 'border-bad' : ''}`}
                        />
                        {errors.expected_date && (
                            <p className="text-sm text-bad">
                                {errors.expected_date}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="reason_detail"
                            className="text-[13px] font-semibold"
                        >
                            Detalle del motivo
                            {data.reason === 'other' ? ' *' : ''}
                        </Label>
                        <Input
                            id="reason_detail"
                            value={data.reason_detail}
                            onChange={(e) =>
                                setData('reason_detail', e.target.value)
                            }
                            maxLength={500}
                            className={`h-[42px] rounded-[10px] ${errors.reason_detail ? 'border-bad' : ''}`}
                            placeholder="Por qué exactamente se mueve esta mercancía"
                        />
                        {errors.reason_detail && (
                            <p className="text-sm text-bad">
                                {errors.reason_detail}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Mercancía"
                sub="Qué se mueve, de qué ubicación a cuál, y con qué lote o serie"
            >
                <TransferLinesSection />
            </FormSection>

            <FormSection
                step={3}
                title="Transporte"
                sub="Quién lleva la mercancía y en qué"
            >
                <FormFieldGrid>
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
                    El traslado no pone precio a nada. Al confirmarlo se genera
                    el despacho que saca la mercancía del origen, y confirmar
                    ese despacho genera la entrada que la mete en el destino, al
                    mismo costo: el inventario cambia de sitio, no de valor.
                </p>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear traslado' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
