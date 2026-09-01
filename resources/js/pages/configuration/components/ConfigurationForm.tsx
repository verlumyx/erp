import { Transition } from '@headlessui/react';
import { Check, ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import { CurrencySelect, useCurrencies } from '@/components/currency-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { cn } from '@/lib/utils';
import { useConfigurationFormContext } from '../contexts/ConfigurationFormContext';

interface FormSectionHeadProps {
    step: number;
    title: string;
    sub: string;
    open: boolean;
    onToggle: () => void;
}

function FormSectionHead({
    step,
    title,
    sub,
    open,
    onToggle,
}: FormSectionHeadProps) {
    return (
        <button
            type="button"
            onClick={onToggle}
            aria-expanded={open}
            className={cn(
                'flex w-full items-center gap-3 p-5 text-left transition-colors hover:bg-accent/40',
                open && 'border-b',
            )}
        >
            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary">
                {step}
            </span>
            <div className="mr-auto min-w-0">
                <div className="text-base font-bold tracking-tight">
                    {title}
                </div>
                <div className="mt-0.5 text-[13px] text-muted-foreground">
                    {sub}
                </div>
            </div>
            <ChevronDown
                className={cn(
                    'size-5 shrink-0 text-muted-foreground transition-transform',
                    open && 'rotate-180',
                )}
            />
        </button>
    );
}

const RATE_TYPE_OPTIONS: OptionType[] = [
    { value: 'legal', label: 'Legal' },
    { value: 'manual', label: 'Manual' },
];

const YES_NO_OPTIONS: OptionType[] = [
    { value: 'yes', label: 'Sí' },
    { value: 'no', label: 'No' },
];

/** Valor del select de moneda secundaria cuando la empresa no usa una. */
const NO_SECONDARY = '';

/** Qué campo vive en cada sección, para poder abrirla si trae un error. */
const SECTION_FIELDS: Record<number, string[]> = {
    1: ['base_currency', 'secondary_currency'],
    2: ['rate_type', 'allows_rate_override'],
    3: ['amount_decimals', 'price_decimals'],
    4: ['adjustment_approval_threshold'],
};

export function ConfigurationForm() {
    const {
        data,
        setData,
        processing,
        errors,
        recentlySuccessful,
        usesDualCurrency,
        handleSubmit,
    } = useConfigurationFormContext();

    const currencies = useCurrencies();

    /** Las secciones arrancan cerradas: se abren una a una al tocarlas. */
    const [openSections, setOpenSections] = useState<Record<number, boolean>>(
        {},
    );

    const toggleSection = (step: number): void =>
        setOpenSections((previous) => ({
            ...previous,
            [step]: !previous[step],
        }));

    /**
     * Un error de validación en una sección cerrada quedaría invisible, así
     * que la sección se abre sola al recibirlo.
     */
    const sectionsWithErrors = Object.entries(SECTION_FIELDS)
        .filter(([, fields]) =>
            fields.some(
                (field) =>
                    !!(errors as Record<string, string | undefined>)[field],
            ),
        )
        .map(([step]) => Number(step));

    const errorSignature = sectionsWithErrors.join(',');

    useEffect(() => {
        if (errorSignature === '') {
            return;
        }

        setOpenSections((previous) => ({
            ...previous,
            ...Object.fromEntries(
                errorSignature.split(',').map((step) => [step, true]),
            ),
        }));
    }, [errorSignature]);

    const secondaryOptions: OptionType[] = [
        { value: NO_SECONDARY, label: 'Sin segunda moneda' },
        ...currencies.map((currency) => ({
            value: currency.code,
            label: `${currency.name} (${currency.code})`,
        })),
    ];

    const symbolOf = (code: string): string =>
        currencies.find((currency) => currency.code === code)?.symbol ?? code;

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Moneda"
                        sub="En qué moneda lleva sus cifras la empresa y cuál acompaña a cada importe"
                        open={!!openSections[1]}
                        onToggle={() => toggleSection(1)}
                    />

                    {openSections[1] && (
                        <div className="flex flex-col gap-4 p-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="base_currency"
                                        className="text-[13px] font-semibold"
                                    >
                                        Moneda principal *
                                    </Label>
                                    <CurrencySelect
                                        id="base_currency"
                                        value={data.base_currency}
                                        error={errors.base_currency}
                                        onValueChange={(value) =>
                                            setData('base_currency', value)
                                        }
                                    />
                                    <p className="text-[12.5px] text-muted-foreground">
                                        Los precios, pedidos y facturas nacen en
                                        esta moneda.
                                    </p>
                                    {errors.base_currency && (
                                        <p className="text-sm text-bad">
                                            {errors.base_currency}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="secondary_currency"
                                        className="text-[13px] font-semibold"
                                    >
                                        Moneda de presentación
                                    </Label>
                                    <Select2
                                        inputId="secondary_currency"
                                        options={secondaryOptions}
                                        value={
                                            secondaryOptions.find(
                                                (option) =>
                                                    option.value ===
                                                    data.secondary_currency,
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            setData(
                                                'secondary_currency',
                                                option?.value ?? NO_SECONDARY,
                                            )
                                        }
                                        error={!!errors.secondary_currency}
                                        size="md"
                                        placeholder="Moneda"
                                    />
                                    <p className="text-[12.5px] text-muted-foreground">
                                        Se muestra junto a cada importe, al
                                        cambio del día.
                                    </p>
                                    {errors.secondary_currency && (
                                        <p className="text-sm text-bad">
                                            {errors.secondary_currency}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="rounded-[10px] border border-dashed p-4">
                                <div className="text-[12.5px] font-semibold text-muted-foreground">
                                    Así se verán los importes
                                </div>
                                <div className="mt-1.5 flex items-baseline gap-2">
                                    <span className="text-lg font-extrabold tabular-nums">
                                        {symbolOf(data.base_currency)} 1.234,56
                                    </span>
                                    {usesDualCurrency && (
                                        <span className="text-[13.5px] font-medium text-muted-foreground tabular-nums">
                                            {symbolOf(data.secondary_currency)}{' '}
                                            45.061,44
                                        </span>
                                    )}
                                </div>
                                {!usesDualCurrency && (
                                    <p className="mt-1.5 text-[12.5px] text-muted-foreground">
                                        Sin segunda moneda el sistema no
                                        convierte nada: los totales salen tal
                                        cual se capturan.
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Tasa de cambio"
                        sub="Qué tasa valora los documentos y si se puede corregir a mano"
                        open={!!openSections[2]}
                        onToggle={() => toggleSection(2)}
                    />

                    {openSections[2] && (
                        <div className="flex flex-col gap-4 p-5">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="rate_type"
                                        className="text-[13px] font-semibold"
                                    >
                                        Tipo de tasa *
                                    </Label>
                                    <Select2
                                        inputId="rate_type"
                                        options={RATE_TYPE_OPTIONS}
                                        value={
                                            RATE_TYPE_OPTIONS.find(
                                                (option) =>
                                                    option.value ===
                                                    data.rate_type,
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            setData(
                                                'rate_type',
                                                (option?.value ?? 'legal') as
                                                    | 'legal'
                                                    | 'manual',
                                            )
                                        }
                                        error={!!errors.rate_type}
                                        size="md"
                                        placeholder="Tipo"
                                    />
                                    {errors.rate_type && (
                                        <p className="text-sm text-bad">
                                            {errors.rate_type}
                                        </p>
                                    )}
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="allows_rate_override"
                                        className="text-[13px] font-semibold"
                                    >
                                        Tasa editable en documentos *
                                    </Label>
                                    <Select2
                                        inputId="allows_rate_override"
                                        options={YES_NO_OPTIONS}
                                        value={
                                            YES_NO_OPTIONS.find(
                                                (option) =>
                                                    option.value ===
                                                    data.allows_rate_override,
                                            ) ?? null
                                        }
                                        onChange={(option) =>
                                            setData(
                                                'allows_rate_override',
                                                (option?.value ?? 'no') as
                                                    | 'yes'
                                                    | 'no',
                                            )
                                        }
                                        error={!!errors.allows_rate_override}
                                        size="md"
                                        isSearchable={false}
                                    />
                                    <p className="text-[12.5px] text-muted-foreground">
                                        Con «No», los documentos siempre usan la
                                        tasa cargada en el catálogo.
                                    </p>
                                    {errors.allows_rate_override && (
                                        <p className="text-sm text-bad">
                                            {errors.allows_rate_override}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <p className="text-[13px] text-muted-foreground">
                                Cuando no hay tasa del día se usa la última
                                cargada con fecha anterior. Si no existe
                                ninguna, el documento no se puede emitir.
                            </p>
                        </div>
                    )}
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Decimales"
                        sub="Cuántos decimales se guardan en importes y precios"
                        open={!!openSections[3]}
                        onToggle={() => toggleSection(3)}
                    />

                    {openSections[3] && (
                        <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="amount_decimals"
                                    className="text-[13px] font-semibold"
                                >
                                    Importes *
                                </Label>
                                <NumberInput
                                    id="amount_decimals"
                                    value={data.amount_decimals}
                                    onValueChange={(value) =>
                                        setData('amount_decimals', value)
                                    }
                                    min={0}
                                    max={6}
                                    decimals={0}
                                    className={`h-[42px] rounded-[10px] ${errors.amount_decimals ? 'border-bad' : ''}`}
                                />
                                {errors.amount_decimals && (
                                    <p className="text-sm text-bad">
                                        {errors.amount_decimals}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="price_decimals"
                                    className="text-[13px] font-semibold"
                                >
                                    Precios *
                                </Label>
                                <NumberInput
                                    id="price_decimals"
                                    value={data.price_decimals}
                                    onValueChange={(value) =>
                                        setData('price_decimals', value)
                                    }
                                    min={0}
                                    max={8}
                                    decimals={0}
                                    className={`h-[42px] rounded-[10px] ${errors.price_decimals ? 'border-bad' : ''}`}
                                />
                                {errors.price_decimals && (
                                    <p className="text-sm text-bad">
                                        {errors.price_decimals}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Inventario"
                        sub="Cuánto puede mover un ajuste antes de necesitar una segunda firma"
                        open={!!openSections[4]}
                        onToggle={() => toggleSection(4)}
                    />

                    {openSections[4] && (
                        <div className="flex flex-col gap-4 p-5">
                            <div className="flex flex-col gap-1.5 md:max-w-[50%]">
                                <Label
                                    htmlFor="adjustment_approval_threshold"
                                    className="text-[13px] font-semibold"
                                >
                                    Umbral de aprobación de ajustes *
                                </Label>
                                <CurrencyInput
                                    id="adjustment_approval_threshold"
                                    value={data.adjustment_approval_threshold}
                                    onValueChange={(value) =>
                                        setData(
                                            'adjustment_approval_threshold',
                                            value,
                                        )
                                    }
                                    min={0}
                                    decimals={2}
                                    className={`h-[42px] rounded-[10px] ${errors.adjustment_approval_threshold ? 'border-bad' : ''}`}
                                />
                                {errors.adjustment_approval_threshold && (
                                    <p className="text-sm text-bad">
                                        {errors.adjustment_approval_threshold}
                                    </p>
                                )}
                            </div>
                            <p className="text-[13px] text-muted-foreground">
                                Por encima de este impacto —en{' '}
                                {symbolOf(data.base_currency)}, y da igual que
                                sea sobrante o faltante— el ajuste lo tiene que
                                confirmar alguien distinto de quien lo registró.
                                Con cero, cualquier ajuste que mueva valor pide
                                esa segunda firma.
                            </p>
                        </div>
                    )}
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Principal
                        </span>
                        <b className="font-bold">{data.base_currency}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Presentación
                        </span>
                        <b className="font-bold">
                            {usesDualCurrency ? data.secondary_currency : '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tasa
                        </span>
                        <b className="font-bold">
                            {data.rate_type === 'legal' ? 'Legal' : 'Manual'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Editable
                        </span>
                        <b className="font-bold">
                            {data.allows_rate_override === 'yes' ? 'Sí' : 'No'}
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
                <Transition
                    show={recentlySuccessful}
                    enter="transition ease-in-out"
                    enterFrom="opacity-0"
                    leave="transition ease-in-out"
                    leaveTo="opacity-0"
                >
                    <p className="text-good text-center text-[13px] font-semibold">
                        Configuración guardada
                    </p>
                </Transition>
            </Card>
        </form>
    );
}
