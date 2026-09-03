import { Transition } from '@headlessui/react';
import { Check, ImageOff, KeyRound, Upload } from 'lucide-react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useStoreSettingsFormContext } from '../contexts/StoreSettingsFormContext';
import type { SelectOption, YesNo } from '../types/Store';

const YES_NO_OPTIONS: OptionType[] = [
    { value: 'yes', label: 'Sí' },
    { value: 'no', label: 'No' },
];

const NONE = '';

interface FieldProps {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}

function Field({ id, label, hint, error, children }: FieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id} className="text-[13px] font-semibold">
                {label}
            </Label>
            {children}
            {hint && (
                <p className="text-[12.5px] text-muted-foreground">{hint}</p>
            )}
            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}

function withNone(options: SelectOption[], label: string): OptionType[] {
    return [{ value: NONE, label }, ...options];
}

function SectionTitle({ title, sub }: { title: string; sub: string }) {
    return (
        <div className="border-b p-5">
            <div className="text-base font-bold tracking-tight">{title}</div>
            <div className="mt-0.5 text-[13px] text-muted-foreground">
                {sub}
            </div>
        </div>
    );
}

export function StoreSettingsForm() {
    const {
        data,
        setData,
        processing,
        errors,
        recentlySuccessful,
        handleSubmit,
        generateKey,
        selectLogo,
        removeLogo,
        currentLogoUrl,
        settings,
        options,
    } = useStoreSettingsFormContext();

    const fileInput = useRef<HTMLInputElement>(null);

    const priceListOptions = withNone(
        options.price_lists,
        'Sin lista (no se muestran precios)',
    );
    const warehouseOptions = withNone(options.warehouses, 'Todas las bodegas');
    const clientTypeOptions = withNone(
        options.client_types,
        'Sin tipo (convertir no podrá crear clientes)',
    );

    const yesNo = (field: keyof typeof data, value: YesNo) =>
        setData(field, value as never);

    const yesNoSelect = (
        id:
            | 'is_enabled'
            | 'shows_stock'
            | 'allows_orders'
            | 'shows_secondary_currency',
        label: string,
        hint: string,
    ) => (
        <Field id={id} label={label} hint={hint} error={errors[id]}>
            <Select2
                inputId={id}
                options={YES_NO_OPTIONS}
                value={
                    YES_NO_OPTIONS.find(
                        (option) => option.value === data[id],
                    ) ?? null
                }
                onChange={(option) =>
                    yesNo(id, (option?.value ?? 'no') as YesNo)
                }
                error={!!errors[id]}
                size="md"
                isSearchable={false}
            />
        </Field>
    );

    const isValidHex = /^#[0-9a-fA-F]{6}$/.test(data.brand_color);

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <SectionTitle
                        title="Tienda"
                        sub="Cómo se presenta la tienda y qué muestra de cada artículo"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <Field
                                id="store_name"
                                label="Nombre de la tienda *"
                                error={errors.store_name}
                            >
                                <Input
                                    id="store_name"
                                    value={data.store_name}
                                    maxLength={150}
                                    onChange={(e) =>
                                        setData('store_name', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.store_name ? 'border-bad' : ''}`}
                                />
                            </Field>
                            <Field
                                id="brand_color"
                                label="Color de acento *"
                                hint="Único color que cambia por empresa: botones, enlaces y chips activos."
                                error={errors.brand_color}
                            >
                                <div className="flex items-center gap-2">
                                    <input
                                        type="color"
                                        aria-label="Elegir color"
                                        value={
                                            isValidHex
                                                ? data.brand_color
                                                : '#111827'
                                        }
                                        onChange={(e) =>
                                            setData(
                                                'brand_color',
                                                e.target.value.toLowerCase(),
                                            )
                                        }
                                        className="size-[42px] shrink-0 cursor-pointer rounded-[10px] border bg-card p-1"
                                    />
                                    <Input
                                        id="brand_color"
                                        value={data.brand_color}
                                        maxLength={7}
                                        placeholder="#111827"
                                        onChange={(e) =>
                                            setData(
                                                'brand_color',
                                                e.target.value,
                                            )
                                        }
                                        className={`h-[42px] rounded-[10px] font-mono ${errors.brand_color ? 'border-bad' : ''}`}
                                    />
                                </div>
                            </Field>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Logo
                            </Label>
                            <div className="flex flex-wrap items-center gap-4">
                                <div className="grid size-20 place-items-center overflow-hidden rounded-[12px] border bg-muted">
                                    {currentLogoUrl ? (
                                        <img
                                            src={currentLogoUrl}
                                            alt="Logo de la tienda"
                                            className="max-h-full max-w-full object-contain"
                                        />
                                    ) : (
                                        <ImageOff className="size-6 text-muted-foreground" />
                                    )}
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <input
                                        ref={fileInput}
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        className="hidden"
                                        onChange={(e) =>
                                            selectLogo(
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="h-10 rounded-[10px]"
                                        onClick={() =>
                                            fileInput.current?.click()
                                        }
                                    >
                                        <Upload />
                                        {currentLogoUrl
                                            ? 'Cambiar logo'
                                            : 'Subir logo'}
                                    </Button>
                                    {currentLogoUrl && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            className="h-10 rounded-[10px] text-bad"
                                            onClick={removeLogo}
                                        >
                                            Quitar
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <p className="text-[12.5px] text-muted-foreground">
                                PNG, JPG o WebP. Se muestra en la cabecera y en
                                la pestaña del navegador.
                            </p>
                            {errors.logo && (
                                <p className="text-sm text-bad">
                                    {errors.logo}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <Field
                                id="contact_phone"
                                label="Teléfono de contacto"
                                hint="Formato internacional para el enlace de WhatsApp (+58…)."
                                error={errors.contact_phone}
                            >
                                <Input
                                    id="contact_phone"
                                    value={data.contact_phone}
                                    maxLength={30}
                                    placeholder="+584141234567"
                                    onChange={(e) =>
                                        setData('contact_phone', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.contact_phone ? 'border-bad' : ''}`}
                                />
                            </Field>
                            <Field
                                id="contact_email"
                                label="Correo de contacto"
                                error={errors.contact_email}
                            >
                                <Input
                                    id="contact_email"
                                    type="email"
                                    value={data.contact_email}
                                    maxLength={150}
                                    onChange={(e) =>
                                        setData('contact_email', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.contact_email ? 'border-bad' : ''}`}
                                />
                            </Field>
                            <Field
                                id="store_url"
                                label="URL pública de la tienda"
                                hint="Arma el enlace de las invitaciones. Sin ella no se pueden invitar clientes."
                                error={errors.store_url}
                            >
                                <Input
                                    id="store_url"
                                    type="url"
                                    value={data.store_url}
                                    maxLength={255}
                                    placeholder="https://tienda.miempresa.com"
                                    onChange={(e) =>
                                        setData('store_url', e.target.value)
                                    }
                                    className={`h-[42px] rounded-[10px] ${errors.store_url ? 'border-bad' : ''}`}
                                />
                            </Field>
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <SectionTitle
                        title="Catálogo"
                        sub="De dónde salen los precios y la disponibilidad que ve el comprador"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                        <Field
                            id="price_list_id"
                            label="Lista de precio"
                            hint="Sin lista, la tienda muestra «Consultar» en todos los artículos."
                            error={errors.price_list_id}
                        >
                            <Select2
                                inputId="price_list_id"
                                options={priceListOptions}
                                value={
                                    priceListOptions.find(
                                        (option) =>
                                            option.value === data.price_list_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'price_list_id',
                                        option?.value ?? NONE,
                                    )
                                }
                                error={!!errors.price_list_id}
                                size="md"
                            />
                        </Field>
                        <Field
                            id="warehouse_id"
                            label="Bodega"
                            hint="Contra la que se calcula la disponibilidad. Vacía = suma de todas."
                            error={errors.warehouse_id}
                        >
                            <Select2
                                inputId="warehouse_id"
                                options={warehouseOptions}
                                value={
                                    warehouseOptions.find(
                                        (option) =>
                                            option.value === data.warehouse_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'warehouse_id',
                                        option?.value ?? NONE,
                                    )
                                }
                                error={!!errors.warehouse_id}
                                size="md"
                            />
                        </Field>
                        {yesNoSelect(
                            'shows_stock',
                            'Mostrar cantidad disponible',
                            'Con «No» la tienda solo dice «Disponible» o «Agotado».',
                        )}
                        {yesNoSelect(
                            'shows_secondary_currency',
                            'Mostrar precio en moneda secundaria',
                            'Con la tasa del día de la empresa.',
                        )}
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <SectionTitle
                        title="Pedidos"
                        sub="Si la tienda acepta pedidos y con qué tipo se crean los clientes nuevos"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                        {yesNoSelect(
                            'allows_orders',
                            'Permitir pedidos',
                            'Habilita el carrito. Con «No» el detalle solo ofrece WhatsApp.',
                        )}
                        <Field
                            id="default_client_type_id"
                            label="Tipo de cliente por defecto"
                            hint="Con el que se crea el cliente al convertir el primer pedido de un comprador."
                            error={errors.default_client_type_id}
                        >
                            <Select2
                                inputId="default_client_type_id"
                                options={clientTypeOptions}
                                value={
                                    clientTypeOptions.find(
                                        (option) =>
                                            option.value ===
                                            data.default_client_type_id,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'default_client_type_id',
                                        option?.value ?? NONE,
                                    )
                                }
                                error={!!errors.default_client_type_id}
                                size="md"
                            />
                        </Field>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <SectionTitle
                        title="Acceso"
                        sub="La llave con la que la tienda lee el ERP"
                    />
                    <div className="flex flex-col gap-4 p-5">
                        {yesNoSelect(
                            'is_enabled',
                            'Tienda habilitada',
                            'Interruptor general: con «No» la API pública responde 403 a todo.',
                        )}
                        <div className="flex flex-wrap items-center justify-between gap-4 rounded-[12px] border border-dashed p-4">
                            <div className="flex flex-col gap-1">
                                <span className="flex items-center gap-2 text-[13.5px] font-bold">
                                    <KeyRound className="size-4 text-muted-foreground" />
                                    {settings.has_api_key
                                        ? 'Llave generada'
                                        : 'Sin llave: la tienda no tiene acceso'}
                                </span>
                                <span className="text-[12.5px] text-muted-foreground">
                                    {settings.has_api_key
                                        ? settings.api_key_last_used_at
                                            ? `Último uso: ${settings.api_key_last_used_at}`
                                            : 'Todavía no se ha usado'
                                        : 'Genera una llave y cópiala en la tienda.'}
                                </span>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 rounded-[10px]"
                                onClick={generateKey}
                                disabled={processing}
                            >
                                <KeyRound />
                                {settings.has_api_key
                                    ? 'Generar otra llave'
                                    : 'Generar llave'}
                            </Button>
                        </div>
                        {settings.has_api_key && (
                            <p className="text-[12.5px] text-muted-foreground">
                                Generar otra llave invalida la anterior de
                                inmediato.
                            </p>
                        )}
                    </div>
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    {[
                        ['Habilitada', data.is_enabled === 'yes' ? 'Sí' : 'No'],
                        ['Pedidos', data.allows_orders === 'yes' ? 'Sí' : 'No'],
                        [
                            'Lista',
                            options.price_lists.find(
                                (o) => o.value === data.price_list_id,
                            )?.label ?? '—',
                        ],
                        [
                            'Bodega',
                            options.warehouses.find(
                                (o) => o.value === data.warehouse_id,
                            )?.label ?? 'Todas',
                        ],
                    ].map(([label, value]) => (
                        <div
                            key={label}
                            className="flex items-center justify-between gap-3 text-[13.5px]"
                        >
                            <span className="font-medium text-muted-foreground">
                                {label}
                            </span>
                            <b className="truncate font-bold">{value}</b>
                        </div>
                    ))}
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Color
                        </span>
                        <span className="flex items-center gap-2 font-bold">
                            <span
                                className="size-4 rounded-full border"
                                style={{
                                    backgroundColor: isValidHex
                                        ? data.brand_color
                                        : undefined,
                                }}
                            />
                            {data.brand_color}
                        </span>
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
                        Ajustes guardados
                    </p>
                </Transition>
            </Card>
        </form>
    );
}
