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
import { useClientCollectionFormContext } from '../contexts/ClientCollectionFormContext';
import {
    ORIGIN_TYPE_LABELS,
    PAYMENT_METHOD_LABELS,
    type ClientCollectionMethod,
    type ClientCollectionOptions,
    type ClientCollectionOriginType,
} from '../types/ClientCollection';
import { ClientCollectionApplicationsSection } from './ClientCollectionApplicationsSection';

interface ClientCollectionFormProps {
    options: ClientCollectionOptions;
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

/**
 * Desde dónde arranca el cobro. `advance` no está aquí: ese cobro nace al
 * aprobar un anticipo y esta pantalla solo lo muestra.
 */
const ORIGIN_OPTIONS: OptionType[] = [
    { value: 'client', label: ORIGIN_TYPE_LABELS.client },
    { value: 'invoice', label: ORIGIN_TYPE_LABELS.invoice },
];

const PAYMENT_METHOD_OPTIONS: OptionType[] = (
    ['cash', 'transfer', 'check', 'card', 'other'] as ClientCollectionMethod[]
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

export function ClientCollectionForm({ options }: ClientCollectionFormProps) {
    const {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        mode,
        totals,
        balances,
        clientLookupUrl,
        clientOption,
        selectClient,
        originInvoiceLookupUrl,
        originInvoiceOption,
        selectOriginInvoice,
        selectOriginType,
        selectPaymentMethod,
        selectCurrency,
    } = useClientCollectionFormContext();

    /** El origen se congela al crear el cobro: en edición solo se muestra. */
    const originLocked = mode === 'edit';

    const collectorOptions: OptionType[] = options.collectors.map(
        (collector) => ({ value: collector.id, label: collector.name }),
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
                        title="Origen del cobro"
                        sub="Desde dónde arranca y quién paga"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Aplicar cobro a *
                            </Label>
                            <Select2
                                inputId="origin_type"
                                options={ORIGIN_OPTIONS}
                                value={
                                    ORIGIN_OPTIONS.find(
                                        (option) =>
                                            option.value === data.origin_type,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    selectOriginType(
                                        (option?.value ??
                                            'client') as ClientCollectionOriginType,
                                    )
                                }
                                error={!!errors.origin_type}
                                isDisabled={originLocked}
                                size="md"
                            />
                            <span className="text-[12px] text-muted-foreground">
                                {originLocked
                                    ? 'El origen se congeló al registrar el cobro'
                                    : 'Por cliente reparte entre todas sus facturas'}
                            </span>
                            {errors.origin_type && (
                                <p className="text-sm text-bad">
                                    {errors.origin_type}
                                </p>
                            )}
                        </div>

                        {data.origin_type === 'invoice' ? (
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="origin_id"
                                    className="text-[13px] font-semibold"
                                >
                                    Factura *
                                </Label>
                                <Select2Ajax
                                    inputId="origin_id"
                                    url={originInvoiceLookupUrl}
                                    params={{ open: 'yes' }}
                                    value={originInvoiceOption}
                                    onChange={selectOriginInvoice}
                                    error={!!errors.origin_id}
                                    isDisabled={originLocked}
                                    isClearable
                                    size="md"
                                    placeholder="Busca una factura con saldo"
                                />
                                <span className="text-[12px] text-muted-foreground">
                                    Fija el cliente y precarga su saldo
                                </span>
                                {errors.origin_id && (
                                    <p className="text-sm text-bad">
                                        {errors.origin_id}
                                    </p>
                                )}
                            </div>
                        ) : (
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
                                    params={{ with_balance: 'yes' }}
                                    value={clientOption}
                                    onChange={selectClient}
                                    error={!!errors.client_id}
                                    size="md"
                                    placeholder="Busca un cliente con saldo"
                                />
                                {errors.client_id && (
                                    <p className="text-sm text-bad">
                                        {errors.client_id}
                                    </p>
                                )}
                            </div>
                        )}

                        <div className="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2">
                            <BalanceCard
                                label="Saldo por cobrar"
                                hint="Suma de sus facturas abiertas"
                                amount={balances.receivable}
                                currency={data.currency}
                            />
                            <BalanceCard
                                label="Crédito a favor"
                                hint="Anticipos disponibles del cliente"
                                amount={balances.credit}
                                currency={data.currency}
                            />
                        </div>
                    </div>
                </Card>

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={2}
                        title="Datos del cobro"
                        sub="Cuánto entró, cuándo, por qué vía y con qué tasa"
                    />
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="collection_date"
                                className="text-[13px] font-semibold"
                            >
                                Fecha del cobro *
                            </Label>
                            <Input
                                id="collection_date"
                                type="date"
                                value={data.collection_date}
                                onChange={(e) =>
                                    setData('collection_date', e.target.value)
                                }
                                className={`h-[42px] rounded-[10px] ${errors.collection_date ? 'border-bad' : ''}`}
                            />
                            {errors.collection_date && (
                                <p className="text-sm text-bad">
                                    {errors.collection_date}
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
                                            option.value ===
                                            data.payment_method,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    selectPaymentMethod(
                                        (option?.value ??
                                            'cash') as ClientCollectionMethod,
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
                                placeholder="Número de transferencia o voucher"
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

                        {data.payment_method === 'check' && (
                            <>
                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="check_number"
                                        className="text-[13px] font-semibold"
                                    >
                                        Número de cheque *
                                    </Label>
                                    <Input
                                        id="check_number"
                                        type="text"
                                        value={data.check_number}
                                        onChange={(e) =>
                                            setData(
                                                'check_number',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={30}
                                        className={`h-[42px] rounded-[10px] ${errors.check_number ? 'border-bad' : ''}`}
                                    />
                                    {errors.check_number && (
                                        <p className="text-sm text-bad">
                                            {errors.check_number}
                                        </p>
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label
                                        htmlFor="check_date"
                                        className="text-[13px] font-semibold"
                                    >
                                        Fecha de cobro del cheque
                                    </Label>
                                    <Input
                                        id="check_date"
                                        type="date"
                                        value={data.check_date}
                                        onChange={(e) =>
                                            setData(
                                                'check_date',
                                                e.target.value,
                                            )
                                        }
                                        className={`h-[42px] rounded-[10px] ${errors.check_date ? 'border-bad' : ''}`}
                                    />
                                    <span className="text-[12px] text-muted-foreground">
                                        Para cheques posfechados
                                    </span>
                                    {errors.check_date && (
                                        <p className="text-sm text-bad">
                                            {errors.check_date}
                                        </p>
                                    )}
                                </div>
                            </>
                        )}

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Cobrador
                            </Label>
                            <Select2
                                inputId="collected_by"
                                options={collectorOptions}
                                value={
                                    collectorOptions.find(
                                        (option) =>
                                            option.value === data.collected_by,
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    setData('collected_by', option?.value ?? '')
                                }
                                error={!!errors.collected_by}
                                isClearable
                                size="md"
                                placeholder="Quién recibió el dinero"
                            />
                            {errors.collected_by && (
                                <p className="text-sm text-bad">
                                    {errors.collected_by}
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
                            dateLabel="la fecha del cobro"
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
                                onValueChange={(value) =>
                                    setData('amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.amount ? 'border-bad' : ''}`}
                            />
                            {errors.amount && (
                                <p className="text-sm text-bad">
                                    {errors.amount}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label
                                htmlFor="withholding_amount"
                                className="text-[13px] font-semibold"
                            >
                                Retención
                            </Label>
                            <CurrencyInput
                                id="withholding_amount"
                                value={data.withholding_amount}
                                onValueChange={(value) =>
                                    setData('withholding_amount', value)
                                }
                                min={0}
                                decimals={2}
                                className={`h-[42px] rounded-[10px] ${errors.withholding_amount ? 'border-bad' : ''}`}
                            />
                            <span className="text-[12px] text-muted-foreground">
                                Cancela deuda aunque no entre en caja
                            </span>
                            {errors.withholding_amount && (
                                <p className="text-sm text-bad">
                                    {errors.withholding_amount}
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

                <Card className="gap-0 overflow-hidden rounded-2xl py-0">
                    <FormSectionHead
                        step={3}
                        title="Reparto entre facturas"
                        sub="Cuánto se le abona a cada factura abierta"
                    />
                    <ClientCollectionApplicationsSection />
                </Card>
            </div>

            <Card className="gap-3.5 rounded-2xl p-5 xl:sticky xl:top-[86px]">
                <div className="text-base font-bold tracking-tight">
                    Resumen
                </div>
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Monto recibido
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={data.amount}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    {data.withholding_amount > 0 && (
                        <div className="flex items-center justify-between text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Retención
                            </span>
                            <b className="font-bold tabular-nums">
                                <AmountDual
                                    amount={data.withholding_amount}
                                    currency={data.currency}
                                    rate={data.exchange_rate || undefined}
                                    className="items-end"
                                />
                            </b>
                        </div>
                    )}
                    <div className="flex items-center justify-between border-t pt-2.5 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Aplicado a facturas
                        </span>
                        <b className="font-bold tabular-nums">
                            <AmountDual
                                amount={totals.applied}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
                        </b>
                    </div>
                    <div className="flex items-center justify-between text-[15px]">
                        <span className="font-semibold">Sin aplicar</span>
                        <b className="font-extrabold tabular-nums">
                            <AmountDual
                                amount={totals.unapplied}
                                currency={data.currency}
                                rate={data.exchange_rate || undefined}
                                className="items-end"
                            />
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
                          ? 'Registrar cobro'
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
