import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import salesOrders from '@/routes/sales-orders';
import type {
    ItemOption,
    SalesOrder,
    SalesOrderOptions,
} from '../types/SalesOrder';

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
    exchange_rate: number;
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
 * Precio del artículo en la lista indicada. Sin lista o sin precio registrado
 * cae en 0 y el usuario lo captura a mano.
 */
function resolvePrice(
    item: ItemOption | undefined,
    priceListId: string,
): number {
    if (!item) {
        return 0;
    }

    const price = item.prices.find(
        (candidate) => candidate.price_list_id === priceListId,
    );

    return Number(price?.price ?? 0);
}

function baseUnitId(item: ItemOption | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

export function useSalesOrderForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseSalesOrderFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
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
            currency: initialData?.currency ?? 'USD',
            exchange_rate: Number(initialData?.exchange_rate ?? 1),
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    const repriceLine = (
        line: SalesOrderLineRow,
        priceListId: string,
    ): SalesOrderLineRow => {
        if (line.item_id === '') {
            return line;
        }

        const item = options.items.find(
            (candidate) => candidate.id === line.item_id,
        );
        const price = resolvePrice(item, priceListId);

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
                repriceLine(line, priceListId ?? ''),
            ),
        }));
    };

    /** Cambiar la lista de precio revalúa las líneas cuyo precio no se tocó a mano. */
    const selectPriceList = (priceListId: string) => {
        setData((current) => ({
            ...current,
            price_list_id: priceListId,
            lines: current.lines.map((line) => repriceLine(line, priceListId)),
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
     */
    const selectLineItem = (index: number, itemId: string) => {
        const item = options.items.find((candidate) => candidate.id === itemId);
        const price = resolvePrice(item, data.price_list_id);

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? {
                          ...line,
                          item_id: itemId,
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
                subtotal: round(accumulator.subtotal + amounts.subtotal, 2),
                discountAmount: round(
                    accumulator.discountAmount + amounts.discountAmount,
                    2,
                ),
                taxAmount: round(accumulator.taxAmount + amounts.taxAmount, 2),
                total: round(accumulator.total + amounts.total, 2),
            };
        },
        { subtotal: 0, discountAmount: 0, taxAmount: 0, total: 0 },
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

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
        selectClient,
        selectPriceList,
        addLine,
        removeLine,
        updateLine,
        selectLineItem,
    };
}
