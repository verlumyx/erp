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
import { useSupplierFormContext } from '../contexts/SupplierFormContext';
import {
    DOCUMENT_TYPE_LABELS,
    formatDocument,
    type DocumentType,
} from '../types/Supplier';
import { SupplierAddressesSection } from './SupplierAddressesSection';
import { SupplierContactsSection } from './SupplierContactsSection';

const NO_SUPPLIER_TYPE = 'none';

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

export function SupplierForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
        useSupplierFormContext();

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
                        sub="Razón social y RIF con que se registra al proveedor"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <TextField
                            id="name"
                            label="Nombre *"
                            value={data.name}
                            error={errors.name}
                            placeholder="Ej. Distribuidora Andina C.A."
                            maxLength={200}
                            onChange={(value) => setData('name', value)}
                        />

                        <TextField
                            id="legal_name"
                            label="Nombre legal"
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
                                Tipo de proveedor
                            </Label>
                            <Select
                                value={
                                    data.supplier_type_id || NO_SUPPLIER_TYPE
                                }
                                onValueChange={(value) =>
                                    setData(
                                        'supplier_type_id',
                                        value === NO_SUPPLIER_TYPE ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin tipo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_SUPPLIER_TYPE}>
                                        Sin tipo
                                    </SelectItem>
                                    {options.supplierTypes.map(
                                        (supplierType) => (
                                            <SelectItem
                                                key={supplierType.id}
                                                value={supplierType.id}
                                            >
                                                {supplierType.name}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.supplier_type_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_type_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Datos de contacto"
                        sub="Correo, teléfonos y dirección fiscal del proveedor"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <TextField
                            id="email"
                            label="Correo principal"
                            value={data.email}
                            error={errors.email}
                            placeholder="compras@proveedor.com"
                            maxLength={255}
                            onChange={(value) => setData('email', value)}
                        />
                        <TextField
                            id="website"
                            label="Sitio web"
                            value={data.website}
                            error={errors.website}
                            placeholder="https://proveedor.com"
                            maxLength={255}
                            onChange={(value) => setData('website', value)}
                        />
                        <TextField
                            id="phone"
                            label="Teléfono"
                            value={data.phone}
                            error={errors.phone}
                            maxLength={30}
                            onChange={(value) => setData('phone', value)}
                        />
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
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Contactos"
                        sub="Personas con quienes se gestionan las compras"
                    />
                    <SupplierContactsSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Direcciones"
                        sub="Dónde se factura, se retira y se despacha la mercancía"
                    />
                    <SupplierAddressesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={5}
                        title="Condiciones comerciales"
                        sub="Moneda, crédito y tiempo de entrega acordados"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <TextField
                            id="currency"
                            label="Moneda *"
                            value={data.currency}
                            error={errors.currency}
                            placeholder="USD"
                            maxLength={3}
                            onChange={(value) =>
                                setData('currency', value.toUpperCase())
                            }
                        />

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
                            {errors.credit_limit && (
                                <p className="text-sm text-bad">
                                    {errors.credit_limit}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="lead_time_days"
                                className="text-[13px] font-semibold"
                            >
                                Días de entrega
                            </Label>
                            <NumberInput
                                id="lead_time_days"
                                value={data.lead_time_days}
                                onValueChange={(value) =>
                                    setData('lead_time_days', value)
                                }
                                min={0}
                                decimals={0}
                                className={`h-[42px] rounded-[10px] ${errors.lead_time_days ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Alimenta la sugerencia de reorden
                            </span>
                            {errors.lead_time_days && (
                                <p className="text-sm text-bad">
                                    {errors.lead_time_days}
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
                                className="rounded-[10px]"
                                rows={3}
                            />
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
                            Proveedor
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
                          ? 'Crear proveedor'
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
