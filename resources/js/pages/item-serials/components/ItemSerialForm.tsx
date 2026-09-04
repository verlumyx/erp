import { Select2Ajax } from '@/components/select2-ajax';
import { FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useItemSerialFormContext } from '../contexts/ItemSerialFormContext';

export function ItemSerialForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        warehouses,
        itemOption,
        lotOption,
        selectLot,
        lotLookupUrl,
    } = useItemSerialFormContext();

    const warehouseOptions: OptionType[] = [
        { value: 'ninguna', label: 'Sin bodega asignada' },
        ...warehouses.map((warehouse) => ({
            value: warehouse.id,
            label: warehouse.name,
        })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la serie"
                sub="La unidad que se controla una por una"
            >
                <div className="flex flex-col gap-4 p-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Artículo
                            </Label>
                            <div className="flex h-[42px] items-center rounded-[10px] border bg-muted px-3 text-sm font-semibold">
                                <span className="truncate">
                                    {itemOption.label}
                                </span>
                            </div>
                            <p className="text-[12px] text-muted-foreground">
                                El artículo no se cambia: la serie se creó al
                                recibir su mercancía.
                            </p>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="serial_number"
                                className="text-[13px] font-semibold"
                            >
                                Número de serie *
                            </Label>
                            <Input
                                id="serial_number"
                                type="text"
                                value={data.serial_number}
                                onChange={(e) =>
                                    setData('serial_number', e.target.value)
                                }
                                placeholder="Ej. SN-000123"
                                className={`h-[42px] rounded-[10px] ${errors.serial_number ? 'border-bad' : ''}`}
                                maxLength={100}
                                required
                            />
                            <p className="text-[12px] text-muted-foreground">
                                Único dentro del artículo.
                            </p>
                            {errors.serial_number && (
                                <p className="text-sm text-bad">
                                    {errors.serial_number}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="lot_id"
                                className="text-[13px] font-semibold"
                            >
                                Lote
                            </Label>
                            <Select2Ajax
                                inputId="lot_id"
                                url={lotLookupUrl}
                                params={{ item_id: data.item_id }}
                                value={lotOption}
                                onChange={selectLot}
                                error={!!errors.lot_id}
                                size="md"
                                isClearable
                                placeholder="Sin lote"
                            />
                            <p className="text-[12px] text-muted-foreground">
                                Solo lotes de este artículo.
                            </p>
                            {errors.lot_id && (
                                <p className="text-sm text-bad">
                                    {errors.lot_id}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="warehouse_id"
                                className="text-[13px] font-semibold"
                            >
                                Bodega
                            </Label>
                            <Select2
                                inputId="warehouse_id"
                                options={warehouseOptions}
                                value={
                                    warehouseOptions.find(
                                        (option) =>
                                            option.value ===
                                            (data.warehouse_id || 'ninguna'),
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'warehouse_id',
                                        !option || option.value === 'ninguna'
                                            ? ''
                                            : option.value,
                                    )
                                }
                                error={!!errors.warehouse_id}
                                size="md"
                                placeholder="Sin bodega asignada"
                            />
                            <p className="text-[12px] text-muted-foreground">
                                Dónde se encuentra hoy la unidad.
                            </p>
                            {errors.warehouse_id && (
                                <p className="text-sm text-bad">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Serie">
                    {data.serial_number || '—'}
                </SummaryRow>
                <SummaryRow
                    label="Artículo"
                    valueClassName="max-w-[60%] truncate font-bold"
                >
                    {itemOption.label}
                </SummaryRow>
                <SummaryRow
                    label="Lote"
                    valueClassName="max-w-[60%] truncate font-bold"
                >
                    {lotOption?.label ?? 'Sin lote'}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel="Guardar cambios"
            />
        </FormLayout>
    );
}
