import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import { useRemoteOption } from '@/hooks/use-remote-option';
import { useTodayRates } from '@/hooks/use-today-rates';
import { generateUUID } from '@/lib/utils';
import purchaseOrders from '@/routes/purchase-orders';
import supplierAdvances from '@/routes/supplier-advances';
import suppliers from '@/routes/suppliers';
import type {
    PurchaseOrderOptionMeta,
    SupplierAdvance,
    SupplierAdvancePaymentMethod,
    SupplierOptionMeta,
} from '../types/SupplierAdvance';

interface UseSupplierAdvanceFormProps {
    mode: 'create' | 'edit';
    initialData?: SupplierAdvance;
    onSuccess?: () => void;
}

interface SupplierAdvanceFormData {
    id: string;
    supplier_id: string;
    purchase_order_id: string;
    advance_date: string;
    payment_method: SupplierAdvancePaymentMethod;
    reference: string;
    bank_account: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha del anticipo.
     */
    exchange_rate: string;
    amount: number;
    notes: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Los dos indicadores del proveedor que la pantalla muestra al elegirlo. */
export interface SupplierBalances {
    /** Suma de lo que deben sus facturas abiertas. */
    payable: number;
    /** Lo que ya se le adelantó y todavía no se ha aplicado. */
    credit: number;
}

/**
 * El proveedor del anticipo que se edita, con la etiqueta que trae su Resource:
 * así el select lo muestra desde el primer render.
 */
function supplierSeed(advance?: SupplierAdvance): AjaxOption | null {
    if (!advance?.supplier_id) {
        return null;
    }

    const name = advance.supplier_name ?? '';

    return {
        value: advance.supplier_id,
        label: advance.supplier_code
            ? `${advance.supplier_code} · ${name}`
            : name,
    };
}

/** La orden que motiva el anticipo que se edita, si nació de una. */
function purchaseOrderSeed(advance?: SupplierAdvance): AjaxOption | null {
    if (!advance?.purchase_order_id) {
        return null;
    }

    return {
        value: advance.purchase_order_id,
        label: advance.purchase_order_code ?? 'Orden de compra',
    };
}

export function useSupplierAdvanceForm({
    mode,
    initialData,
    onSuccess,
}: UseSupplierAdvanceFormProps) {
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

    /** Y las órdenes contra el suyo, acotadas a las del proveedor elegido. */
    const purchaseOrder = useRemoteOption({
        url: purchaseOrders.lookup(companyId).url,
        seed: purchaseOrderSeed(initialData),
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar el anticipo antes
     * de guardarlo. Prohibida la corrección, viaja vacía y la resuelve el
     * backend.
     */
    const catalogRate = (currency: string): string => {
        if (configuration?.allows_rate_override !== 'yes') {
            return '';
        }

        const rate = todayRates[currency];

        return rate === undefined ? '' : String(rate);
    };

    const { data, setData, post, put, transform, processing, errors, reset } =
        useForm<SupplierAdvanceFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            purchase_order_id: initialData?.purchase_order_id ?? '',
            advance_date:
                initialData?.advance_date ??
                new Date().toISOString().slice(0, 10),
            payment_method: initialData?.payment_method ?? 'transfer',
            reference: initialData?.reference ?? '',
            bank_account: initialData?.bank_account ?? '',
            /** Un anticipo nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            amount: Number(initialData?.amount ?? 0),
            notes: initialData?.notes ?? '',
        });

    /**
     * Cambiar la moneda trae la tasa del catálogo de esa moneda. El monto ya
     * capturado no se toca: es el que se le va a entregar al proveedor.
     */
    const selectCurrency = (currency: string) =>
        setData((current) => ({
            ...current,
            currency,
            exchange_rate: catalogRate(currency),
        }));

    /**
     * Elegir el proveedor arrastra su moneda y descarta la orden anterior: era
     * de otro proveedor, y un anticipo no cruza proveedores.
     */
    const selectSupplier = (option: AjaxOption | null) => {
        supplier.select(option);
        purchaseOrder.select(null);

        setData((current) => ({
            ...current,
            supplier_id: option?.value ?? '',
            purchase_order_id: '',
        }));

        const meta = (option?.meta ?? {}) as Partial<SupplierOptionMeta>;

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    /** Elegir la orden arrastra su moneda: el anticipo se entrega en la misma. */
    const selectPurchaseOrder = (option: AjaxOption | null) => {
        purchaseOrder.select(option);

        const meta = (option?.meta ?? {}) as Partial<PurchaseOrderOptionMeta>;

        setData((current) => ({
            ...current,
            purchase_order_id: option?.value ?? '',
            supplier_id: meta.supplier_id ?? current.supplier_id,
        }));

        if (meta.currency) {
            selectCurrency(meta.currency);
        }
    };

    const balances: SupplierBalances = (() => {
        const meta = (supplier.optionOf(data.supplier_id)?.meta ??
            {}) as Partial<SupplierOptionMeta>;

        return {
            payable: Number(meta.current_balance ?? 0),
            credit: Number(meta.advance_balance ?? 0),
        };
    })();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así un anticipo con fecha anterior se
         * sigue valorando con la tasa de su día y no con la de hoy.
         */
        transform((payload) => ({
            ...payload,
            exchange_rate:
                payload.exchange_rate === catalogRate(payload.currency)
                    ? ''
                    : payload.exchange_rate,
        }));

        if (mode === 'create') {
            post(supplierAdvances.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                supplierAdvances.update({
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
        balances,
        supplierLookupUrl: supplier.url,
        supplierOption: supplier.optionOf(data.supplier_id),
        selectSupplier,
        purchaseOrderLookupUrl: purchaseOrder.url,
        purchaseOrderOption: purchaseOrder.optionOf(data.purchase_order_id),
        selectPurchaseOrder,
        selectCurrency,
    };
}
