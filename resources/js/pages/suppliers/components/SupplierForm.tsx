import { CurrencySelect } from '@/components/currency-select';
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
import { useSupplierFormContext } from '../contexts/SupplierFormContext';
import {
    DOCUMENT_TYPE_LABELS,
    formatDocument,
    type DocumentType,
} from '../types/Supplier';
import { SupplierAddressesSection } from './SupplierAddressesSection';
import { SupplierContactsSection } from './SupplierContactsSection';

const NO_SUPPLIER_TYPE = 'none';

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

export function SupplierForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
        useSupplierFormContext();

    const supplierTypeOptions: OptionType[] = [
        { value: NO_SUPPLIER_TYPE, label: 'Sin tipo' },
        ...options.supplierTypes.map((supplierType) => ({
            value: supplierType.id,
            label: supplierType.name,
        })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Identificación"
                sub="Razón social y RIF con que se registra al proveedor"
            >
                <FormFieldGrid>
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
                            Tipo de proveedor
                        </Label>
                        <Select2
                            options={supplierTypeOptions}
                            value={
                                supplierTypeOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.supplier_type_id ||
                                            NO_SUPPLIER_TYPE),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'supplier_type_id',
                                    !option || option.value === NO_SUPPLIER_TYPE
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.supplier_type_id}
                            size="md"
                            placeholder="Sin tipo"
                        />
                        {errors.supplier_type_id && (
                            <p className="text-sm text-bad">
                                {errors.supplier_type_id}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Datos de contacto"
                sub="Correo, teléfonos y dirección fiscal del proveedor"
            >
                <FormFieldGrid>
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={3}
                title="Contactos"
                sub="Personas con quienes se gestionan las compras"
            >
                <SupplierContactsSection />
            </FormSection>

            <FormSection
                step={4}
                title="Direcciones"
                sub="Dónde se factura, se retira y se despacha la mercancía"
            >
                <SupplierAddressesSection />
            </FormSection>

            <FormSection
                step={5}
                title="Condiciones comerciales"
                sub="Moneda, crédito y tiempo de entrega acordados"
            >
                <FormFieldGrid>
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
                            className="rounded-[10px]"
                            rows={3}
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Proveedor">
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
                    mode === 'create' ? 'Crear proveedor' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
