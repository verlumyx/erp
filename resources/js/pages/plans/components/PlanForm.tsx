import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { clp } from '@/lib/crm-demo';
import { usePlanFormContext } from '../contexts/PlanFormContext';
import { CAPACITY_LABELS, type PlanCapacity } from '../types/Plan';

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

const CAPACITIES: PlanCapacity[] = ['profile', 'full_account'];

export function PlanForm() {
    const { data, setData, processing, errors, handleSubmit, mode, services } =
        usePlanFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del plan"
                        sub="Plan vendible construido sobre un servicio del catálogo"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="service_id"
                                    className="text-[13px] font-semibold"
                                >
                                    Servicio *
                                </Label>
                                <Select
                                    value={data.service_id || undefined}
                                    onValueChange={(value) =>
                                        setData('service_id', value)
                                    }
                                    disabled={services.length === 0}
                                >
                                    <SelectTrigger
                                        id="service_id"
                                        className={`h-[42px] w-full rounded-[10px] ${errors.service_id ? 'border-bad' : ''}`}
                                    >
                                        <SelectValue
                                            placeholder={
                                                services.length === 0
                                                    ? 'Sin servicios activos'
                                                    : 'Selecciona un servicio'
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {services.map((s) => (
                                            <SelectItem key={s.id} value={s.id}>
                                                {s.name} ({s.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.service_id && (
                                    <p className="text-sm text-bad">
                                        {errors.service_id}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="name"
                                    className="text-[13px] font-semibold"
                                >
                                    Nombre del plan *
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Ej. Netflix Mensual"
                                    className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                    maxLength={150}
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
                                    htmlFor="capacity"
                                    className="text-[13px] font-semibold"
                                >
                                    Capacidad *
                                </Label>
                                <Select
                                    value={data.capacity}
                                    onValueChange={(value) =>
                                        setData(
                                            'capacity',
                                            value as PlanCapacity,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="capacity"
                                        className={`h-[42px] w-full rounded-[10px] ${errors.capacity ? 'border-bad' : ''}`}
                                    >
                                        <SelectValue placeholder="Capacidad" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CAPACITIES.map((c) => (
                                            <SelectItem key={c} value={c}>
                                                {CAPACITY_LABELS[c]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.capacity && (
                                    <p className="text-sm text-bad">
                                        {errors.capacity}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="duration_days"
                                    className="text-[13px] font-semibold"
                                >
                                    Duración (días) *
                                </Label>
                                <NumberInput
                                    id="duration_days"
                                    min={1}
                                    value={data.duration_days}
                                    onValueChange={(value) =>
                                        setData('duration_days', value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.duration_days ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.duration_days && (
                                    <p className="text-sm text-bad">
                                        {errors.duration_days}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="sale_price"
                                    className="text-[13px] font-semibold"
                                >
                                    Precio de venta *
                                </Label>
                                <CurrencyInput
                                    id="sale_price"
                                    min={0}
                                    decimals={2}
                                    value={data.sale_price}
                                    onValueChange={(value) =>
                                        setData('sale_price', value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.sale_price ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.sale_price && (
                                    <p className="text-sm text-bad">
                                        {errors.sale_price}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="roi_target_pct"
                                    className="text-[13px] font-semibold"
                                >
                                    Meta ROI (%) *
                                </Label>
                                <NumberInput
                                    id="roi_target_pct"
                                    min={0}
                                    decimals={2}
                                    value={data.roi_target_pct}
                                    onValueChange={(value) =>
                                        setData('roi_target_pct', value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.roi_target_pct ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.roi_target_pct && (
                                    <p className="text-sm text-bad">
                                        {errors.roi_target_pct}
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
                            Plan
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Capacidad
                        </span>
                        <b className="font-bold">
                            {CAPACITY_LABELS[data.capacity]}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Duración
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.duration_days} días
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Meta ROI
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.roi_target_pct}%
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t border-dashed border-input pt-2.5 text-[15px]">
                        <span className="font-medium text-muted-foreground">
                            Precio de venta
                        </span>
                        <b className="font-bold tabular-nums">
                            {clp(data.sale_price)}
                        </b>
                    </div>
                </div>
                <Button
                    type="submit"
                    disabled={processing || services.length === 0}
                    className="h-10 w-full justify-center rounded-[11px] font-semibold shadow-[0_4px_12px_color-mix(in_srgb,var(--primary)_28%,transparent)]"
                >
                    <Check />
                    {processing
                        ? 'Guardando…'
                        : mode === 'create'
                          ? 'Crear plan'
                          : 'Guardar cambios'}
                </Button>
                {services.length === 0 && (
                    <p className="text-xs text-muted-foreground">
                        Primero crea un servicio activo para poder armar un
                        plan.
                    </p>
                )}
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
