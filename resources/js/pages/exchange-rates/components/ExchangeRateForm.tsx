import { Check } from 'lucide-react';
import { CurrencySelect } from '@/components/currency-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useExchangeRateFormContext } from '../contexts/ExchangeRateFormContext';
import type { ExchangeRateType } from '../types/ExchangeRate';
import { RATE_DECIMALS, TYPE_LABELS } from '../types/ExchangeRate';

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

export function ExchangeRateForm() {
    const { data, setData, processing, errors, handleSubmit, mode } =
        useExchangeRateFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la tasa"
                        sub="Moneda, fecha de vigencia y valor"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                                    onValueChange={(value) =>
                                        setData('currency', value)
                                    }
                                />
                                {errors.currency && (
                                    <p className="text-sm text-bad">
                                        {errors.currency}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="rate_date"
                                    className="text-[13px] font-semibold"
                                >
                                    Fecha de vigencia *
                                </Label>
                                <Input
                                    id="rate_date"
                                    type="date"
                                    value={data.rate_date}
                                    onChange={(e) =>
                                        setData('rate_date', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.rate_date ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.rate_date && (
                                    <p className="text-sm text-bad">
                                        {errors.rate_date}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="type"
                                    className="text-[13px] font-semibold"
                                >
                                    Tipo *
                                </Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData('type', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="type"
                                        className={`h-[42px] w-full rounded-[10px] ${errors.type ? 'border-bad' : ''}`}
                                    >
                                        <SelectValue placeholder="Tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {(
                                            Object.keys(
                                                TYPE_LABELS,
                                            ) as ExchangeRateType[]
                                        ).map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {TYPE_LABELS[type]}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.type && (
                                    <p className="text-sm text-bad">
                                        {errors.type}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="rate"
                                    className="text-[13px] font-semibold"
                                >
                                    Valor de la tasa *
                                </Label>
                                <CurrencyInput
                                    id="rate"
                                    value={
                                        data.rate === ''
                                            ? null
                                            : Number(data.rate)
                                    }
                                    onValueChange={(value) =>
                                        setData('rate', String(value))
                                    }
                                    decimals={RATE_DECIMALS}
                                    min={0}
                                    placeholder="Ej. 36,50000000"
                                    className={`h-[42px] rounded-[10px] ${errors.rate ? 'border-bad' : ''}`}
                                    required
                                />
                                {errors.rate && (
                                    <p className="text-sm text-bad">
                                        {errors.rate}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="source"
                                    className="text-[13px] font-semibold"
                                >
                                    Fuente (opcional)
                                </Label>
                                <Input
                                    id="source"
                                    type="text"
                                    value={data.source}
                                    onChange={(e) =>
                                        setData('source', e.target.value)
                                    }
                                    placeholder="Ej. Banco Central"
                                    className={`h-[42px] rounded-[10px] ${errors.source ? 'border-bad' : ''}`}
                                    maxLength={150}
                                />
                                {errors.source && (
                                    <p className="text-sm text-bad">
                                        {errors.source}
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
                                placeholder="Notas sobre esta tasa…"
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
                            Solo existe una tasa por moneda, fecha y tipo: si
                            cargas de nuevo la misma combinación se actualiza la
                            existente.
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
                            Moneda
                        </span>
                        <b className="font-bold">{data.currency}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Fecha
                        </span>
                        <b className="font-bold">{data.rate_date || '—'}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tipo
                        </span>
                        <b className="font-bold">
                            {TYPE_LABELS[data.type as ExchangeRateType] ?? '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tasa
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.rate || '—'}
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
                          ? 'Crear tasa'
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
