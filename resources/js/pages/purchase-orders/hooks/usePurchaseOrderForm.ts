import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import purchaseOrders from '@/routes/purchase-orders';
import type { PurchaseOrder } from '../types/PurchaseOrder';

interface UsePurchaseOrderFormProps {
    mode: 'create' | 'edit';
    initialData?: PurchaseOrder;
    onSuccess?: () => void;
}

export interface PurchaseOrderLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_percent: number;
    withholding_percent: number;
    notes: string;
}

interface PurchaseOrderFormData {
    id: string;
    supplier_id: string;
    warehouse_id: string;
    order_date: string;
    expected_date: string;
    supplier_reference: string;
    currency: string;
    exchange_rate: number;
    payment_term_days: number;
    discount_amount: number;
    notes: string;
    lines: PurchaseOrderLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

export interface PurchaseOrderTotals {
    subtotal: number;
    taxAmount: number;
    total: number;
}

function emptyLine(): PurchaseOrderLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        unit_price: 0,
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
function lineRows(order?: PurchaseOrder): PurchaseOrderLineRow[] {
    const rows = (order?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            unit_price: Number(line.unit_price),
            discount_percent: Number(line.discount_percent),
            tax_percent: Number(line.tax_percent),
            withholding_percent: Number(line.withholding_percent),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Espejo del cálculo del backend (`PurchaseOrderLineData`): sirve para mostrar
 * el resumen mientras se captura. El importe que se guarda siempre lo recalcula
 * el servidor.
 */
export function lineAmounts(line: PurchaseOrderLineRow): {
    subtotal: number;
    taxAmount: number;
    total: number;
} {
    const gross = line.quantity * line.unit_price;
    const discount = round2((gross * line.discount_percent) / 100);
    const subtotal = round2(gross - discount);
    const taxAmount = round2((subtotal * line.tax_percent) / 100);

    return { subtotal, taxAmount, total: round2(subtotal + taxAmount) };
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

export function usePurchaseOrderForm({
    mode,
    initialData,
    onSuccess,
}: UsePurchaseOrderFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<PurchaseOrderFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_id: initialData?.supplier_id ?? '',
            warehouse_id: initialData?.warehouse_id ?? '',
            order_date:
                initialData?.order_date ??
                new Date().toISOString().slice(0, 10),
            expected_date: initialData?.expected_date ?? '',
            supplier_reference: initialData?.supplier_reference ?? '',
            currency: initialData?.currency ?? 'USD',
            exchange_rate: Number(initialData?.exchange_rate ?? 1),
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            discount_amount: Number(initialData?.discount_amount ?? 0),
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof PurchaseOrderLineRow>(
        index: number,
        field: K,
        value: PurchaseOrderLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /** Cambiar de artículo invalida la unidad elegida: se resuelve en una sola pasada. */
    const setLineItem = (
        index: number,
        itemId: string,
        measurementUnitId: string,
        unitPrice: number,
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? {
                          ...line,
                          item_id: itemId,
                          measurement_unit_id: measurementUnitId,
                          unit_price:
                              line.unit_price > 0 ? line.unit_price : unitPrice,
                      }
                    : line,
            ),
        );

    const totals: PurchaseOrderTotals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = lineAmounts(line);

            return {
                subtotal: round2(accumulator.subtotal + amounts.subtotal),
                taxAmount: round2(accumulator.taxAmount + amounts.taxAmount),
                total: 0,
            };
        },
        { subtotal: 0, taxAmount: 0, total: 0 },
    );

    totals.total = round2(
        totals.subtotal - data.discount_amount + totals.taxAmount,
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(purchaseOrders.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                purchaseOrders.update({
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
        addLine,
        removeLine,
        updateLine,
        setLineItem,
    };
}
