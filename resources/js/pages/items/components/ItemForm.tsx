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

const TYPE_OPTIONS: OptionType[] = Object.entries(ITEM_TYPE_LABELS).map(
    ([value, label]) => ({ value, label }),
);

const COST_METHOD_OPTIONS: OptionType[] = Object.entries(
    COST_METHOD_LABELS,
).map(([value, label]) => ({ value, label }));

const YES_NO_OPTIONS: OptionType[] = [
    { value: 'yes', label: 'Sí' },
    { value: 'no', label: 'No' },
];

export function ItemForm() {
    const { data, setData, processing, errors, handleSubmit, mode, options } =
        useItemFormContext();

    const categoryOptions: OptionType[] = [
        { value: NO_CATEGORY, label: 'Sin categoría' },
        ...options.categories.map((category) => ({
            value: category.id,
            label: category.name,
        })),
    ];

    const taxOptions: OptionType[] = [
        { value: NO_TAX, label: 'Sin impuesto' },
        ...options.taxes.map((tax) => ({
            value: tax.id,
            label: taxLabel(tax),
        })),
    ];

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Identificación"
                sub="Cómo se reconoce el artículo dentro y fuera del sistema"
            >
                <FormFieldGrid>
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
                            onChange={(e) => setData('barcode', e.target.value)}
                            placeholder="EAN / UPC"
                            className={`h-[42px] rounded-[10px] ${errors.barcode ? 'border-bad' : ''}`}
                            maxLength={60}
                        />
                        {errors.barcode && (
                            <p className="text-sm text-bad">{errors.barcode}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
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
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Ej. Martillo de carpintero 16 oz"
                            className={`h-[42px] rounded-[10px] ${errors.name ? 'border-bad' : ''}`}
                            maxLength={200}
                            required
                        />
                        {errors.name && (
                            <p className="text-sm text-bad">{errors.name}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Tipo *
                        </Label>
                        <Select2
                            options={TYPE_OPTIONS}
                            value={
                                TYPE_OPTIONS.find(
                                    (option) => option.value === data.type,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'type',
                                    (option?.value ?? '') as ItemType,
                                )
                            }
                            error={!!errors.type}
                            size="md"
                        />
                        {errors.type && (
                            <p className="text-sm text-bad">{errors.type}</p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Categoría
                        </Label>
                        <Select2
                            options={categoryOptions}
                            value={
                                categoryOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.category_id || NO_CATEGORY),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'category_id',
                                    !option || option.value === NO_CATEGORY
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.category_id}
                            size="md"
                            placeholder="Sin categoría"
                        />
                        {errors.category_id && (
                            <p className="text-sm text-bad">
                                {errors.category_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5 sm:col-span-2 xl:col-span-3">
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
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Unidades del artículo"
                sub="Una unidad base y las demás por su factor de conversión"
            >
                <ItemUnitsSection />
            </FormSection>

            <FormSection
                step={3}
                title="Precios por lista"
                sub="Precio, moneda y vigencia del artículo en cada lista"
            >
                <ItemPricesSection />
            </FormSection>

            <FormSection
                step={4}
                title="Costos, impuestos y venta"
                sub="Cómo se valúa el artículo, qué impuestos aplica y dónde puede usarse"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Método de costo *
                        </Label>
                        <Select2
                            options={COST_METHOD_OPTIONS}
                            value={
                                COST_METHOD_OPTIONS.find(
                                    (option) =>
                                        option.value === data.cost_method,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'cost_method',
                                    (option?.value ?? '') as CostMethod,
                                )
                            }
                            error={!!errors.cost_method}
                            size="md"
                        />
                    </div>

                    <MoneyField
                        id="standard_cost"
                        label="Costo estándar"
                        value={data.standard_cost}
                        error={errors.standard_cost}
                        onChange={(value) => setData('standard_cost', value)}
                    />

                    <MoneyField
                        id="min_price"
                        label="Precio mínimo"
                        value={data.min_price}
                        error={errors.min_price}
                        onChange={(value) => setData('min_price', value)}
                    />
                </FormFieldGrid>
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Se compra
                        </Label>
                        <Select2
                            options={YES_NO_OPTIONS}
                            value={
                                YES_NO_OPTIONS.find(
                                    (option) =>
                                        option.value === data.is_purchasable,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'is_purchasable',
                                    (option?.value ?? 'no') as 'yes' | 'no',
                                )
                            }
                            error={!!errors.is_purchasable}
                            size="md"
                            isSearchable={false}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Impuesto de compra
                        </Label>
                        <Select2
                            options={taxOptions}
                            value={
                                taxOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.purchase_tax_id || NO_TAX),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'purchase_tax_id',
                                    !option || option.value === NO_TAX
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.purchase_tax_id}
                            size="md"
                            placeholder="Sin impuesto"
                        />
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
                        <Select2
                            options={YES_NO_OPTIONS}
                            value={
                                YES_NO_OPTIONS.find(
                                    (option) =>
                                        option.value === data.is_sellable,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'is_sellable',
                                    (option?.value ?? 'no') as 'yes' | 'no',
                                )
                            }
                            error={!!errors.is_sellable}
                            size="md"
                            isSearchable={false}
                        />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label className="text-[13px] font-semibold">
                            Impuesto de venta
                        </Label>
                        <Select2
                            options={taxOptions}
                            value={
                                taxOptions.find(
                                    (option) =>
                                        option.value ===
                                        (data.sale_tax_id || NO_TAX),
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'sale_tax_id',
                                    !option || option.value === NO_TAX
                                        ? ''
                                        : option.value,
                                )
                            }
                            error={!!errors.sale_tax_id}
                            size="md"
                            placeholder="Sin impuesto"
                        />
                        {errors.sale_tax_id && (
                            <p className="text-sm text-bad">
                                {errors.sale_tax_id}
                            </p>
                        )}
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={5}
                title="Reposición y logística"
                sub="Puntos de reorden y datos físicos del artículo"
            >
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
                        onChange={(value) => setData('reorder_quantity', value)}
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
                            onChange={(e) => setData('notes', e.target.value)}
                            className="rounded-[10px]"
                            rows={3}
                        />
                    </div>
                </div>
            </FormSection>

            <FormSummary>
                <SummaryRow label="Artículo">
                    {data.name || (mode === 'create' ? 'Nuevo' : '—')}
                </SummaryRow>
                <SummaryRow label="Tipo">
                    {ITEM_TYPE_LABELS[data.type]}
                </SummaryRow>
                <SummaryRow label="Unidades">{data.units.length}</SummaryRow>
                <SummaryRow label="Precios">{data.prices.length}</SummaryRow>
            </FormSummary>

            <FormActionBar
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Crear artículo' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
