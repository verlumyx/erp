import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
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

const DOCUMENT_TYPE_OPTIONS: OptionType[] = Object.entries(
    DOCUMENT_TYPE_LABELS,
).map(([value, label]) => ({ value, label }));

const CREDIT_BLOCKED_OPTIONS: OptionType[] = [
    { value: 'no', label: 'No' },
    { value: 'yes', label: 'Sí' },
];

export function ClientForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
        useClientFormContext();

    const clientTypeOptions: OptionType[] = [
        { value: NO_CLIENT_TYPE, label: 'Sin tipo' },
        ...options.clientTypes.map((clientType) => ({
            value: clientType.id,
            label: clientType.name,
        })),
    ];

    const priceListOptions: OptionType[] = [
        { value: NO_PRICE_LIST, label: 'Lista por defecto de la empresa' },
        ...options.priceLists.map((priceList) => ({
            value: priceList.id,
            label: priceList.name,
        })),
    ];

    const salespersonOptions: OptionType[] = [
        { value: NO_SALESPERSON, label: 'Sin vendedor' },
        ...options.salespeople.map((salesperson) => ({
            value: salesperson.id,
            label: salesperson.name,
        })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Identificación"
                sub="Razón social y RIF con que se factura al cliente"
            >
                <FormFieldGrid>
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
                        <Select2
                            options={DOCUMENT_TYPE_OPTIONS}
                            value={
                                DOCUMENT_TYPE_OPTIONS.find(
                                    (option) =>
                                        option.value === data.document_type,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'document_type',
                                    (option?.value ?? '') as DocumentType,
                                )
                            }
                            error={!!errors.document_type}
                            size="md"
                        />
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
                            setData('document_number', value.replace(/\D/g, ''))
                        }
                    />

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo de cliente
                        </Label>
                        <Select2
                            options={clientTypeOptions}
                            value={
                                clientTypeOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.client_type_id || NO_CLIENT_TYPE),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'client_type_id',
                                    !option || option.value === NO_CLIENT_TYPE
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.client_type_id}
                            size="md"
                            placeholder="Sin tipo"
                        />
                        {errors.client_type_id && (
                            <p className="text-sm text-bad">
                                {errors.client_type_id}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Datos de contacto"
                sub="Correo, teléfonos y dirección fiscal del cliente"
            >
                <FormFieldGrid>
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
                            <p className="text-sm text-bad">{errors.phone}</p>
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

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="address"
                            className="text-[13px] font-semibold"
                        >
                            Dirección fiscal
                        </Label>
                        <Textarea
                            id="address"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            className="rounded-[10px]"
                            rows={2}
                            maxLength={500}
                        />
                        {errors.address && (
                            <p className="text-sm text-bad">{errors.address}</p>
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Contactos"
                sub="Personas con quienes se gestionan las ventas"
            >
                <ClientContactsSection />
            </FormSection>

            <FormSection
                step={4}
                title="Direcciones"
                sub="Dónde se factura y a qué sucursales se despacha"
            >
                <ClientAddressesSection />
            </FormSection>

            <FormSection
                step={5}
                title="Condiciones comerciales"
                sub="Lista de precio, crédito, descuento y vendedor asignado"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Lista de precio
                        </Label>
                        <Select2
                            options={priceListOptions}
                            value={
                                priceListOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.price_list_id || NO_PRICE_LIST),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'price_list_id',
                                    !option || option.value === NO_PRICE_LIST
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.price_list_id}
                            size="md"
                            placeholder="Lista por defecto"
                        />
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
                        <Select2
                            options={salespersonOptions}
                            value={
                                salespersonOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.salesperson_id || NO_SALESPERSON),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'salesperson_id',
                                    !option || option.value === NO_SALESPERSON
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.salesperson_id}
                            size="md"
                            placeholder="Sin vendedor"
                        />
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
                        <Select2
                            options={CREDIT_BLOCKED_OPTIONS}
                            value={
                                CREDIT_BLOCKED_OPTIONS.find(
                                    (option) =>
                                        option.value === data.credit_blocked,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'credit_blocked',
                                    (option?.value ?? 'no') as YesNo,
                                )
                            }
                            error={!!errors.credit_blocked}
                            size="md"
                            isSearchable={false}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Bloquea nuevas ventas a crédito
                        </span>
                        {errors.credit_blocked && (
                            <p className="text-sm text-bad">
                                {errors.credit_blocked}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                        <Label
                            htmlFor="notes"
                            className="text-[13px] font-semibold"
                        >
                            Notas
                        </Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Preferencias de pago, referido por…"
                            className="rounded-[10px]"
                            rows={3}
                        />
                        {errors.notes && (
                            <p className="text-sm text-bad">{errors.notes}</p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Cliente">
                    {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                </SummaryRow>
                <SummaryRow label="RIF">
                    {data.document_number
                        ? formatDocument(
                              data.document_type,
                              data.document_number,
                          )
                        : '—'}
                </SummaryRow>
                <SummaryRow label="Contactos">
                    {data.contacts.length}
                </SummaryRow>
                <SummaryRow label="Direcciones">
                    {data.addresses.length}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear cliente' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
