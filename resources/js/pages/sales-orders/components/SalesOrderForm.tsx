import { Check } from 'lucide-react';
import { CurrencySelect } from '@/components/currency-select';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
import { useSalesOrderFormContext } from '../contexts/SalesOrderFormContext';
import { formatAmount } from '../types/SalesOrder';
import { SalesOrderLinesSection } from './SalesOrderLinesSection';

const NO_ADDRESS = 'none';
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

export function SalesOrderForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        options,
        totals,
        selectClient,
        selectPriceList,
    } = useSalesOrderFormContext();

    const client = options.clients.find(
        (candidate) => candidate.id === data.client_id,
    );

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="Cabecera"
                        sub="Cliente, bodega de despacho y fechas del pedido"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Cliente *
                            </Label>
                            <Select
                                value={data.client_id}
                                onValueChange={selectClient}
                            >
                                <SelectTrigger
                                    className={`h-[42px] w-full rounded-[10px] ${errors.client_id ? 'border-bad' : ''}`}
                                >
                                    <SelectValue placeholder="Selecciona un cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.clients.map((option) => (
                                        <SelectItem
                                            key={option.id}
                                            value={option.id}
                                        >
                                            {option.code
                                                ? `${option.code} — `
                                                : ''}
                                            {option.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {client?.credit_blocked === 'yes' && (
                                <span className="text-[12px] font-semibold text-warn">
                                    Este cliente tiene el crédito bloqueado
                                </span>
                            )}
                            {errors.client_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Dirección de entrega
                            </Label>
                            <Select
                                value={data.client_address_id || NO_ADDRESS}
                                onValueChange={(value) =>
                                    setData(
                                        'client_address_id',
                                        value === NO_ADDRESS ? '' : value,
                                    )
                                }
                                disabled={client === undefined}
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Dirección fiscal del cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={NO_ADDRESS}>
                                        Dirección fiscal del cliente
                                    </SelectItem>
                                    {(client?.addresses ?? []).map(
                                        (address) => (
                                            <SelectItem
                                                key={address.id}
                                                value={address.id}
                                            >
                                                {address.name} —{' '}
                                                {address.address}
                                            </SelectItem>
                                        ),
                                    )}
                                </SelectContent>
                            </Select>
                            {errors.client_address_id && (
                                <p className="text-sm text-bad">
                                    {errors.client_address_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Bodega de despacho *
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
                            <Label className="text-[13px] font-semibold">
                                Vendedor
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
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="order_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha del pedido *
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
                                Fecha comprometida
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

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="client_reference"
                                className="text-[13px] font-semibold"
                            >
                                Orden de compra del cliente
                            </Label>
                            <Input
                                id="client_reference"
                                type="text"
                                value={data.client_reference}
                                onChange={(e) =>
                                    setData('client_reference', e.target.value)
                                }
                                placeholder="Ej. OC-2026-0341"
                                maxLength={60}
                                className="h-[42px] rounded-[10px]"
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Lista de precio
                            </Label>
                            <Select
                                value={data.price_list_id || NO_PRICE_LIST}
                                onValueChange={(value) =>
                                    selectPriceList(
                                        value === NO_PRICE_LIST ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger className="h-[42px] w-full rounded-[10px]">
                                    <SelectValue placeholder="Lista por defecto de la empresa" />
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
                            <span className="text-[12px] text-muted-foreground">
                                Cambiarla revalúa las líneas sin precio pactado
                            </span>
                        </div>

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
                                onValueChange={(value) =>
                                    setData('currency', value)
                                }
                                error={errors.currency}
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
                                Tasa de cambio *
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
                                className="h-[42px] rounded-[10px]"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                0 = contado
                            </span>
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
                                placeholder="Instrucciones de entrega, acuerdos con el cliente…"
                                className="rounded-[10px]"
                                rows={3}
                            />
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Líneas"
                        sub="Artículos pedidos: el precio queda congelado al guardar"
                    />
                    <SalesOrderLinesSection />
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
                        <b className="text-right font-bold">
                            {client?.name ??
                                (mode === 'create' ? 'Sin elegir' : '—')}
                        </b>
                    </div>
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
                            {formatAmount(totals.subtotal)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Descuento
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.discountAmount)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Impuesto
                        </span>
                        <b className="font-bold tabular-nums">
                            {formatAmount(totals.taxAmount)}
                        </b>
                    </div>
                    <div className="flex items-center justify-between border-t pt-2.5 text-[15px]">
                        <span className="font-semibold">Total</span>
                        <b className="font-extrabold tabular-nums">
                            {data.currency} {formatAmount(totals.total)}
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
                          ? 'Crear pedido'
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
