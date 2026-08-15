import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { COUNTRY_CODES } from '@/lib/phone';
import { useClientFormContext } from '../contexts/ClientFormContext';

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

export function ClientForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useClientFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos del cliente"
                        sub="Información de contacto"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="name"
                                    className="text-[13px] font-semibold"
                                >
                                    Nombre completo *
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    placeholder="Ej. Camila Rojas"
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
                                    htmlFor="phone"
                                    className="text-[13px] font-semibold"
                                >
                                    Teléfono / WhatsApp
                                </Label>
                                <div className="flex gap-2">
                                    <select
                                        value={data.phone_prefix}
                                        onChange={(e) =>
                                            setData(
                                                'phone_prefix',
                                                e.target.value,
                                            )
                                        }
                                        aria-label="Prefijo de país"
                                        className="h-[42px] shrink-0 rounded-[10px] border border-input bg-card px-2 text-sm outline-none focus:border-primary focus:ring-[3px] focus:ring-primary-soft"
                                    >
                                        {COUNTRY_CODES.map((c) => (
                                            <option key={c.name} value={c.dial}>
                                                {c.name} ({c.dial})
                                            </option>
                                        ))}
                                    </select>
                                    <Input
                                        id="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) =>
                                            setData('phone', e.target.value)
                                        }
                                        placeholder="412 1234567"
                                        className={`h-[42px] min-w-0 flex-1 rounded-[10px] ${errors.phone ? 'border-bad' : ''}`}
                                        maxLength={20}
                                    />
                                </div>
                                {errors.phone && (
                                    <p className="text-sm text-bad">
                                        {errors.phone}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5 md:col-span-2">
                                <Label
                                    htmlFor="email"
                                    className="text-[13px] font-semibold"
                                >
                                    Correo
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    placeholder="correo@ejemplo.com"
                                    className={`h-[42px] rounded-[10px] ${errors.email ? 'border-bad' : ''}`}
                                    maxLength={255}
                                />
                                {errors.email && (
                                    <p className="text-sm text-bad">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="notes"
                                className="text-[13px] font-semibold"
                            >
                                Nota (opcional)
                            </Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                placeholder="Preferencias de pago, referido por…"
                                className={`rounded-[10px] ${errors.notes ? 'border-bad' : ''}`}
                                rows={3}
                            />
                            {errors.notes && (
                                <p className="text-sm text-bad">
                                    {errors.notes}
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
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Cliente
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nuevo' : '—')}
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
                          ? 'Crear cliente'
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
