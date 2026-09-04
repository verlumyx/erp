import { FormFieldGrid, FormLayout } from '@/components/form-layout';
import { FormSection } from '@/components/form-section';
import {
    FormActionBar,
    FormSummary,
    SummaryRow,
} from '@/components/form-summary';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useWarehouseFormContext } from '../contexts/WarehouseFormContext';
import {
    WAREHOUSE_TYPE_LABELS,
    type WarehouseType,
    type YesNo,
} from '../types/Warehouse';

interface FlagFieldProps {
    id: string;
    label: string;
    hint: string;
    value: YesNo;
    onChange: (value: YesNo) => void;
}

function FlagField({ id, label, hint, value, onChange }: FlagFieldProps) {
    return (
        <div className="flex items-start gap-3 rounded-[10px] border p-3.5">
            <Checkbox
                id={id}
                checked={value === 'yes'}
                onCheckedChange={(checked) => onChange(checked ? 'yes' : 'no')}
                className="mt-0.5"
            />
            <div className="flex flex-col gap-0.5">
                <Label htmlFor={id} className="text-[13px] font-semibold">
                    {label}
                </Label>
                <span className="text-[12px] text-muted-foreground">
                    {hint}
                </span>
            </div>
        </div>
    );
}

const TYPE_OPTIONS: OptionType[] = Object.entries(WAREHOUSE_TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

export function WarehouseForm() {
    const { data, setData, processing, errors, handleSubmit, mode, users } =
        useWarehouseFormContext();

    const userOptions: OptionType[] = [
        { value: 'ninguno', label: 'Sin encargado' },
        ...users.map((user) => ({ value: user.id, label: user.name })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Datos de la bodega"
                sub="Dónde se almacena el inventario"
            >
                <div className="flex flex-col gap-4 p-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                placeholder="Ej. Bodega Central"
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
                                htmlFor="type"
                                className="text-[13px] font-semibold"
                            >
                                Tipo *
                            </Label>
                            <Select2
                                inputId="type"
                                options={TYPE_OPTIONS}
                                value={
                                    TYPE_OPTIONS.find(
                                        (option) => option.value === data.type,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'type',
                                        (option?.value ?? '') as WarehouseType,
                                    )
                                }
                                error={!!errors.type}
                                size="md"
                                placeholder="Tipo de bodega"
                            />
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
                                htmlFor="city"
                                className="text-[13px] font-semibold"
                            >
                                Ciudad
                            </Label>
                            <Input
                                id="city"
                                type="text"
                                value={data.city}
                                onChange={(e) =>
                                    setData('city', e.target.value)
                                }
                                placeholder="Ej. Caracas"
                                className={`h-[42px] rounded-[10px] ${errors.city ? 'border-bad' : ''}`}
                                maxLength={100}
                            />
                            {errors.city && (
                                <p className="text-sm text-bad">
                                    {errors.city}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="phone"
                                className="text-[13px] font-semibold"
                            >
                                Teléfono
                            </Label>
                            <Input
                                id="phone"
                                type="text"
                                value={data.phone}
                                onChange={(e) =>
                                    setData('phone', e.target.value)
                                }
                                placeholder="Ej. 04141234567"
                                className={`h-[42px] rounded-[10px] ${errors.phone ? 'border-bad' : ''}`}
                                maxLength={30}
                            />
                            {errors.phone && (
                                <p className="text-sm text-bad">
                                    {errors.phone}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="address"
                            className="text-[13px] font-semibold"
                        >
                            Dirección
                        </Label>
                        <Input
                            id="address"
                            type="text"
                            value={data.address}
                            onChange={(e) => setData('address', e.target.value)}
                            placeholder="Calle, avenida, referencia…"
                            className={`h-[42px] rounded-[10px] ${errors.address ? 'border-bad' : ''}`}
                            maxLength={500}
                        />
                        {errors.address && (
                            <p className="text-sm text-bad">{errors.address}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="responsible_user_id"
                            className="text-[13px] font-semibold"
                        >
                            Encargado
                        </Label>
                        <Select2
                            inputId="responsible_user_id"
                            options={userOptions}
                            value={
                                userOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.responsible_user_id || 'ninguno'),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'responsible_user_id',
                                    !option || option.value === 'ninguno'
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.responsible_user_id}
                            size="md"
                            placeholder="Sin encargado"
                        />
                        {errors.responsible_user_id && (
                            <p className="text-sm text-bad">
                                {errors.responsible_user_id}
                            </p>
                        )}
                    </div>
                </div>
            </FormSection>

            <FormSection
                step={2}
                title="Comportamiento"
                sub="Cómo se usa la bodega en los documentos"
            >
                <FormFieldGrid>
                    <FlagField
                        id="is_default"
                        label="Bodega por defecto"
                        hint="Se sugiere en los documentos. Solo una por empresa."
                        value={data.is_default}
                        onChange={(value) => setData('is_default', value)}
                    />
                    <FlagField
                        id="is_sales_available"
                        label="Disponible para venta"
                        hint="Su existencia cuenta como disponible para vender."
                        value={data.is_sales_available}
                        onChange={(value) =>
                            setData('is_sales_available', value)
                        }
                    />
                    <FlagField
                        id="allows_negative_stock"
                        label="Permite existencia negativa"
                        hint="Admite salidas sin existencia suficiente."
                        value={data.allows_negative_stock}
                        onChange={(value) =>
                            setData('allows_negative_stock', value)
                        }
                    />
                    <FlagField
                        id="uses_locations"
                        label="Usa ubicaciones"
                        hint="Habilita el árbol de ubicaciones (pasillo/estante)."
                        value={data.uses_locations}
                        onChange={(value) => setData('uses_locations', value)}
                    />
                </FormFieldGrid>
                <div className="flex flex-col gap-1.5 px-5 pb-5">
                    <Label
                        htmlFor="notes"
                        className="text-[13px] font-semibold"
                    >
                        Notas (opcional)
                    </Label>
                    <Textarea
                        id="notes"
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        placeholder="Observaciones sobre la bodega…"
                        className={`rounded-[10px] ${errors.notes ? 'border-bad' : ''}`}
                        rows={3}
                    />
                    {errors.notes && (
                        <p className="text-sm text-bad">{errors.notes}</p>
                    )}
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Bodega">
                    {data.name || (mode === 'create' ? 'Nueva' : '—')}
                </SummaryRow>
                <SummaryRow label="Tipo">
                    {WAREHOUSE_TYPE_LABELS[data.type]}
                </SummaryRow>
                <SummaryRow label="Ubicaciones">
                    {data.uses_locations === 'yes' ? 'Sí' : 'No'}
                </SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear bodega' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
