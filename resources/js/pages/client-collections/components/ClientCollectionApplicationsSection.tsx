import { Split } from 'lucide-react';
import { AmountDual } from '@/components/amount-dual';
import { Button } from '@/components/ui/button';
import { CurrencyInput } from '@/components/ui/currency-input';
import { useClientCollectionFormContext } from '../contexts/ClientCollectionFormContext';

/**
 * El reparto del cobro entre las facturas abiertas del cliente.
 *
 * No es una tabla de líneas que se agregan y se quitan: las filas son las
 * facturas que el cliente tiene con saldo, y lo único que se captura es cuánto
 * se le abona a cada una.
 */
export function ClientCollectionApplicationsSection() {
    const {
        data,
        errors,
        invoices,
        loadingInvoices,
        totals,
        appliedTo,
        applyToInvoice,
        distributeRemaining,
    } = useClientCollectionFormContext();

    /** El error de una fila llega indexado por su posición en el payload. */
    const rowError = (invoiceId: string): string | undefined => {
        const index = data.applications.findIndex(
            (row) => row.sales_invoice_id === invoiceId,
        );

        if (index < 0) {
            return undefined;
        }

        const fields = errors as Record<string, string | undefined>;

        return (
            fields[`applications.${index}.applied_amount`] ??
            fields[`applications.${index}.sales_invoice_id`]
        );
    };

    if (data.client_id === '') {
        return (
            <div className="p-8 text-center text-sm text-muted-foreground">
                Elige antes el cliente: el reparto se arma con sus facturas
                abiertas.
            </div>
        );
    }

    if (loadingInvoices) {
        return (
            <div className="flex flex-col gap-3 p-5">
                {[0, 1, 2].map((row) => (
                    <div
                        key={row}
                        className="h-[52px] animate-pulse rounded-[10px] bg-muted"
                    />
                ))}
            </div>
        );
    }

    if (invoices.length === 0) {
        return (
            <div className="p-8 text-center text-sm text-muted-foreground">
                Este cliente no tiene facturas con saldo. El cobro quedará
                entero sin aplicar.
            </div>
        );
    }

    return (
        <div className="flex flex-col">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-3">
                <span className="text-[13px] text-muted-foreground">
                    {invoices.length} factura{invoices.length !== 1 ? 's' : ''}{' '}
                    con saldo
                </span>
                <Button
                    type="button"
                    variant="outline"
                    className="h-9 rounded-[10px] bg-card px-3 text-[13px] font-semibold"
                    onClick={distributeRemaining}
                >
                    <Split className="size-4" />
                    Repartir de la más vieja
                </Button>
            </div>

            <div className="hidden h-11 items-center gap-3.5 border-b bg-muted px-5 lg:grid lg:grid-cols-[1.6fr_1fr_1fr_1fr_1.2fr]">
                {['Factura', 'Vence', 'Total', 'Saldo', 'A aplicar'].map(
                    (header) => (
                        <div
                            key={header}
                            className="text-[11.5px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            {header}
                        </div>
                    ),
                )}
            </div>

            <div className="flex flex-col">
                {invoices.map((invoice) => {
                    const error = rowError(invoice.id);

                    return (
                        <div
                            key={invoice.id}
                            className="grid gap-3.5 border-b px-5 py-3 last:border-b-0 lg:grid-cols-[1.6fr_1fr_1fr_1fr_1.2fr] lg:items-center"
                        >
                            <div className="flex min-w-0 flex-col">
                                <span className="truncate font-bold">
                                    {invoice.code}
                                </span>
                                <span className="truncate text-[12.5px] text-muted-foreground">
                                    {invoice.invoiceNumber}
                                </span>
                            </div>
                            <div className="text-[13.5px] text-muted-foreground tabular-nums">
                                {invoice.dueDate ?? '—'}
                            </div>
                            <div className="text-[13.5px] tabular-nums">
                                <AmountDual
                                    amount={invoice.total}
                                    currency={invoice.currency}
                                    rate={invoice.exchangeRate}
                                />
                            </div>
                            <div className="text-[13.5px] font-semibold tabular-nums">
                                <AmountDual
                                    amount={invoice.balance}
                                    currency={invoice.currency}
                                    rate={invoice.exchangeRate}
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <CurrencyInput
                                    value={appliedTo(invoice.id)}
                                    onValueChange={(value) =>
                                        applyToInvoice(invoice.id, value)
                                    }
                                    min={0}
                                    max={Number(invoice.balance)}
                                    decimals={2}
                                    aria-label={`Monto a aplicar a ${invoice.code}`}
                                    className={`h-[42px] rounded-[10px] ${error ? 'border-bad' : ''}`}
                                />
                                {error && (
                                    <p className="text-sm text-bad">{error}</p>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {totals.unapplied > 0 && (
                <p className="border-t px-5 py-3 text-[12.5px] text-muted-foreground">
                    Lo que quede sin aplicar queda registrado como excedente del
                    cobro, a favor del cliente.
                </p>
            )}

            {typeof (errors as Record<string, string | undefined>)
                .applications === 'string' && (
                <p className="border-t px-5 py-3 text-sm text-bad">
                    {(errors as Record<string, string>).applications}
                </p>
            )}
        </div>
    );
}
