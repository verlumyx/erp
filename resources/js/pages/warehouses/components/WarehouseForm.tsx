import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { useWarehouseFormContext } from '../contexts/WarehouseFormContext';
import {
    WAREHOUSE_TYPE_LABELS,
    type WarehouseType,
    type YesNo,
} from '../types/Warehouse';

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

export function WarehouseForm() {
    const { data, setData, processing, errors, handleSubmit, mode, users } =
        useWarehouseFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la bodega"
                        sub="Dónde se almacena el inventario"
                    />
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
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData('type', value as WarehouseType)
                                    }
                                >
                                    <SelectTrigger
                                        id="type"
                                        className="h-[42px] w-full rounded-[10px]"
                                    >
                                        <SelectValue placeholder="Tipo de bodega" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(
                                            WAREHOUSE_TYPE_LABELS,
                                        ).map(([value, label]) => (
                                            <SelectItem
                                                key={value}
                                                value={value}
                                            >
                                                {label}
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
                                onChange={(e) =>
                                    setData('address', e.target.value)
                                }
                                placeholder="Calle, avenida, referencia…"
                                className={`h-[42px] rounded-[10px] ${errors.address ? 'border-bad' : ''}`}
                                maxLength={500}
                            />
                            {errors.address && (
                                <p className="text-sm text-bad">
                                    {errors.address}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="responsible_user_id"
                                className="text-[13px] font-semibold"
                            >
                                Encargado
                            </Label>
                            <Select
                                value={data.responsible_user_id || 'ninguno'}
                                onValueChange={(value) =>
                                    setData(
                                        'responsible_user_id',
                                        value === 'ninguno' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="responsible_user_id"
                                    className="h-[42px] w-full rounded-[10px]"
                                >
                                    <SelectValue placeholder="Sin encargado" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ninguno">
                                        Sin encargado
                                    </SelectItem>
                                    {users.map((user) => (
                                        <SelectItem
                                            key={user.id}
                                            value={user.id}
                                        >
                                            {user.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.responsible_user_id && (
                                <p className="text-sm text-bad">
                                    {errors.responsible_user_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Comportamiento"
                        sub="Cómo se usa la bodega en los documentos"
                    />
                    <div className="grid grid-cols-1 gap-3 p-5 md:grid-cols-2">
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
                            onChange={(value) =>
                                setData('uses_locations', value)
                            }
                        />
                    </div>
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
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Bodega
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nueva' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tipo
                        </span>
                        <b className="font-bold">
                            {WAREHOUSE_TYPE_LABELS[data.type]}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Ubicaciones
                        </span>
                        <b className="font-bold">
                            {data.uses_locations === 'yes' ? 'Sí' : 'No'}
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
                          ? 'Crear bodega'
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
