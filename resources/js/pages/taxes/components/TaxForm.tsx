import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Textarea } from '@/components/ui/textarea';
import { useTaxFormContext } from '../contexts/TaxFormContext';
import { formatPercentage, PERCENTAGE_DECIMALS } from '../types/Tax';

interface FormSectionHeadProps {
    step: number;
    title: string;
    sub: string;
    children?: React.ReactNode;
}

function FormSectionHead({ step, title, sub, children }: FormSectionHeadProps) {
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
            {children}
        </div>
    );
}

export function TaxForm() {
    const {
        data,
        setData,
        setHasWithholding,
        processing,
        errors,
        handleSubmit,
        mode,
    } = useTaxFormContext();

    const hasWithholding = data.has_withholding === 'yes';

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del impuesto"
                        sub="Nombre y porcentaje que se aplica a la línea"
                    />
                    <div className="flex flex-col gap-4 p-5">
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
                                    placeholder="Ej. IVA 15%"
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
                                    htmlFor="percentage"
                                    className="text-[13px] font-semibold"
                                >
                                    Porcentaje (%) *
                                </Label>
                                <NumberInput
                                    id="percentage"
                                    value={data.percentage}
                                    onValueChange={(value) =>
                                        setData('percentage', value)
                                    }
                                    min={0}
                                    max={100}
                                    decimals={PERCENTAGE_DECIMALS}
                                    placeholder="15"
                                    className={`h-[42px] rounded-[10px] ${errors.percentage ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.percentage && (
                                    <p className="text-sm text-bad">
                                        {errors.percentage}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="description"
                                className="text-[13px] font-semibold"
                            >
                                Descripción (opcional)
                            </Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="Cuándo se aplica este impuesto…"
                                className={`rounded-[10px] ${errors.description ? 'border-bad' : ''}`}
                                rows={3}
                            />
                            {errors.description && (
                                <p className="text-sm text-bad">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                        <p className="text-[13px] text-muted-foreground">
                            Un impuesto con 0% sirve para artículos exentos y
                            permite declararlos correctamente.
                        </p>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Retención"
                        sub="Porcentaje que además se retiene al documento"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="flex items-start gap-3 rounded-[10px] border p-3.5">
                            <Checkbox
                                id="has_withholding"
                                checked={hasWithholding}
                                onCheckedChange={(checked) =>
                                    setHasWithholding(checked ? 'yes' : 'no')
                                }
                                className="mt-0.5"
                            />
                            <div className="flex flex-col gap-0.5">
                                <Label
                                    htmlFor="has_withholding"
                                    className="text-[13px] font-semibold"
                                >
                                    Practica retención
                                </Label>
                                <span className="text-[12px] text-muted-foreground">
                                    Además del impuesto se retiene un
                                    porcentaje.
                                </span>
                            </div>
                        </div>
                        {errors.has_withholding && (
                            <p className="text-sm text-bad">
                                {errors.has_withholding}
                            </p>
                        )}
                        {hasWithholding && (
                            <div className="flex max-w-xs flex-col gap-1.5">
                                <Label
                                    htmlFor="withholding_percentage"
                                    className="text-[13px] font-semibold"
                                >
                                    Porcentaje de retención (%) *
                                </Label>
                                <NumberInput
                                    id="withholding_percentage"
                                    value={data.withholding_percentage}
                                    onValueChange={(value) =>
                                        setData('withholding_percentage', value)
                                    }
                                    min={0}
                                    max={100}
                                    decimals={PERCENTAGE_DECIMALS}
                                    placeholder="75"
                                    className={`h-[42px] rounded-[10px] ${errors.withholding_percentage ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.withholding_percentage && (
                                    <p className="text-sm text-bad">
                                        {errors.withholding_percentage}
                                    </p>
                                )}
                            </div>
                        )}
                        <p className="text-[13px] text-muted-foreground">
                            Sin retención el porcentaje se guarda en 0.
                        </p>
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
                            Impuesto
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Porcentaje
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatPercentage(data.percentage || 0)}%
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Retención
                        </span>
                        <b className="font-bold tabular-nums">
                            {hasWithholding
                                ? `${formatPercentage(data.withholding_percentage || 0)}%`
                                : 'No aplica'}
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
                          ? 'Crear impuesto'
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
