import { useForm, usePage } from '@inertiajs/react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
} from '@/hooks/use-item-catalog';
import { useTodayRates } from '@/hooks/use-today-rates';
import { convertAmount } from '@/lib/money';
import { generateUUID } from '@/lib/utils';
import salesOrders from '@/routes/sales-orders';
import type { TodayRates } from '@/types';
import type { SalesOrder, SalesOrderOptions } from '../types/SalesOrder';

interface UseSalesOrderFormProps {
    mode: 'create' | 'edit';
    options: SalesOrderOptions;
    initialData?: SalesOrder;
    onSuccess?: () => void;
}

export interface SalesOrderLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    unit_price: number;
    list_price: number;
    discount_percent: number;
    tax_percent: number;
    withholding_percent: number;
    notes: string;
}

interface SalesOrderFormData {
    id: string;
    client_id: string;
    client_address_id: string;
    warehouse_id: string;
    price_list_id: string;
    salesperson_id: string;
    order_date: string;
    expected_date: string;
    client_reference: string;
    currency: string;
    /**
     * Corrección manual de la tasa. Vacío —el caso normal— hace que la resuelva
     * el backend con el catálogo a la fecha del pedido.
     */
    exchange_rate: string;
    payment_term_days: number;
    notes: string;
    lines: SalesOrderLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** Importes de una línea. Es el mismo cálculo que hace el repositorio. */
export interface LineAmounts {
    gross: number;
    discountAmount: number;
    subtotal: number;
    taxAmount: number;
    total: number;
}

export function lineAmounts(line: SalesOrderLineRow): LineAmounts {
    const gross = round(line.quantity * line.unit_price, 2);
    const discountAmount = round((gross * line.discount_percent) / 100, 2);
    const subtotal = round(gross - discountAmount, 2);
    const taxAmount = round((subtotal * line.tax_percent) / 100, 2);

    return {
        gross,
        discountAmount,
        subtotal,
        taxAmount,
        total: round(subtotal + taxAmount, 2),
    };
}

function round(value: number, decimals: number): number {
    const factor = 10 ** decimals;

    return Math.round((value + Number.EPSILON) * factor) / factor;
}

function emptyLine(): SalesOrderLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        unit_price: 0,
        list_price: 0,
        discount_percent: 0,
        tax_percent: 0,
        withholding_percent: 0,
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(order?: SalesOrder): SalesOrderLineRow[] {
    const rows = (order?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            unit_price: Number(line.unit_price),
            list_price: Number(line.list_price),
            discount_percent: Number(line.discount_percent),
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

function defaultWarehouseId(options: SalesOrderOptions): string {
    const preferred = options.warehouses.find(
        (warehouse) => warehouse.is_default === 'yes',
    );

    return preferred?.id ?? options.warehouses[0]?.id ?? '';
}

/**
 * Precio del artículo en la lista indicada, reexpresado en la moneda del
 * pedido: cada lista lleva su propia moneda y el pedido puede emitirse en otra.
 *
 * Es una sugerencia para la pantalla; al guardar, el backend vuelve a resolver
 * el precio contra la tasa de la fecha del pedido y congela ese. Sin lista, sin
 * precio registrado o sin tasa para convertirlo cae en 0 y el usuario lo
 * captura a mano.
 */
function resolvePrice(
    item: ItemCatalogEntry | undefined,
    priceListId: string,
    currency: string,
    rates: TodayRates,
): number {
    if (!item) {
        return 0;
    }

    const price = item.prices.find(
        (candidate) => candidate.price_list_id === priceListId,
    );

    if (!price) {
        return 0;
    }

    if (price.currency === currency) {
        return Number(price.price);
    }

    return (
        convertAmount(
            Number(price.price),
            rates[price.currency],
            rates[currency],
        ) ?? 0
    );
}

function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

/** En ventas el artículo se reconoce por su sku. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.sku ? `${entry.sku} — ${entry.name}` : entry.name;
}

export function useSalesOrderForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseSalesOrderFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();
    const todayRates = useTodayRates();

    /**
     * El catálogo de artículos ya no viaja en las props: la pantalla solo
     * conoce los que trae el pedido y los que el usuario va eligiendo.
     */
    const catalog = useItemCatalog({
        companyId,
        formatLabel: itemLabel,
        seed: (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .map((line) => ({
                id: line.item_id,
                sku: line.item_sku,
                name: line.item_name,
            })),
    });

    const initialCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    /**
     * Con la corrección permitida el campo nace con la tasa del catálogo a la
     * vista, no vacío: el usuario ve con qué se va a valorar el pedido antes de
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
        useForm<SalesOrderFormData>({
            id: initialData?.id ?? generateUUID(),
            client_id: initialData?.client_id ?? '',
            client_address_id: initialData?.client_address_id ?? '',
            warehouse_id:
                initialData?.warehouse_id ?? defaultWarehouseId(options),
            price_list_id: initialData?.price_list_id ?? '',
            salesperson_id: initialData?.salesperson_id ?? '',
            order_date:
                initialData?.order_date ??
                new Date().toISOString().slice(0, 10),
            expected_date: initialData?.expected_date ?? '',
            client_reference: initialData?.client_reference ?? '',
            /** Un pedido nace en la moneda en la que la empresa lleva sus cifras. */
            currency: initialCurrency,
            exchange_rate: catalogRate(initialCurrency),
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    const repriceLine = (
        line: SalesOrderLineRow,
        priceListId: string,
        currency: string,
    ): SalesOrderLineRow => {
        if (line.item_id === '') {
            return line;
        }

        const item = catalog.itemOf(line.item_id);

        /*
         * Un artículo que la pantalla todavía no conoce —una línea del pedido
         * cuya hidratación no ha llegado— se deja intacta: revaluarla con lo
         * que no sabemos borraría el precio que ya tiene.
         */
        if (!item?.hydrated) {
            return line;
        }

        const price = resolvePrice(item, priceListId, currency, todayRates);

        /** Un precio pactado a mano (distinto del de lista) se respeta. */
        if (line.unit_price !== line.list_price) {
            return { ...line, list_price: price };
        }

        return { ...line, list_price: price, unit_price: price };
    };

    /**
     * Elegir el cliente arrastra sus condiciones comerciales: lista de precio,
     * días de crédito y su dirección de entrega sugerida.
     */
    const selectClient = (clientId: string) => {
        const client = options.clients.find(
            (candidate) => candidate.id === clientId,
        );

        const priceListId = client?.price_list_id ?? data.price_list_id;
        const defaultAddress = client?.addresses.find(
            (address) =>
                address.type === 'shipping' && address.is_default === 'yes',
        );

        setData((current) => ({
            ...current,
            client_id: clientId,
            client_address_id: defaultAddress?.id ?? '',
            price_list_id: priceListId ?? '',
            payment_term_days:
                client?.payment_term_days ?? current.payment_term_days,
            /** Cambiar de lista revalúa las líneas que aún no tienen precio pactado. */
            lines: current.lines.map((line) =>
                repriceLine(line, priceListId ?? '', current.currency),
            ),
        }));
    };

    /** Cambiar la lista de precio revalúa las líneas cuyo precio no se tocó a mano. */
    const selectPriceList = (priceListId: string) => {
        setData((current) => ({
            ...current,
            price_list_id: priceListId,
            lines: current.lines.map((line) =>
                repriceLine(line, priceListId, current.currency),
            ),
        }));
    };

    /**
     * Cambiar la moneda del pedido reexpresa los precios de lista: el mismo
     * artículo cuesta otro número en otra moneda.
     */
    const selectCurrency = (currency: string) => {
        setData((current) => ({
            ...current,
            currency,
            /** Otra moneda, otra tasa: la del catálogo vuelve a la vista. */
            exchange_rate: catalogRate(currency),
            lines: current.lines.map((line) =>
                repriceLine(line, current.price_list_id, currency),
            ),
        }));
    };

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.length === 1
                ? [emptyLine()]
                : data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof SalesOrderLineRow>(
        index: number,
        field: K,
        value: SalesOrderLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /**
     * Elegir el artículo trae su unidad base y su precio de la lista aplicada.
     * La opción llega del select remoto con todo eso dentro, así que basta con
     * recordarla para que el resto de la pantalla la conozca.
     */
    const selectLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;
        const price = resolvePrice(
            item,
            data.price_list_id,
            data.currency,
            todayRates,
        );

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? {
                          ...line,
                          item_id: item?.id ?? '',
                          measurement_unit_id: baseUnitId(item),
                          list_price: price,
                          unit_price: price,
                      }
                    : line,
            ),
        );
    };

    const totals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = lineAmounts(line);

            return {
                gross: round(accumulator.gross + amounts.gross, 2),
                discountAmount: round(
                    accumulator.discountAmount + amounts.discountAmount,
                    2,
                ),
                subtotal: round(accumulator.subtotal + amounts.subtotal, 2),
                taxAmount: round(accumulator.taxAmount + amounts.taxAmount, 2),
                total: round(accumulator.total + amounts.total, 2),
            };
        },
        { gross: 0, discountAmount: 0, subtotal: 0, taxAmount: 0, total: 0 },
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        /**
         * El campo enseña la tasa del catálogo, pero solo viaja como corrección
         * si el usuario escribió otra: así un pedido con fecha anterior se sigue
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
            post(salesOrders.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });

            return;
        }

        if (initialData) {
            put(
                salesOrders.update({ company: companyId, id: initialData.id })
                    .url,
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
        catalog,
        selectClient,
        selectPriceList,
        selectCurrency,
        addLine,
        removeLine,
        updateLine,
        selectLineItem,
    };
}
