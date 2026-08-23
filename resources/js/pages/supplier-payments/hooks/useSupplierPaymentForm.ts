import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import purchaseInvoices from '@/routes/purchase-invoices';
import supplierPayments from '@/routes/supplier-payments';
import suppliers from '@/routes/suppliers';
import type {
    PurchaseInvoiceOptionMeta,
    SupplierOptionMeta,
    SupplierPayment,
    SupplierPaymentMethod,
    SupplierPaymentOriginType,
} from '../types/SupplierPayment';
import { useSupplierOpenInvoices } from './useSupplierOpenInvoices';

interface UseSupplierPaymentFormProps {
    mode: 'create' | 'edit';
    initialData?: SupplierPayment;
    onSuccess?: () => void;
}

/** Una fila del reparto tal como se captura: qué factura y por cuánto. */
export interface SupplierPaymentApplicationRow {
    purchase_invoice_id: string;
    applied_amount: number;
}

interface SupplierPaymentFormData {
    id: string;
    supplier_id: string;
    origin_type: SupplierPaymentOriginType;
    origin_id: string;
    payment_date: string;
    payment_method: SupplierPaymentMethod;
    reference: string;
    bank_account: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha del pago.
     */
    exchange_rate: string;
    amount: number;
    withholding_amount: number;
    notes: string;
    applications: SupplierPaymentApplicationRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Los dos indicadores del proveedor que la pantalla muestra junto al origen. */
export interface SupplierBalances {
    /** Suma de lo que deben sus facturas abiertas. */
    payable: number;
    /**
     * Lo aplicable sin desembolsar dinero: sus anticipos disponibles. Las
     * notas de crédito se sumarán aquí cuando el módulo `NCP` las lleve.
     */
    credit: number;
}

export interface SupplierPaymentTotals {
    /** Suma del reparto entre facturas. */
    applied: number;
    /** Lo que el pago puede saldar: lo entregado más lo retenido. */
    capacity: number;
    /** Excedente sin aplicar. Al confirmar se vuelve un anticipo. */
    unapplied: number;
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

/**
 * El proveedor del pago que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render.
 */
function supplierSeed(payment?: SupplierPayment): AjaxOption | null {
    if (!payment?.supplier_id) {
        return null;
    }

    const name = payment.supplier_name ?? '';

    return {
        value: payment.supplier_id,
        label: payment.supplier_code
            ? `${payment.supplier_code} · ${name}`
            : name,
    };
}

/** La factura de origen del pago que se edita, si nació de una. */
function originInvoiceSeed(payment?: SupplierPayment): AjaxOption | null {
    if (payment?.origin_type !== 'invoice' || !payment.origin_id) {
        return null;
    }

    const applied = (payment.applications ?? []).find(
        (application) => application.purchase_invoice_id === payment.origin_id,
    );

    return {
        value: payment.origin_id,
        label: applied?.purchase_invoice_code ?? 'Factura de compra',
    };
}

/** El reparto guardado, sin las filas que ya se revirtieron. */
function applicationRows(
    payment?: SupplierPayment,
): SupplierPaymentApplicationRow[] {
    return (payment?.applications ?? [])
        .filter((application) => application.status === 'active')
        .map((application) => ({
            purchase_invoice_id: application.purchase_invoice_id,
            applied_amount: Number(application.applied_amount),
        }));
}

export function useSupplierPaymentForm({
    mode,
    initialData,
    onSuccess,
}: UseSupplierPaymentFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /** El padrón de proveedores se busca contra su endpoint de opciones. */
    const supplier = useRemoteOption({
        url: suppliers.lookup(companyId).url,
        seed: supplierSeed(initialData),
        /** De su `meta` salen los dos indicadores, así que hay que hidratarlo. */
        hydrate: true,
    });

    /** Y las facturas contra el suyo, acotadas a las que siguen debiendo. */
    const originInvoice = useRemoteOption({
        url: purchaseInvoices.lookup(companyId).url,
        seed: originInvoiceSeed(initialData),
        hydrate: true,
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar el pago antes de
     * guardarlo. Prohibida la corrección, viaja vacía y la resuelve el backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<SupplierPaymentFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            origin_type: initialData?.origin_type ?? 'supplier',
            origin_id: initialData?.origin_id ?? '',
            payment_date:
                initialData?.payment_date ??
                new Date().toISOString().slice(0, 10),
            payment_method: initialData?.payment_method ?? 'transfer',
            reference: initialData?.reference ?? '',
            bank_account: initialData?.bank_account ?? '',
            /** Un pago nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            amount: Number(initialData?.amount ?? 0),
            withholding_amount: Number(initialData?.withholding_amount ?? 0),
            notes: initialData?.notes ?? '',
            applications: applicationRows(initialData),
        });

    /** El reparto se arma sobre las facturas abiertas del proveedor elegido. */
    const { invoices, loading: loadingInvoices } = useSupplierOpenInvoices(
        companyId,
        data.supplier_id,
    );

    /**
     * Cambiar la moneda del pago trae la tasa del catálogo de esa moneda. El
     * monto ya capturado no se toca: es el que salió del banco.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el proveedor arrastra su moneda y descarta el reparto anterior:
     * era de las facturas de otro, y un pago no cruza proveedores.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            applications: [],
        }));

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Elegir la factura de origen fija el proveedor y precarga esa factura en
     * el reparto por lo que debe; el usuario puede sumarle otras del mismo.
     */
    const selectOriginInvoice = (option: AjaxOption | null) => {
        originInvoice.select(option);

        const meta = (option?.meta ?? {}) as Partial<PurchaseInvoiceOptionMeta>;

        setData((current) => ({
            ...current,
            origin_id: option?.value ?? '',
            supplier_id: meta.supplier_id ?? '',
            applications: option
                ? [
                      {
                          purchase_invoice_id: option.value,
                          applied_amount: Number(meta.balance ?? 0),
                      },
                  ]
                : [],
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /**
     * Cambiar de dónde arranca el pago vacía lo que dependía del origen
     * anterior: el proveedor, la factura y el reparto.
     */
    const selectOriginType = (originType: SupplierPaymentOriginType) => {
        supplier.select(null);
        originInvoice.select(null);

        setData((current) => ({
            ...current,
            origin_type: originType,
            origin_id: '',
            supplier_id: '',
            applications: [],
        }));
    };

    /** Lo que el reparto ya destina a una factura. */
    const appliedTo = (invoiceId: string): number =>
        data.applications.find((row) => row.purchase_invoice_id === invoiceId)
            ?.applied_amount ?? 0;

    /**
     * Escribir cuánto se le abona a una factura. Un cero la saca del reparto:
     * una fila en cero no aporta nada y el backend la rechaza.
     */
    const applyToInvoice = (invoiceId: string, amount: number) =>
        setData(
            'applications',
            amount > 0
                ? [
                      ...data.applications.filter(
                          (row) => row.purchase_invoice_id !== invoiceId,
                      ),
                      {
                          purchase_invoice_id: invoiceId,
                          applied_amount: amount,
                      },
                  ]
                : data.applications.filter(
                      (row) => row.purchase_invoice_id !== invoiceId,
                  ),
        );

    /**
     * Reparte lo que queda del pago entre las facturas abiertas, de la más
     * vieja a la más nueva: es como se salda una cuenta por pagar.
     */
    const distributeRemaining = () => {
        let left = round2(data.amount + data.withholding_amount);

        setData(
            'applications',
            invoices.reduce<SupplierPaymentApplicationRow[]>(
                (rows, invoice) => {
                    const amount = Math.min(left, Number(invoice.balance));

                    if (amount <= 0) {
                        return rows;
                    }

                    left = round2(left - amount);

                    return [
                        ...rows,
                        {
                            purchase_invoice_id: invoice.id,
                            applied_amount: round2(amount),
                        },
                    ];
                },
                [],
            ),
        );
    };

    const balances: SupplierBalances = (() => {
        const meta = (supplier.optionOf(data.supplier_id)?.meta ??
            {}) as Partial<SupplierOptionMeta>;

        return {
            payable: Number(meta.current_balance ?? 0),
            credit: Number(meta.advance_balance ?? 0),
        };
    })();

    const applied = round2(
        data.applications.reduce((total, row) => total + row.applied_amount, 0),
    );

    const capacity = round2(data.amount + data.withholding_amount);

    const totals: SupplierPaymentTotals = {
        applied,
        capacity,
        unapplied: round2(capacity - applied),
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así un pago con fecha anterior se sigue
         * valorando con la tasa de su día y no con la de hoy.
         */
        transform((payload) => ({
            ...payload,
            exchange_rate:
                payload.exchange_rate === catalogRate(payload.currency)
                    ? ''
                    : payload.exchange_rate,
        }));

        if (mode === 'create') {
            post(supplierPayments.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                supplierPayments.update({
                    company: companyId,
                    id: initialData.id,
                }).url,
                { onSuccess },
            );
        }
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        mode,
        totals,
        balances,
        invoices,
        loadingInvoices,
        supplierLookupUrl: supplier.url,
        supplierOption: supplier.optionOf(data.supplier_id),
        selectSupplier,
        originInvoiceLookupUrl: originInvoice.url,
        originInvoiceOption: originInvoice.optionOf(data.origin_id),
        selectOriginInvoice,
        selectOriginType,
        selectCurrency,
        appliedTo,
        applyToInvoice,
        distributeRemaining,
    };
}
