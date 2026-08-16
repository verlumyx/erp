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
import { Textarea } from '@/components/ui/textarea';
import { COUNTRY_CODES } from '@/lib/phone';
import { useClientFormContext } from '../contexts/ClientFormContext';
import {
    DOCUMENT_TYPE_LABELS,
    formatDocument,
    type DocumentType,
    type YesNo,
} from '../types/Client';
import { ClientAddressesSection } from './ClientAddressesSection';
import { ClientContactsSection } from './ClientContactsSection';

const NO_CLIENT_TYPE = 'none';
const NO_PRICE_LIST = 'none';
const NO_SALESPERSON = 'none';

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

interface TextFieldProps {
    id: string;
    label: string;
    value: string;
    error?: string;
    placeholder?: string;
    maxLength?: number;
    onChange: (value: string) => void;
}

function TextField({
    id,
    label,
    value,
    error,
    placeholder,
    maxLength,
    onChange,
}: TextFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id} className="text-[13px] font-semibold">
                {label}
            </Label>
            <Input
                id={id}
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                maxLength={maxLength}
                className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''}`}
            />
            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}

export function ClientForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
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
                        title="Identificación"
                        sub="Razón social y RIF con que se factura al cliente"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <TextField
                            id="name"
                            label="Nombre *"
                            value={data.name}
                            error={errors.name}
                            placeholder="Ej. Camila Rojas"
                            maxLength={150}
                            onChange={(value) => setData('name', value)}
                        />

                        <TextField
                            id="legal_name"
                            label="Razón social"
                            value={data.legal_name}
                            error={errors.legal_name}
                            placeholder="Si difiere del comercial"
                            maxLength={200}
                            onChange={(value) => setData('legal_name', value)}
                        />

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Tipo de documento *
                            </Label>
                            <Select
                                value={data.document_type}
                                onValueChange={(value) =>
                                    setData(
                                        'document_type',
                                        value as DocumentType,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(DOCUMENT_TYPE_LABELS).map(
                                        ([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.document_type && (
                                <p className="text-sm text-bad">
                                    {errors.document_type}
                                </p>
                            )}
                        </div>

                        <TextField
                            id="document_number"
                            label="Número de RIF / cédula *"
                            value={data.document_number}
                            error={errors.document_number}
                            placeholder="Solo dígitos, sin letra ni guiones"
                            maxLength={15}
                            onChange={(value) =>
                                setData(
                                    'document_number',
                                    value.replace(/\D/g, ''),
                                )
                            }
                        />

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Tipo de cliente
                            </Label>
                            <Select
                                value={data.client_type_id || NO_CLIENT_TYPE}
                                onValueChange={(value) =>
                                    setData(
                                        'client_type_id',
                                        value === NO_CLIENT_TYPE ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CLIENT_TYPE}>
                                        Sin tipo
                                    </SelectItem>
                                    {options.clientTypes.map((clientType) => (
                                        <SelectItem
                                            key={clientType.id}
                                            value={clientType.id}
                                        >
                                            {clientType.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.client_type_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_type_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Datos de contacto"
                        sub="Correo, teléfonos y dirección fiscal del cliente"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <TextField
                            id="email"
                            label="Correo"
                            value={data.email}
                            error={errors.email}
                            placeholder="correo@ejemplo.com"
                            maxLength={255}
                            onChange={(value) => setData('email', value)}
                        />

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
                                        setData('phone_prefix', e.target.value)
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

                        <TextField
                            id="mobile"
                            label="Celular"
                            value={data.mobile}
                            error={errors.mobile}
                            maxLength={30}
                            onChange={(value) => setData('mobile', value)}
                        />

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="address"
                                className="text-[13px] font-semibold"
                            >
                                Dirección fiscal
                            </Label>
                            <Textarea
                                id="address"
                                value={data.address}
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                className="rounded-[10px]"
                                rows={2}
                                maxLength={500}
                            />
                            {errors.address && (
                                <p className="text-sm text-bad">
                                    {errors.address}
                                </p>
                            )}
                        </div>

                        <TextField
                            id="city"
                            label="Ciudad"
                            value={data.city}
                            error={errors.city}
                            maxLength={100}
                            onChange={(value) => setData('city', value)}
                        />
                        <TextField
                            id="state"
                            label="Estado"
                            value={data.state}
                            error={errors.state}
                            maxLength={100}
                            onChange={(value) => setData('state', value)}
                        />
                        <TextField
                            id="country"
                            label="País"
                            value={data.country}
                            error={errors.country}
                            maxLength={100}
                            onChange={(value) => setData('country', value)}
                        />

                        {/* Georreferencia del cliente: la usa el armado de rutas. */}
                        <TextField
                            id="latitude"
                            label="Latitud"
                            value={data.latitude}
                            error={errors.latitude}
                            placeholder="10.4806"
                            onChange={(value) => setData('latitude', value)}
                        />
                        <TextField
                            id="longitude"
                            label="Longitud"
                            value={data.longitude}
                            error={errors.longitude}
                            placeholder="-66.9036"
                            onChange={(value) => setData('longitude', value)}
                        />
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Contactos"
                        sub="Personas con quienes se gestionan las ventas"
                    />
                    <ClientContactsSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Direcciones"
                        sub="Dónde se factura y a qué sucursales se despacha"
                    />
                    <ClientAddressesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={5}
                        title="Condiciones comerciales"
                        sub="Lista de precio, crédito, descuento y vendedor asignado"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Lista de precio
                            </Label>
                            <Select
                                value={data.price_list_id || NO_PRICE_LIST}
                                onValueChange={(value) =>
                                    setData(
                                        'price_list_id',
                                        value === NO_PRICE_LIST ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Lista por defecto" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_PRICE_LIST}>
                                        Lista por defecto de la empresa
                                    </SelectItem>
                                    {options.priceLists.map((priceList) => (
                                        <SelectItem
                                            key={priceList.id}
                                            value={priceList.id}
                                        >
                                            {priceList.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.price_list_id && (
                                <p className="text-sm text-bad">
                                    {errors.price_list_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Vendedor asignado
                            </Label>
                            <Select
                                value={data.salesperson_id || NO_SALESPERSON}
                                onValueChange={(value) =>
                                    setData(
                                        'salesperson_id',
                                        value === NO_SALESPERSON ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin vendedor" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_SALESPERSON}>
                                        Sin vendedor
                                    </SelectItem>
                                    {options.salespeople.map((salesperson) => (
                                        <SelectItem
                                            key={salesperson.id}
                                            value={salesperson.id}
                                        >
                                            {salesperson.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.salesperson_id && (
                                <p className="text-sm text-bad">
                                    {errors.salesperson_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="payment_term_days"
                                className="text-[13px] font-semibold"
                            >
                                Días de crédito
                            </Label>
                            <NumberInput
                                id="payment_term_days"
                                value={data.payment_term_days}
                                onValueChange={(value) =>
                                    setData('payment_term_days', value)
                                }
                                min={0}
                                decimals={0}
                                className={`h-[42px] rounded-[10px] ${errors.payment_term_days ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                0 = contado
                            </span>
                            {errors.payment_term_days && (
                                <p className="text-sm text-bad">
                                    {errors.payment_term_days}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="credit_limit"
                                className="text-[13px] font-semibold"
                            >
                                Límite de crédito
                            </Label>
                            <CurrencyInput
                                id="credit_limit"
                                value={data.credit_limit}
                                onValueChange={(value) =>
                                    setData('credit_limit', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.credit_limit ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                0 = sin crédito
                            </span>
                            {errors.credit_limit && (
                                <p className="text-sm text-bad">
                                    {errors.credit_limit}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="discount_percent"
                                className="text-[13px] font-semibold"
                            >
                                Descuento fijo (%)
                            </Label>
                            <NumberInput
                                id="discount_percent"
                                value={data.discount_percent}
                                onValueChange={(value) =>
                                    setData('discount_percent', value)
                                }
                                min={0}
                                max={100}
                                decimals={4}
                                className={`h-[42px] rounded-[10px] ${errors.discount_percent ? 'border-bad' : ''}`}
                            />
                            {errors.discount_percent && (
                                <p className="text-sm text-bad">
                                    {errors.discount_percent}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Crédito bloqueado
                            </Label>
                            <Select
                                value={data.credit_blocked}
                                onValueChange={(value) =>
                                    setData('credit_blocked', value as YesNo)
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="no">No</SelectItem>
                                    <SelectItem value="yes">Sí</SelectItem>
                                </SelectContent>
                            </Select>
                            <span className="text-[12px] text-muted-foreground">
                                Bloquea nuevas ventas a crédito
                            </span>
                            {errors.credit_blocked && (
                                <p className="text-sm text-bad">
                                    {errors.credit_blocked}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="notes"
                                className="text-[13px] font-semibold"
                            >
                                Notas
                            </Label>
                            <Textarea
                                id="notes"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                placeholder="Preferencias de pago, referido por…"
                                className="rounded-[10px]"
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
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            RIF
                        </span>
                        <b className="font-bold tabular-nums">
                            {data.document_number
                                ? formatDocument(
                                      data.document_type,
                                      data.document_number,
                                  )
                                : '—'}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Contactos
                        </span>
                        <b className="font-bold">{data.contacts.length}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Direcciones
                        </span>
                        <b className="font-bold">{data.addresses.length}</b>
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
