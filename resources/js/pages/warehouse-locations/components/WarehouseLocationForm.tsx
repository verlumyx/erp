import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useWarehouseLocationFormContext } from '../contexts/WarehouseLocationFormContext';
import {
    LOCATION_TYPE_LABELS,
    type WarehouseLocationType,
} from '../types/WarehouseLocation';

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

const TYPE_OPTIONS: OptionType[] = Object.entries(LOCATION_TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function WarehouseLocationForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        warehouses,
        parents,
    } = useWarehouseLocationFormContext();

    const parentOptions = parents.filter(
        (parent) =>
            parent.warehouse_id === data.warehouse_id && parent.id !== data.id,
    );

    const warehouseOptions: OptionType[] = warehouses.map((warehouse) => ({
        value: warehouse.id,
        label: warehouse.name,
    }));

    const parentSelectOptions: OptionType[] = [
        { value: 'ninguna', label: 'Sin padre (raíz)' },
        ...parentOptions.map((parent) => ({
            value: parent.id,
            label: `${parent.location_code} · ${parent.name}`,
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
                        title="Datos de la ubicación"
                        sub="Dónde vive el saldo dentro de la bodega"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="warehouse_id"
                                    className="text-[13px] font-semibold"
                                >
                                    Bodega *
                                </Label>
                                <Select2
                                    inputId="warehouse_id"
                                    options={warehouseOptions}
                                    value={
                                        warehouseOptions.find(
                                            (option) =>
                                                option.value ===
                                                data.warehouse_id,
                                        ) ?? null
                                    }
                                    onChange={(option) => {
                                        setData(
                                            'warehouse_id',
                                            option?.value ?? '',
                                        );
                                        setData('parent_id', '');
                                    }}
                                    isDisabled={mode === 'edit'}
                                    error={!!errors.warehouse_id}
                                    size="md"
                                    placeholder="Selecciona una bodega"
                                />
                                <p className="text-[12px] text-muted-foreground">
                                    Solo aparecen las bodegas que gestionan
                                    ubicaciones.
                                </p>
                                {errors.warehouse_id && (
                                    <p className="text-sm text-bad">
                                        {errors.warehouse_id}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="parent_id"
                                    className="text-[13px] font-semibold"
                                >
                                    Ubicación padre
                                </Label>
                                <Select2
                                    inputId="parent_id"
                                    options={parentSelectOptions}
                                    value={
                                        parentSelectOptions.find(
                                            (option) =>
                                                option.value ===
                                                (data.parent_id || 'ninguna'),
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        setData(
                                            'parent_id',
                                            !option ||
                                                option.value === 'ninguna'
                                                ? ''
                                                : option.value,
                                        )
                                    }
                                    isDisabled={data.warehouse_id === ''}
                                    error={!!errors.parent_id}
                                    size="md"
                                    placeholder="Sin padre (raíz)"
                                />
                                {errors.parent_id && (
                                    <p className="text-sm text-bad">
                                        {errors.parent_id}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="name"
                                    className="text-[13px] font-semibold"
                                >
                                    Nombre *
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Ej. Estante A1"
                                    className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                    maxLength={100}
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-bad">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="location_code"
                                    className="text-[13px] font-semibold"
                                >
                                    Código físico *
                                </Label>
                                <Input
                                    id="location_code"
                                    type="text"
                                    value={data.location_code}
                                    onChange={(e) =>
                                        setData(
                                            'location_code',
                                            e.target.value.toUpperCase(),
                                        )
                                    }
                                    placeholder="Ej. A-01-03"
                                    className={`h-[42px] rounded-[10px] ${errors.location_code ? 'border-bad' : ''}`}
                                    maxLength={50}
                                    required
                                />
                                <p className="text-[12px] text-muted-foreground">
                                    El código rotulado en el estante. Único
                                    dentro de la bodega.
                                </p>
                                {errors.location_code && (
                                    <p className="text-sm text-bad">
                                        {errors.location_code}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="type"
                                    className="text-[13px] font-semibold"
                                >
                                    Tipo *
                                </Label>
                                <Select2
                                    inputId="type"
                                    options={TYPE_OPTIONS}
                                    value={
                                        TYPE_OPTIONS.find(
                                            (option) =>
                                                option.value === data.type,
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        setData(
                                            'type',
                                            (option?.value ??
                                                '') as WarehouseLocationType,
                                        )
                                    }
                                    error={!!errors.type}
                                    size="md"
                                    placeholder="Tipo de ubicación"
                                />
                                {errors.type && (
                                    <p className="text-sm text-bad">
                                        {errors.type}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="capacity"
                                    className="text-[13px] font-semibold"
                                >
                                    Capacidad
                                </Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    min={0}
                                    step="0.0001"
                                    value={data.capacity}
                                    onChange={(e) =>
                                        setData(
                                            'capacity',
                                            Number(e.target.value) || 0,
                                        )
                                    }
                                    placeholder="0"
                                    className={`h-[42px] rounded-[10px] ${errors.capacity ? 'border-bad' : ''}`}
                                />
                                {errors.capacity && (
                                    <p className="text-sm text-bad">
                                        {errors.capacity}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-start gap-3 rounded-[10px] border p-3.5">
                            <Checkbox
                                id="is_default"
                                checked={data.is_default === 'yes'}
                                onCheckedChange={(checked) =>
                                    setData(
                                        'is_default',
                                        checked ? 'yes' : 'no',
                                    )
                                }
                                className="mt-0.5"
                            />
                            <div className="flex flex-col gap-0.5">
                                <Label
                                    htmlFor="is_default"
                                    className="text-[13px] font-semibold"
                                >
                                    Ubicación por defecto
                                </Label>
                                <span className="text-[12px] text-muted-foreground">
                                    Se sugiere al mover saldo. Solo una por
                                    bodega.
                                </span>
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
                            Ubicación
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nueva' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Código
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.location_code || '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tipo
                        </span>
                        <b className="font-bold">
                            {LOCATION_TYPE_LABELS[data.type]}
                        </b>
                    </div>
                </div>
                <Button
                    type="submit"
                    disabled={processing}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear ubicación'
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
