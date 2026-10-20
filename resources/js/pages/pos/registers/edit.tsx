import { useMemo } from 'react';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import { FormActionBar, FormSummary, SummaryRow } from '@/components/form-summary';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Select2Ajax } from '@/components/select2-ajax';
import { NumberInput } from '@/components/ui/number-input';
import { useRemoteOption } from '@/hooks/use-remote-option';
import type { BreadcrumbItem } from '@/types';
import type { PosRegister } from '../types/Pos';

interface EditRegisterProps {
    register: PosRegister;
    warehouses: Array<{ id: string; name: string }>;
    priceLists: Array<{ id: string; name: string }>;
    clients: Array<{ id: string; name: string; document_type?: string; document_number?: string; tax_number?: string }>;
}

interface TextFieldProps {
    id: string;
    label: string;
    value: string;
    error?: string;
    placeholder?: string;
    maxLength?: number;
    className?: string;
    onChange: (value: string) => void;
}

function TextField({
    id,
    label,
    value,
    error,
    placeholder,
    maxLength,
    className,
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
                className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''} ${className ?? ''}`}
            />
            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}

export default function PosRegisterEditPage({
    register,
    warehouses,
    priceLists,
}: EditRegisterProps) {
    const { currentCompany } = usePage<{ currentCompany?: { id: string; name: string } }>().props;
    const companyId = currentCompany?.id || '';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Cajas Registradoras', href: `/${companyId}/pos-registers` },
        { title: `Editar ${register.code}`, href: `/${companyId}/pos-registers/${register.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm({
        name: register.name || '',
        warehouse_id: register.warehouse_id || (warehouses.length > 0 ? warehouses[0].id : ''),
        price_list_id: register.price_list_id || '',
        default_client_id: register.default_client_id || '',
        receipt_printer_name: register.receipt_printer_name || '',
        invoice_series: register.invoice_series || '',
        allow_price_override: register.allow_price_override || 'no',
        allow_custom_discounts: register.allow_custom_discounts || 'no',
        max_discount_percent: register.max_discount_percent ?? 0,
        notes: register.notes || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/${companyId}/pos-registers/${register.id}`);
    };

    const warehouseOptions: OptionType[] = useMemo(
        () => warehouses.map((w) => ({ value: w.id, label: w.name })),
        [warehouses],
    );

    const priceListOptions: OptionType[] = useMemo(
        () => [
            { value: '', label: 'Lista Estándar del Sistema' },
            ...priceLists.map((p) => ({ value: p.id, label: p.name })),
        ],
        [priceLists],
    );

    const clientRemoteOption = useRemoteOption({
        url: `/${companyId}/clients/lookup`,
        seed: register.default_client_id
            ? {
                  value: register.default_client_id,
                  label:
                      register.default_client_label ||
                      register.default_client_name ||
                      'Cliente',
              }
            : null,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar Caja - ${register.name}`} />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-5 p-6 pb-14">
                <Link
                    href={`/${companyId}/pos-registers`}
                    className="inline-flex w-max items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-4" />
                    Cajas Registradoras
                </Link>

                <div>
                    <h1 className="text-[27px] font-extrabold tracking-tight">
                        Editar {register.name} ({register.code})
                    </h1>
                    <p className="mt-1 text-[14.5px] text-muted-foreground">
                        Modifica los parámetros de operación, almacén asignado y políticas de cobro
                    </p>
                </div>

                <FormLayout onSubmit={handleSubmit}>
                    <FormSection
                        step={1}
                        title="Datos de la Terminal"
                        sub="Nombre, almacén de despacho y lista de precios asignada"
                    >
                        <FormFieldGrid>
                            <TextField
                                id="name"
                                label="Nombre de la Caja *"
                                value={data.name}
                                error={errors.name}
                                placeholder="Ej: Caja Principal 01, Mostrador Rápido"
                                maxLength={100}
                                onChange={(value) => setData('name', value)}
                            />

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="warehouse_id" className="text-[13px] font-semibold">
                                    Almacén / Bodega de Despacho *
                                </Label>
                                <Select2
                                    inputId="warehouse_id"
                                    options={warehouseOptions}
                                    value={warehouseOptions.find((w) => w.value === data.warehouse_id) ?? null}
                                    onChange={(opt) => setData('warehouse_id', opt?.value ?? '')}
                                    error={!!errors.warehouse_id}
                                    size="md"
                                    placeholder="Selecciona un almacén"
                                />
                                {errors.warehouse_id && (
                                    <p className="text-sm text-bad">{errors.warehouse_id}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="price_list_id" className="text-[13px] font-semibold">
                                    Lista de Precios por Defecto
                                </Label>
                                <Select2
                                    inputId="price_list_id"
                                    options={priceListOptions}
                                    value={priceListOptions.find((p) => p.value === (data.price_list_id || '')) ?? null}
                                    onChange={(opt) => setData('price_list_id', opt?.value ?? '')}
                                    error={!!errors.price_list_id}
                                    size="md"
                                    placeholder="Lista Estándar del Sistema"
                                />
                                {errors.price_list_id && (
                                    <p className="text-sm text-bad">{errors.price_list_id}</p>
                                )}
                            </div>
                        </FormFieldGrid>
                    </FormSection>

                    <FormSection
                        step={2}
                        title="Facturación y Cliente"
                        sub="Cliente predeterminado de mostrador y serie de comprobantes"
                    >
                        <FormFieldGrid>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="default_client_id" className="text-[13px] font-semibold">
                                    Cliente por Defecto (Mostrador) *
                                </Label>
                                <Select2Ajax
                                    inputId="default_client_id"
                                    url={clientRemoteOption.url}
                                    value={clientRemoteOption.optionOf(data.default_client_id)}
                                    onChange={(opt) => {
                                        clientRemoteOption.select(opt);
                                        setData('default_client_id', opt?.value ?? '');
                                    }}
                                    error={!!errors.default_client_id}
                                    size="md"
                                    placeholder="Busca un cliente por nombre o RIF..."
                                />
                                {errors.default_client_id && (
                                    <p className="text-sm text-bad">{errors.default_client_id}</p>
                                )}
                            </div>

                            <TextField
                                id="invoice_series"
                                label="Serie de Facturación Fiscal"
                                value={data.invoice_series}
                                error={errors.invoice_series}
                                placeholder="Ej: CAJ01, F001"
                                maxLength={20}
                                className="font-mono"
                                onChange={(value) => setData('invoice_series', value)}
                            />

                            <TextField
                                id="receipt_printer_name"
                                label="Nombre Impresora de Tickets"
                                value={data.receipt_printer_name}
                                error={errors.receipt_printer_name}
                                placeholder="Ej: EPSON TM-T20III"
                                maxLength={100}
                                onChange={(value) => setData('receipt_printer_name', value)}
                            />
                        </FormFieldGrid>
                    </FormSection>

                    <FormSection
                        step={3}
                        title="Políticas de Venta y Descuento"
                        sub="Límites de descuento para cajeros, permisos de precio y notas"
                    >
                        <FormFieldGrid>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="max_discount_percent" className="text-[13px] font-semibold">
                                    % Máximo Descuento Permitido
                                </Label>
                                <NumberInput
                                    id="max_discount_percent"
                                    min={0}
                                    max={100}
                                    decimals={2}
                                    value={data.max_discount_percent}
                                    onValueChange={(val) => setData('max_discount_percent', val)}
                                    className={`h-[42px] rounded-[10px] text-sm font-mono ${errors.max_discount_percent ? 'border-bad' : ''}`}
                                    placeholder="0"
                                />
                                {errors.max_discount_percent && (
                                    <p className="text-sm text-bad">{errors.max_discount_percent}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-3 sm:col-span-2 xl:col-span-2 pt-1">
                                <Label className="text-[13px] font-semibold">
                                    Permisos para Cajeros
                                </Label>
                                <div className="flex flex-col sm:flex-row gap-6 pt-1">
                                    <label
                                        htmlFor="allow_custom_discounts"
                                        className="flex items-center gap-2.5 cursor-pointer text-sm font-medium"
                                    >
                                        <Checkbox
                                            id="allow_custom_discounts"
                                            checked={data.allow_custom_discounts === 'yes'}
                                            onCheckedChange={(checked) =>
                                                setData('allow_custom_discounts', checked ? 'yes' : 'no')
                                            }
                                        />
                                        <span>Permitir otorgar descuentos en ventas</span>
                                    </label>

                                    <label
                                        htmlFor="allow_price_override"
                                        className="flex items-center gap-2.5 cursor-pointer text-sm font-medium"
                                    >
                                        <Checkbox
                                            id="allow_price_override"
                                            checked={data.allow_price_override === 'yes'}
                                            onCheckedChange={(checked) =>
                                                setData('allow_price_override', checked ? 'yes' : 'no')
                                            }
                                        />
                                        <span>Permitir modificar precio unitario</span>
                                    </label>
                                </div>
                            </div>

                            <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
                                <Label htmlFor="notes" className="text-[13px] font-semibold">
                                    Observaciones o Ubicación Física
                                </Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder="Ej. Ubicada en el mostrador principal del pasillo 1..."
                                    className="rounded-[10px]"
                                    rows={3}
                                />
                                {errors.notes && <p className="text-sm text-bad">{errors.notes}</p>}
                            </div>
                        </FormFieldGrid>
                    </FormSection>

                    <FormSummary>
                        <SummaryRow label="Caja">
                            {data.name || register.code || '—'}
                        </SummaryRow>
                        <SummaryRow label="Almacén">
                            {warehouseOptions.find((w) => w.value === data.warehouse_id)?.label || '—'}
                        </SummaryRow>
                        <SummaryRow label="Cliente Mostrador">
                            {clientRemoteOption.optionOf(data.default_client_id)?.label || register.default_client_label || register.default_client_name || '—'}
                        </SummaryRow>
                        <SummaryRow label="Lista de Precios">
                            {priceListOptions.find((p) => p.value === data.price_list_id)?.label || 'Lista Estándar'}
                        </SummaryRow>
                        <SummaryRow label="Descuento Máx.">
                            {`${data.max_discount_percent ?? 0}%`}
                        </SummaryRow>
                    </FormSummary>

                    <FormActionBar
                        processing={processing}
                        submitLabel="Guardar cambios"
                        onCancel={() => router.visit(`/${companyId}/pos-registers`)}
                    />
                </FormLayout>
            </div>
        </AppLayout>
    );
}
