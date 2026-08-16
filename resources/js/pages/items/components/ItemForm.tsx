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
import { useItemFormContext } from '../contexts/ItemFormContext';
import {
    COST_METHOD_LABELS,
    ITEM_TYPE_LABELS,
    type CostMethod,
    type ItemType,
} from '../types/Item';
import { ItemPricesSection } from './ItemPricesSection';
import { ItemUnitsSection } from './ItemUnitsSection';

const NO_CATEGORY = 'none';
const NO_TAX = 'none';

/** Ej. "IVA general (16%)": el porcentaje llega como decimal(18,4). */
function taxLabel(tax: { name: string; percentage: string }): string {
    return `${tax.name} (${Number(tax.percentage)}%)`;
}

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

interface NumericFieldProps {
    id: string;
    label: string;
    value: number;
    error?: string;
    onChange: (value: number) => void;
}

/** Cantidades y magnitudes físicas del artículo: `decimal(18,4)`. */
function QuantityField({
    id,
    label,
    value,
    error,
    onChange,
}: NumericFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id} className="text-[13px] font-semibold">
                {label}
            </Label>
            <NumberInput
                id={id}
                value={value}
                onValueChange={onChange}
                min={0}
                decimals={4}
                className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''}`}
            />
            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}

/** Precios y costos unitarios: `decimal(18,6)`. */
function MoneyField({ id, label, value, error, onChange }: NumericFieldProps) {
    return (
        <div className="flex flex-col gap-1.5">
            <Label htmlFor={id} className="text-[13px] font-semibold">
                {label}
            </Label>
            <CurrencyInput
                id={id}
                value={value}
                onValueChange={onChange}
                min={0}
                decimals={6}
                className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''}`}
            />
            {error && <p className="text-sm text-bad">{error}</p>}
        </div>
    );
}

export function ItemForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
        useItemFormContext();

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
                        sub="Cómo se reconoce el artículo dentro y fuera del sistema"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="sku"
                                className="text-[13px] font-semibold"
                            >
                                SKU *
                            </Label>
                            <Input
                                id="sku"
                                type="text"
                                value={data.sku}
                                onChange={(e) => setData('sku', e.target.value)}
                                placeholder="Ej. MART-001"
                                className={`h-[42px] rounded-[10px] ${errors.sku ? 'border-bad' : ''}`}
                                maxLength={60}
                                required
                            />
                            {errors.sku && (
                                <p className="text-sm text-bad">{errors.sku}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="barcode"
                                className="text-[13px] font-semibold"
                            >
                                Código de barras
                            </Label>
                            <Input
                                id="barcode"
                                type="text"
                                value={data.barcode}
                                onChange={(e) =>
                                    setData('barcode', e.target.value)
                                }
                                placeholder="EAN / UPC"
                                className={`h-[42px] rounded-[10px] ${errors.barcode ? 'border-bad' : ''}`}
                                maxLength={60}
                            />
                            {errors.barcode && (
                                <p className="text-sm text-bad">
                                    {errors.barcode}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
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
                                placeholder="Ej. Martillo de carpintero 16 oz"
                                className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                                maxLength={200}
                                required
                            />
                            {errors.name && (
                                <p className="text-sm text-bad">
                                    {errors.name}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Tipo *
                            </Label>
                            <Select
                                value={data.type}
                                onValueChange={(value) =>
                                    setData('type', value as ItemType)
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(ITEM_TYPE_LABELS).map(
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
                            {errors.type && (
                                <p className="text-sm text-bad">
                                    {errors.type}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Categoría
                            </Label>
                            <Select
                                value={data.category_id || NO_CATEGORY}
                                onValueChange={(value) =>
                                    setData(
                                        'category_id',
                                        value === NO_CATEGORY ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin categoría" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_CATEGORY}>
                                        Sin categoría
                                    </SelectItem>
                                    {options.categories.map((category) => (
                                        <SelectItem
                                            key={category.id}
                                            value={category.id}
                                        >
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.category_id && (
                                <p className="text-sm text-bad">
                                    {errors.category_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="description"
                                className="text-[13px] font-semibold"
                            >
                                Descripción
                            </Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                placeholder="Detalle largo del artículo…"
                                className="rounded-[10px]"
                                rows={3}
                            />
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Unidades del artículo"
                        sub="Una unidad base y las demás por su factor de conversión"
                    />
                    <ItemUnitsSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Precios por lista"
                        sub="Precio, moneda y vigencia del artículo en cada lista"
                    />
                    <ItemPricesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={4}
                        title="Costos, impuestos y venta"
                        sub="Cómo se valúa el artículo, qué impuestos aplica y dónde puede usarse"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Método de costo *
                            </Label>
                            <Select
                                value={data.cost_method}
                                onValueChange={(value) =>
                                    setData('cost_method', value as CostMethod)
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(COST_METHOD_LABELS).map(
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
                        </div>

                        <MoneyField
                            id="standard_cost"
                            label="Costo estándar"
                            value={data.standard_cost}
                            error={errors.standard_cost}
                            onChange={(value) =>
                                setData('standard_cost', value)
                            }
                        />

                        <MoneyField
                            id="min_price"
                            label="Precio mínimo"
                            value={data.min_price}
                            error={errors.min_price}
                            onChange={(value) => setData('min_price', value)}
                        />
                    </div>
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Se compra
                            </Label>
                            <Select
                                value={data.is_purchasable}
                                onValueChange={(value) =>
                                    setData(
                                        'is_purchasable',
                                        value as 'yes' | 'no',
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="yes">Sí</SelectItem>
                                    <SelectItem value="no">No</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Impuesto de compra
                            </Label>
                            <Select
                                value={data.purchase_tax_id || NO_TAX}
                                onValueChange={(value) =>
                                    setData(
                                        'purchase_tax_id',
                                        value === NO_TAX ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin impuesto" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_TAX}>
                                        Sin impuesto
                                    </SelectItem>
                                    {options.taxes.map((tax) => (
                                        <SelectItem key={tax.id} value={tax.id}>
                                            {taxLabel(tax)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.purchase_tax_id && (
                                <p className="text-sm text-bad">
                                    {errors.purchase_tax_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Se vende
                            </Label>
                            <Select
                                value={data.is_sellable}
                                onValueChange={(value) =>
                                    setData(
                                        'is_sellable',
                                        value as 'yes' | 'no',
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="yes">Sí</SelectItem>
                                    <SelectItem value="no">No</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Impuesto de venta
                            </Label>
                            <Select
                                value={data.sale_tax_id || NO_TAX}
                                onValueChange={(value) =>
                                    setData(
                                        'sale_tax_id',
                                        value === NO_TAX ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Sin impuesto" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_TAX}>
                                        Sin impuesto
                                    </SelectItem>
                                    {options.taxes.map((tax) => (
                                        <SelectItem key={tax.id} value={tax.id}>
                                            {taxLabel(tax)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.sale_tax_id && (
                                <p className="text-sm text-bad">
                                    {errors.sale_tax_id}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={5}
                        title="Reposición y logística"
                        sub="Puntos de reorden y datos físicos del artículo"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                        <QuantityField
                            id="min_stock"
                            label="Existencia mínima"
                            value={data.min_stock}
                            error={errors.min_stock}
                            onChange={(value) => setData('min_stock', value)}
                        />
                        <QuantityField
                            id="max_stock"
                            label="Existencia máxima"
                            value={data.max_stock}
                            error={errors.max_stock}
                            onChange={(value) => setData('max_stock', value)}
                        />
                        <QuantityField
                            id="reorder_quantity"
                            label="Cantidad a reordenar"
                            value={data.reorder_quantity}
                            error={errors.reorder_quantity}
                            onChange={(value) =>
                                setData('reorder_quantity', value)
                            }
                        />
                        <QuantityField
                            id="weight"
                            label="Peso unitario"
                            value={data.weight}
                            error={errors.weight}
                            onChange={(value) => setData('weight', value)}
                        />
                        <QuantityField
                            id="volume"
                            label="Volumen unitario"
                            value={data.volume}
                            error={errors.volume}
                            onChange={(value) => setData('volume', value)}
                        />
                        <div className="flex flex-col gap-1.5 sm:col-span-2 lg:col-span-3">
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
                            Artículo
                        </span>
                        <b className="font-bold">
                            {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Tipo
                        </span>
                        <b className="font-bold">
                            {ITEM_TYPE_LABELS[data.type]}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Unidades
                        </span>
                        <b className="font-bold">{data.units.length}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Precios
                        </span>
                        <b className="font-bold">{data.prices.length}</b>
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
                          ? 'Crear artículo'
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
