import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useMeasurementUnitFormContext } from '../contexts/MeasurementUnitFormContext';

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

export function MeasurementUnitForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useMeasurementUnitFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la unidad"
                        sub="Nombre y símbolo con los que se identifica"
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
                                    placeholder="Ej. Kilogramo"
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
                                    htmlFor="abbreviation"
                                    className="text-[13px] font-semibold"
                                >
                                    Símbolo *
                                </Label>
                                <Input
                                    id="abbreviation"
                                    type="text"
                                    value={data.abbreviation}
                                    onChange={(e) =>
                                        setData('abbreviation', e.target.value)
                                    }
                                    placeholder="Ej. kg"
                                    className={`h-[42px] rounded-[10px] ${errors.abbreviation ? 'border-bad' : ''}`}
                                    maxLength={10}
                                    required
                                />
                                {errors.abbreviation && (
                                    <p className="text-sm text-bad">
                                        {errors.abbreviation}
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
                                placeholder="Para qué se usa esta unidad…"
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
                            La unidad no guarda factores de conversión: la
                            equivalencia se define por artículo.
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
                            Unidad
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nueva' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Símbolo
                        </span>
                        <b className="font-bold">{data.abbreviation || '—'}</b>
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
                          ? 'Crear unidad'
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
