import { Check } from 'lucide-react';
import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { CurrencyInput } from '@/components/ui/currency-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useSupplierAdvanceFormContext } from '../contexts/SupplierAdvanceFormContext';
import {
    PAYMENT_METHOD_LABELS,
    type SupplierAdvancePaymentMethod,
} from '../types/SupplierAdvance';

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

const PAYMENT_METHOD_OPTIONS: OptionType[] = (
    Object.keys(PAYMENT_METHOD_LABELS) as SupplierAdvancePaymentMethod[]
).map((method) => ({ value: method, label: PAYMENT_METHOD_LABELS[method] }));

/** Uno de los dos indicadores del proveedor elegido. */
function BalanceCard({
    label,
    hint,
    amount,
    currency,
}: {
    label: string;
    hint: string;
    amount: number;
    currency: string;
}) {
    return (
        <div className="flex flex-col gap-1 rounded-[12px] border bg-muted px-4 py-3">
            <span className="text-[12px] font-semibold text-muted-foreground">
                {label}
            </span>
            <b className="text-[17px] font-extrabold tabular-nums">
                <AmountDual amount={amount} currency={currency} />
            </b>
            <span className="text-[11.5px] text-muted-foreground">{hint}</span>
        </div>
    );
}

export function SupplierAdvanceForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        balances,
        supplierLookupUrl,
        supplierOption,
        selectSupplier,
        purchaseOrderLookupUrl,
        purchaseOrderOption,
        selectPurchaseOrder,
        selectCurrency,
    } = useSupplierAdvanceFormContext();

    return (
        <form
            onSubmit={handleSubmit}
            className="grid grid-cols-1 items-start gap-5 xl:grid-cols-[1fr_320px]"
        >
            <div className="flex min-w-0 flex-col gap-5">
                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={1}
                        title="A quién se le adelanta"
                        sub="El proveedor y, si la hay, la orden que lo motiva"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="supplier_id"
                                className="text-[13px] font-semibold"
                            >
                                Proveedor *
                            </Label>
                            <Select2Ajax
                                inputId="supplier_id"
                                url={supplierLookupUrl}
                                value={supplierOption}
                                onChange={selectSupplier}
                                error={!!errors.supplier_id}
                                size="md"
                                placeholder="Busca un proveedor"
                            />
                            {errors.supplier_id && (
                                <p className="text-sm text-bad">
                                    {errors.supplier_id}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="purchase_order_id"
                                className="text-[13px] font-semibold"
                            >
                                Orden de compra
                            </Label>
                            <Select2Ajax
                                inputId="purchase_order_id"
                                url={purchaseOrderLookupUrl}
                                params={
                                    data.supplier_id
                                        ? { supplier_id: data.supplier_id }
                                        : undefined
                                }
                                value={purchaseOrderOption}
                                onChange={selectPurchaseOrder}
                                error={!!errors.purchase_order_id}
                                isClearable
                                size="md"
                                placeholder="Sin orden asociada"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Opcional: hay anticipos que no nacen de una
                                orden
                            </span>
                            {errors.purchase_order_id && (
                                <p className="text-sm text-bad">
                                    {errors.purchase_order_id}
                                </p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2">
                            <BalanceCard
                                label="Saldo por pagar"
                                hint="Suma de sus facturas abiertas"
                                amount={balances.payable}
                                currency={data.currency}
                            />
                            <BalanceCard
                                label="Crédito a favor"
                                hint="Anticipos entregados y sin aplicar"
                                amount={balances.credit}
                                currency={data.currency}
                            />
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Datos del anticipo"
                        sub="Cuánto se adelanta, cuándo, por qué vía y con qué tasa"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="advance_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha del anticipo *
                            </Label>
                            <Input
                                id="advance_date"
                                type="date"
                                value={data.advance_date}
                                onChange={(e) =>
                                    setData('advance_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.advance_date ? 'border-bad' : ''}`}
                            />
                            {errors.advance_date && (
                                <p className="text-sm text-bad">
                                    {errors.advance_date}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Forma de pago *
                            </Label>
                            <Select2
                                inputId="payment_method"
                                options={PAYMENT_METHOD_OPTIONS}
                                value={
                                    PAYMENT_METHOD_OPTIONS.find(
                                        (option) =>
                                            option.value ===
                                            data.payment_method,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData(
                                        'payment_method',
                                        (option?.value ??
                                            'transfer') as SupplierAdvancePaymentMethod,
                                    )
                                }
                                error={!!errors.payment_method}
                                size="md"
                            />
                            {errors.payment_method && (
                                <p className="text-sm text-bad">
                                    {errors.payment_method}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="reference"
                                className="text-[13px] font-semibold"
                            >
                                Referencia
                            </Label>
                            <Input
                                id="reference"
                                type="text"
                                value={data.reference}
                                onChange={(e) =>
                                    setData('reference', e.target.value)
                                }
                                maxLength={60}
                                placeholder="Número de transferencia o cheque"
                                className={`h-[42px] rounded-[10px] ${errors.reference ? 'border-bad' : ''}`}
                            />
                            {errors.reference && (
                                <p className="text-sm text-bad">
                                    {errors.reference}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="bank_account"
                                className="text-[13px] font-semibold"
                            >
                                Cuenta bancaria
                            </Label>
                            <Input
                                id="bank_account"
                                type="text"
                                value={data.bank_account}
                                onChange={(e) =>
                                    setData('bank_account', e.target.value)
                                }
                                maxLength={60}
                                placeholder="Cuenta desde la que se pagó"
                                className={`h-[42px] rounded-[10px] ${errors.bank_account ? 'border-bad' : ''}`}
                            />
                            {errors.bank_account && (
                                <p className="text-sm text-bad">
                                    {errors.bank_account}
                                </p>
                            )}
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
                                error={errors.currency}
                                onValueChange={selectCurrency}
                            />
                            {errors.currency && (
                                <p className="text-sm text-bad">
                                    {errors.currency}
                                </p>
                            )}
                        </div>

                        <ExchangeRateField
                            value={data.exchange_rate}
                            onValueChange={(value) =>
                                setData('exchange_rate', value)
                            }
                            currency={data.currency}
                            dateLabel="la fecha del anticipo"
                            error={errors.exchange_rate}
                        />

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="amount"
                                className="text-[13px] font-semibold"
                            >
                                Monto entregado *
                            </Label>
                            <CurrencyInput
                                id="amount"
                                value={data.amount}
                                onValueChange={(value) =>
                                    setData('amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.amount ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Se entrega al confirmar el pago que genera
                                aprobarlo
                            </span>
                            {errors.amount && (
                                <p className="text-sm text-bad">
                                    {errors.amount}
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
                    <div className="flex items-center justify-between text-[15px]">
                        <span className="font-semibold">
                            Monto del anticipo
                        </span>
                        <b className="font-extrabold tabular-nums">
                            <AmountDual
                                amount={data.amount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <p className="text-[12.5px] leading-relaxed text-muted-foreground">
                        Guardarlo no mueve dinero. Aprobarlo genera el pago que
                        lo entrega, y confirmar ese pago es lo que le da crédito
                        al proveedor.
                    </p>
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
                          ? 'Registrar anticipo'
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
