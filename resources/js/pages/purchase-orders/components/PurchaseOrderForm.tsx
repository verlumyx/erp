import { Check } from 'lucide-react';
import { CurrencySelect } from '@/components/currency-select';
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
import { usePurchaseOrderFormContext } from '../contexts/PurchaseOrderFormContext';
import { formatAmount } from '../types/PurchaseOrder';
import { PurchaseOrderLinesSection } from './PurchaseOrderLinesSection';

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

export function PurchaseOrderForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        totals,
    } = usePurchaseOrderFormContext();

    /** El proveedor arrastra su moneda y sus días de crédito; ambos quedan editables. */
    const handleSupplierChange = (supplierId: string) => {
        const supplier = options.suppliers.find(
            (option) => option.id === supplierId,
        );

        setData((current) => ({
            ...current,
            supplier_id: supplierId,
            currency: supplier?.currency ?? current.currency,
            payment_term_days:
                supplier?.payment_term_days ?? current.payment_term_days,
        }));
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Datos de la orden"
                        sub="A quién se le pide, dónde se recibe y cuándo"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Proveedor *
                            </Label>
                            <Select
                                value={data.supplier_id}
                                onValueChange={handleSupplierChange}
                            >
                                <SelectTrigger
                                    className={`h-[42px] w-full rounded-[10px] ${errors.supplier_id ? 'border-bad' : ''}`}
                                >
                                    <SelectValue placeholder="Selecciona un proveedor" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.suppliers.map((supplier) => (
                                        <SelectItem
                                            key={supplier.id}
                                            value={supplier.id}
                                        >
                                            {supplier.code} · {supplier.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.supplier_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega de recepción *
                            </Label>
                            <Select
                                value={data.warehouse_id}
                                onValueChange={(value) =>
                                    setData('warehouse_id', value)
                                }
                            >
                                <SelectTrigger
                                    className={`h-[42px] w-full rounded-[10px] ${errors.warehouse_id ? 'border-bad' : ''}`}
                                >
                                    <SelectValue placeholder="Selecciona una bodega" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.warehouses.map((warehouse) => (
                                        <SelectItem
                                            key={warehouse.id}
                                            value={warehouse.id}
                                        >
                                            {warehouse.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.warehouse_id && (
                                <p className="text-sm text-bad">
                                    {errors.warehouse_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="order_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha de emisión *
                            </Label>
                            <Input
                                id="order_date"
                                type="date"
                                value={data.order_date}
                                onChange={(e) =>
                                    setData('order_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.order_date ? 'border-bad' : ''}`}
                            />
                            {errors.order_date && (
                                <p className="text-sm text-bad">
                                    {errors.order_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="expected_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha estimada de entrega
                            </Label>
                            <Input
                                id="expected_date"
                                type="date"
                                value={data.expected_date}
                                onChange={(e) =>
                                    setData('expected_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.expected_date ? 'border-bad' : ''}`}
                            />
                            {errors.expected_date && (
                                <p className="text-sm text-bad">
                                    {errors.expected_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5 sm:col-span-2">
                            <Label
                                htmlFor="supplier_reference"
                                className="text-[13px] font-semibold"
                            >
                                Referencia del proveedor
                            </Label>
                            <Input
                                id="supplier_reference"
                                type="text"
                                value={data.supplier_reference}
                                onChange={(e) =>
                                    setData(
                                        'supplier_reference',
                                        e.target.value,
                                    )
                                }
                                placeholder="Número de cotización o pedido del proveedor"
                                maxLength={60}
                                className={`h-[42px] rounded-[10px] ${errors.supplier_reference ? 'border-bad' : ''}`}
                            />
                            {errors.supplier_reference && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_reference}
                                </p>
                            )}
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Qué se pide, en qué unidad y a qué costo"
                    />
                    <PurchaseOrderLinesSection />
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Condiciones"
                        sub="Moneda, tasa, crédito y descuento global"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
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
                                htmlFor="exchange_rate"
                                className="text-[13px] font-semibold"
                            >
                                Tasa de cambio
                            </Label>
                            <NumberInput
                                id="exchange_rate"
                                value={data.exchange_rate}
                                onValueChange={(value) =>
                                    setData('exchange_rate', value)
                                }
                                min={0}
                                decimals={8}
                                className={`h-[42px] rounded-[10px] ${errors.exchange_rate ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Tasa a moneda base del documento
                            </span>
                            {errors.exchange_rate && (
                                <p className="text-sm text-bad">
                                    {errors.exchange_rate}
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
                                htmlFor="discount_amount"
                                className="text-[13px] font-semibold"
                            >
                                Descuento global
                            </Label>
                            <CurrencyInput
                                id="discount_amount"
                                value={data.discount_amount}
                                onValueChange={(value) =>
                                    setData('discount_amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.discount_amount ? 'border-bad' : ''}`}
                            />
                            {errors.discount_amount && (
                                <p className="text-sm text-bad">
                                    {errors.discount_amount}
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
                            Líneas
                        </span>
                        <b className="font-bold">{data.lines.length}</b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Subtotal
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.subtotal, data.currency)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Descuento
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(data.discount_amount, data.currency)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Impuesto
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.taxAmount, data.currency)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t pt-2.5 text-[15px]">
                        <span className="font-semibold">Total</span>
                        <b className="font-extrabold tabular-nums">
                            {formatAmount(totals.total, data.currency)}
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
                          ? 'Crear orden'
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
