import { Check } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useItemSerialFormContext } from '../contexts/ItemSerialFormContext';

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
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la serie"
                        sub="La unidad que se controla una por una"
                    />
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
                                    El artículo no se cambia: la serie se creó
                                    al recibir su mercancía.
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
                                                (data.warehouse_id ||
                                                    'ninguna'),
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        setData(
                                            'warehouse_id',
                                            !option ||
                                                option.value === 'ninguna'
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
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Serie
                        </span>
                        <b className="font-bold">{data.serial_number || '—'}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Artículo
                        </span>
                        <b className="max-w-[60%] truncate font-bold">
                            {itemOption.label}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Lote
                        </span>
                        <b className="max-w-[60%] truncate font-bold">
                            {lotOption?.label ?? 'Sin lote'}
                        </b>
                    </div>
                </div>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing ? 'Guardando…' : 'Guardar cambios'}
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
