import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useItemCatalog,
    type ItemCatalogEntry,
    type ItemCatalogSeed,
} from '@/hooks/use-item-catalog';
import { generateUUID } from '@/lib/utils';
import transfers from '@/routes/transfers';
import type {
    Transfer,
    TransferOptions,
    TransferReason,
} from '../types/Transfer';

interface UseTransferFormProps {
    mode: 'create' | 'edit';
    options: TransferOptions;
    initialData?: Transfer;
    onSuccess?: () => void;
}

export interface TransferLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    quantity: number;
    notes: string;
}

interface TransferFormData {
    id: string;
    origin_warehouse_id: string;
    destination_warehouse_id: string;
    transfer_date: string;
    expected_date: string;
    reason: TransferReason;
    reason_detail: string;
    driver_id: string;
    vehicle_plate: string;
    notes: string;
    lines: TransferLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** En inventario el artículo se reconoce por su sku. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.sku ? `${entry.sku} — ${entry.name}` : entry.name;
}

/** La unidad en la que se traslada por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

function emptyLine(): TransferLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        quantity: 1,
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(model?: Transfer): TransferLineRow[] {
    const rows = (model?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            quantity: Number(line.quantity),
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

export interface TransferTotals {
    /** Bultos de la carga. El valor no se suma aquí: lo resuelve el backend. */
    quantity: number;
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

export function useTransferForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseTransferFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    /** Los importes del traslado son de costo: van en la moneda de la empresa. */
    const currency = configuration?.base_currency ?? 'USD';

    const { data, setData, post, put, processing, errors, reset } =
        useForm<TransferFormData>({
            id: initialData?.id ?? generateUUID(),
            origin_warehouse_id: initialData?.origin_warehouse_id ?? '',
            destination_warehouse_id:
                initialData?.destination_warehouse_id ?? '',
            transfer_date:
                initialData?.transfer_date ??
                new Date().toISOString().slice(0, 10),
            expected_date: initialData?.expected_date ?? '',
            reason: initialData?.reason ?? 'restock',
            reason_detail: initialData?.reason_detail ?? '',
            driver_id: initialData?.driver_id ?? '',
            vehicle_plate: initialData?.vehicle_plate ?? '',
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos no viaja en las props: la pantalla solo conoce
     * los que trae el traslado y los que el usuario va eligiendo.
     */
    const catalogSeed: ItemCatalogSeed[] = useMemo(() => {
        const seeds = new Map<string, ItemCatalogSeed>();

        (initialData?.lines ?? [])
            .filter((line) => line.status === 'active')
            .forEach((line) =>
                seeds.set(line.item_id, {
                    id: line.item_id,
                    code: line.item_code,
                    name: line.item_name,
                }),
            );

        data.lines.forEach((line) => {
            if (line.item_id !== '' && !seeds.has(line.item_id)) {
                seeds.set(line.item_id, { id: line.item_id });
            }
        });

        return [...seeds.values()];
    }, [initialData, data.lines]);

    const catalog = useItemCatalog({
        companyId,
        formatLabel: itemLabel,
        seed: catalogSeed,
    });

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof TransferLineRow>(
        index: number,
        field: K,
        value: TransferLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /** Cambiar de artículo invalida la unidad: ya no es la misma mercancía. */
    const setLineItem = (index: number, option: AjaxOption | null) => {
        const item = option ? catalog.remember(option) : undefined;

        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index
                    ? {
                          ...line,
                          item_id: item?.id ?? '',
                          measurement_unit_id: baseUnitId(item),
                      }
                    : line,
            ),
        );
    };

    const selectOriginWarehouse = (warehouseId: string) =>
        setData((current) => ({
            ...current,
            origin_warehouse_id: warehouseId,
            lines: current.lines.map((line) => ({
                ...line,
            })),
        }));

    const selectDestinationWarehouse = (warehouseId: string) =>
        setData((current) => ({
            ...current,
            destination_warehouse_id: warehouseId,
            lines: current.lines.map((line) => ({
                ...line,
            })),
        }));

    /**
     * Las bodegas que quedan libres para cada campo: una bodega no puede ser
     * dos extremos del mismo viaje.
     */
    const warehousesExcept = (...taken: string[]) =>
        options.warehouses.filter(
            (warehouse) => !taken.filter(Boolean).includes(warehouse.id),
        );

    /**
     * El valor de la carga no se calcula en la pantalla: el costo con el que la
     * mercancía viaja lo pone el kardex al sacarla del origen, y hasta entonces
     * no hay cifra que enseñar que no sea una suposición.
     */
    const totals: TransferTotals = data.lines.reduce(
        (accumulator, line) => ({
            quantity: round2(accumulator.quantity + line.quantity),
        }),
        { quantity: 0 },
    );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(transfers.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                transfers.update({
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
        currency,
        totals,
        selectOriginWarehouse,
        selectDestinationWarehouse,
        warehousesExcept,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        catalog,
    };
}

