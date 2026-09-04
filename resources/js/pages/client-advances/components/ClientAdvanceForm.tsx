import { AmountDual } from '@/components/amount-dual';
import { CurrencySelect } from '@/components/currency-select';
import { ExchangeRateField } from '@/components/exchange-rate-field';
import { Select2Ajax } from '@/components/select2-ajax';
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
import { Select2, type OptionType } from '@/components/ui/select2';
import { Textarea } from '@/components/ui/textarea';
import { useClientAdvanceFormContext } from '../contexts/ClientAdvanceFormContext';
import {
    PAYMENT_METHOD_LABELS,
    type ClientAdvancePaymentMethod,
} from '../types/ClientAdvance';

const PAYMENT_METHOD_OPTIONS: OptionType[] = (
    Object.keys(PAYMENT_METHOD_LABELS) as ClientAdvancePaymentMethod[]
).map((method) => ({ value: method, label: PAYMENT_METHOD_LABELS[method] }));

/** Uno de los dos indicadores del cliente elegido. */
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

export function ClientAdvanceForm() {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        balances,
        clientLookupUrl,
        clientOption,
        selectClient,
        salesOrderLookupUrl,
        salesOrderOption,
        selectSalesOrder,
        selectCurrency,
    } = useClientAdvanceFormContext();

    return (
        <FormLayout onSubmit={handleSubmit}>
            <FormSection
                step={1}
                title="Quién adelanta el dinero"
                sub="El cliente y, si lo hay, el pedido que lo motiva"
            >
                <FormFieldGrid>
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="client_id"
                            className="text-[13px] font-semibold"
                        >
                            Cliente *
                        </Label>
                        <Select2Ajax
                            inputId="client_id"
                            url={clientLookupUrl}
                            value={clientOption}
                            onChange={selectClient}
                            error={!!errors.client_id}
                            size="md"
                            placeholder="Busca un cliente"
                        />
                        {errors.client_id && (
                            <p className="text-sm text-bad">
                                {errors.client_id}
                            </p>
                        )}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="sales_order_id"
                            className="text-[13px] font-semibold"
                        >
                            Pedido de venta
                        </Label>
                        <Select2Ajax
                            inputId="sales_order_id"
                            url={salesOrderLookupUrl}
                            params={
                                data.client_id
                                    ? { client_id: data.client_id }
                                    : undefined
                            }
                            value={salesOrderOption}
                            onChange={selectSalesOrder}
                            error={!!errors.sales_order_id}
                            isClearable
                            size="md"
                            placeholder="Sin pedido asociado"
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Opcional: hay anticipos que no nacen de un pedido
                        </span>
                        {errors.sales_order_id && (
                            <p className="text-sm text-bad">
                                {errors.sales_order_id}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2 xl:col-span-3">
                        <BalanceCard
                            label="Saldo por cobrar"
                            hint="Suma de sus facturas abiertas"
                            amount={balances.receivable}
                            currency={data.currency}
                        />
                        <BalanceCard
                            label="Crédito a favor"
                            hint="Anticipos recibidos y sin aplicar"
                            amount={balances.credit}
                            currency={data.currency}
                        />
                    </div>
                </FormFieldGrid>
            </FormSection>

            <FormSection
                step={2}
                title="Datos del anticipo"
                sub="Cuánto se recibe, cuándo, por qué vía y con qué tasa"
            >
                <FormFieldGrid>
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
                            Forma de cobro *
                        </Label>
                        <Select2
                            inputId="payment_method"
                            options={PAYMENT_METHOD_OPTIONS}
                            value={
                                PAYMENT_METHOD_OPTIONS.find(
                                    (option) =>
                                        option.value === data.payment_method,
                                ) ?? null
                            }
                            onChange={(option) =>
                                setData(
                                    'payment_method',
                                    (option?.value ??
                                        'transfer') as ClientAdvancePaymentMethod,
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
                            Cuenta receptora
                        </Label>
                        <Input
                            id="bank_account"
                            type="text"
                            value={data.bank_account}
                            onChange={(e) =>
                                setData('bank_account', e.target.value)
                            }
                            maxLength={60}
                            placeholder="Cuenta en la que entró el dinero"
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
                            Monto recibido *
                        </Label>
                        <CurrencyInput
                            id="amount"
                            value={data.amount}
                            onValueChange={(value) => setData('amount', value)}
                            min={0}
                            decimals={2}
                            className={`h-[42px] rounded-[10px] ${errors.amount ? 'border-bad' : ''}`}
                        />
                        <span className="text-[12px] text-muted-foreground">
                            Entra al confirmar el cobro que genera aprobarlo
                        </span>
                        {errors.amount && (
                            <p className="text-sm text-bad">{errors.amount}</p>
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
                <SummaryRow label="Monto del anticipo" emphasis>
                    <AmountDual
                        amount={data.amount}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-end"
                    />
                </SummaryRow>
                <p className="text-[12.5px] leading-relaxed text-muted-foreground">
                    Guardarlo no mueve dinero. Aprobarlo genera el cobro que lo
                    recibe, y confirmar ese cobro es lo que le da crédito al
                    cliente.
                </p>
            </FormSummary>

            <FormActionBar
                headlineLabel="Monto del anticipo"
                headline={
                    <AmountDual
                        amount={data.amount}
                        currency={data.currency}
                        rate={data.exchange_rate || undefined}
                        className="items-start"
                    />
                }
                processing={processing}
                submitLabel={
                    mode === 'create' ? 'Registrar anticipo' : 'Guardar cambios'
                }
            />
        </FormLayout>
    );
}
