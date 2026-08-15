import { Check, Minus, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { useServiceFormContext } from '../contexts/ServiceFormContext';
import { ServiceLogo } from './ServiceLogo';

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

export function ServiceForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useServiceFormContext();

    const setMaxProfiles = (value: number) => {
        setData('max_profiles', Math.max(1, value || 1));
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del servicio"
                        sub="Plataforma o servicio de streaming del catálogo"
                    />
                    <div className="flex flex-col gap-4 p-5">
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
                                placeholder="Ej. Netflix"
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
                                htmlFor="logo_url"
                                className="text-[13px] font-semibold"
                            >
                                URL del logo
                            </Label>
                            <Input
                                id="logo_url"
                                type="text"
                                value={data.logo_url}
                                onChange={(e) =>
                                    setData('logo_url', e.target.value)
                                }
                                placeholder="https://…/logo.png"
                                className={`h-[42px] rounded-[10px] ${errors.logo_url ? 'border-bad' : ''}`}
                                maxLength={255}
                            />
                            {errors.logo_url && (
                                <p className="text-sm text-bad">
                                    {errors.logo_url}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="max_profiles"
                                className="text-[13px] font-semibold"
                            >
                                Máximo de perfiles *
                            </Label>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-[42px] shrink-0 rounded-[10px] bg-card"
                                    onClick={() =>
                                        setMaxProfiles(data.max_profiles - 1)
                                    }
                                    disabled={data.max_profiles <= 1}
                                    aria-label="Disminuir"
                                >
                                    <Minus className="size-4" />
                                </Button>
                                <NumberInput
                                    id="max_profiles"
                                    min={1}
                                    value={data.max_profiles}
                                    onValueChange={setMaxProfiles}
                                    className={`h-[42px] w-24 rounded-[10px] text-center ${errors.max_profiles ? 'border-bad' : ''}`}
                                    required
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-[42px] shrink-0 rounded-[10px] bg-card"
                                    onClick={() =>
                                        setMaxProfiles(data.max_profiles + 1)
                                    }
                                    aria-label="Aumentar"
                                >
                                    <Plus className="size-4" />
                                </Button>
                                <span className="text-[13px] text-muted-foreground">
                                    perfiles por cuenta
                                </span>
                            </div>
                            {errors.max_profiles && (
                                <p className="text-sm text-bad">
                                    {errors.max_profiles}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex items-center gap-3">
                    <ServiceLogo
                        name={data.name}
                        logoUrl={data.logo_url || null}
                        className="size-12 rounded-[12px]"
                    />
                    <div className="flex min-w-0 flex-col">
                        <b className="truncate font-bold">
                            {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                        </b>
                        <span className="text-[12.5px] text-muted-foreground">
                            {data.max_profiles} perfil
                            {data.max_profiles !== 1 ? 'es' : ''} máx.
                        </span>
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
                          ? 'Crear servicio'
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
