import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import {
    useItemCatalog,
    type ItemCatalogEntry,
    type ItemCatalogSeed,
} from '@/hooks/use-item-catalog';
import {
    useRemoteOptionSet,
    type RemoteOptionSeed,
} from '@/hooks/use-remote-option-set';
import { generateUUID } from '@/lib/utils';
import adjustments from '@/routes/adjustments';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import {
    REVALUATION_TYPE,
    type Adjustment,
    type AdjustmentDirection,
    type AdjustmentOptions,
    type AdjustmentType,
} from '../types/Adjustment';
import { useAdjustmentStock } from './useAdjustmentStock';

interface UseAdjustmentFormProps {
    mode: 'create' | 'edit';
    options: AdjustmentOptions;
    initialData?: Adjustment;
    onSuccess?: () => void;
}

export interface AdjustmentLineRow {
    id: string;
    item_id: string;
    measurement_unit_id: string;
    /** Lo único que se captura: lo que se encontró al contar. */
    counted_quantity: number;
    /** Costo nuevo. Solo lo lee una revaluación. */
    unit_cost: number;
    /** Vacía deja que el kardex tome la ubicación por defecto de la bodega. */
    location_id: string;
    lot_id: string;
    serial_id: string;
    reason: string;
    counted_by: string;
    notes: string;
}

interface AdjustmentFormData {
    id: string;
    warehouse_id: string;
    adjustment_date: string;
    type: AdjustmentType;
    direction: AdjustmentDirection;
    reason: string;
    count_id: string;
    attachment_path: string;
    notes: string;
    lines: AdjustmentLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/** En inventario el artículo se reconoce por su código. */
function itemLabel(entry: ItemCatalogEntry): string {
    return entry.code ? `${entry.code} — ${entry.name}` : entry.name;
}

/** La unidad en la que se cuenta por defecto: la base del artículo. */
function baseUnitId(item: ItemCatalogEntry | undefined): string {
    if (!item) {
        return '';
    }

    const base = item.units.find((unit) => unit.is_base === 'yes');

    return (
        base?.measurement_unit_id ?? item.units[0]?.measurement_unit_id ?? ''
    );
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

function round4(value: number): number {
    return Math.round((value + Number.EPSILON) * 10000) / 10000;
}

function emptyLine(): AdjustmentLineRow {
    return {
        id: generateUUID(),
        item_id: '',
        measurement_unit_id: '',
        counted_quantity: 0,
        unit_cost: 0,
        location_id: '',
        lot_id: '',
        serial_id: '',
        reason: '',
        counted_by: '',
        notes: '',
    };
}

/**
 * Solo se editan las líneas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function lineRows(model?: Adjustment): AdjustmentLineRow[] {
    const rows = (model?.lines ?? [])
        .filter((line) => line.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            id: line.id,
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            counted_quantity: Number(line.counted_quantity),
            unit_cost: Number(line.unit_cost),
            location_id: line.location_id ?? '',
            lot_id: line.lot_id ?? '',
            serial_id: line.serial_id ?? '',
            reason: line.reason ?? '',
            counted_by: line.counted_by ?? '',
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/**
 * Los valores ya elegidos en las líneas, con la etiqueta que trajo el Resource:
 * así el select los muestra desde el primer render.
 */
function optionSeeds(
    saved: Array<{ id: string | null; label?: string | null }>,
    current: string[],
): RemoteOptionSeed[] {
    const seeds = new Map<string, RemoteOptionSeed>();

    saved.forEach((entry) => {
        if (entry.id) {
            seeds.set(entry.id, { id: entry.id, label: entry.label });
        }
    });

    current.forEach((id) => {
        if (id !== '' && !seeds.has(id)) {
            seeds.set(id, { id });
        }
    });

    return [...seeds.values()];
}

/** Lo que una línea aporta al ajuste, calculado como lo calcula el backend. */
export interface AdjustmentLineAmounts {
    /** Lo que dice el sistema y el costo con el que hoy lo valora. */
    system: number;
    average: number;
    difference: number;
    /** La diferencia y la existencia, ambas en unidad base. */
    baseDifference: number;
    baseSystem: number;
    unitCost: number;
    /** Impacto en el valor del inventario, con signo. */
    impact: number;
}

export interface AdjustmentTotals {
    quantityIn: number;
    quantityOut: number;
    costIn: number;
    costOut: number;
    net: number;
}

export function useAdjustmentForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseAdjustmentFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<AdjustmentFormData>({
            id: initialData?.id ?? generateUUID(),
            warehouse_id: initialData?.warehouse_id ?? '',
            adjustment_date:
                initialData?.adjustment_date ??
                new Date().toISOString().slice(0, 10),
            type: initialData?.type ?? 'physical_count',
            direction: initialData?.direction ?? 'mixed',
            reason: initialData?.reason ?? '',
            count_id: initialData?.count_id ?? '',
            attachment_path: initialData?.attachment_path ?? '',
            notes: initialData?.notes ?? '',
            lines: lineRows(initialData),
        });

    /**
     * El catálogo de artículos no viaja en las props: la pantalla solo conoce
     * los que trae el ajuste y los que el usuario va eligiendo.
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

    const lotSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).map((line) => ({
                    id: line.lot_id,
                    label: line.lot_number,
                })),
                data.lines.map((line) => line.lot_id),
            ),
        [initialData, data.lines],
    );

    const serialSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).map((line) => ({
                    id: line.serial_id,
                    label: line.serial_number,
                })),
                data.lines.map((line) => line.serial_id),
            ),
        [initialData, data.lines],
    );

    const lots = useRemoteOptionSet({
        url: itemLots.lookup(companyId).url,
        seed: lotSeed,
    });

    const serials = useRemoteOptionSet({
        url: itemSerials.lookup(companyId).url,
        seed: serialSeed,
    });

    /** La existencia con la que se compara cada línea. */
    const { stockOf } = useAdjustmentStock({
        companyId,
        warehouseId: data.warehouse_id,
        keys: data.lines.map((line) => ({
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            location_id: line.location_id,
            lot_id: line.lot_id,
        })),
    });

    const isRevaluation = data.type === REVALUATION_TYPE;

    /** Factor con el que la línea convierte a la unidad base del artículo. */
    const factorOf = (line: AdjustmentLineRow): number => {
        const unit = catalog
            .itemOf(line.item_id)
            ?.units.find(
                (candidate) =>
                    candidate.measurement_unit_id === line.measurement_unit_id,
            );

        return unit ? Number(unit.conversion_factor) : 1;
    };

    /**
     * Espejo del cálculo del backend: sirve para enseñar la diferencia y su
     * impacto mientras se cuenta. Lo que se guarda siempre lo recalcula el
     * servidor contra la existencia del momento.
     */
    const amountsOf = (line: AdjustmentLineRow): AdjustmentLineAmounts => {
        const stock = stockOf({
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            location_id: line.location_id,
            lot_id: line.lot_id,
        });

        const factor = factorOf(line);
        const baseSystem = round4(stock.system * factor);

        if (isRevaluation) {
            return {
                system: stock.system,
                average: stock.average,
                difference: 0,
                baseDifference: 0,
                baseSystem,
                unitCost: line.unit_cost,
                impact: round2(baseSystem * (line.unit_cost - stock.average)),
            };
        }

        const difference = round4(line.counted_quantity - stock.system);
        const baseDifference = round4(difference * factor);

        return {
            system: stock.system,
            average: stock.average,
            difference,
            baseDifference,
            baseSystem,
            unitCost: stock.average,
            impact: round2(baseDifference * stock.average),
        };
    };

    const totals: AdjustmentTotals = data.lines.reduce(
        (accumulator, line) => {
            const amounts = amountsOf(line);
            const quantity = Math.abs(
                isRevaluation ? 0 : amounts.baseDifference,
            );
            const cost = Math.abs(amounts.impact);

            if (amounts.impact < 0) {
                return {
                    ...accumulator,
                    quantityOut: round4(accumulator.quantityOut + quantity),
                    costOut: round2(accumulator.costOut + cost),
                    net: round2(accumulator.net - cost),
                };
            }

            return {
                ...accumulator,
                quantityIn: round4(accumulator.quantityIn + quantity),
                costIn: round2(accumulator.costIn + cost),
                net: round2(accumulator.net + cost),
            };
        },
        { quantityIn: 0, quantityOut: 0, costIn: 0, costOut: 0, net: 0 },
    );

    /**
     * Por encima del umbral el ajuste lo tiene que firmar alguien distinto de
     * quien lo registra. El aviso sale mientras se captura, no al intentar
     * aplicarlo.
     */
    const needsSecondApproval =
        Math.abs(totals.net) > Number(options.approval_threshold ?? 0);

    /** Las ubicaciones de la bodega elegida: se cuenta un sitio suyo. */
    const locations = options.locations.filter(
        (location) => location.warehouse_id === data.warehouse_id,
    );

    /**
     * Cambiar de bodega invalida las ubicaciones ya elegidas: eran de la
     * anterior, y con ellas la existencia contra la que se comparaba.
     */
    const selectWarehouse = (warehouseId: string) =>
        setData((current) => ({
            ...current,
            warehouse_id: warehouseId,
            lines: current.lines.map((line) => ({ ...line, location_id: '' })),
        }));

    const addLine = () => setData('lines', [...data.lines, emptyLine()]);

    const removeLine = (index: number) =>
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );

    const updateLine = <K extends keyof AdjustmentLineRow>(
        index: number,
        field: K,
        value: AdjustmentLineRow[K],
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, [field]: value } : line,
            ),
        );

    /**
     * Cambiar de artículo invalida la unidad, el lote y la serie: ya no
     * identifican a la misma mercancía, así que tampoco a la misma existencia.
     */
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
                          lot_id: '',
                          serial_id: '',
                          counted_quantity: 0,
                          unit_cost: 0,
                      }
                    : line,
            ),
        );
    };

    const setLineLot = (index: number, option: AjaxOption | null) => {
        if (option) {
            lots.remember(option);
        }

        updateLine(index, 'lot_id', option?.value ?? '');
    };

    const setLineSerial = (index: number, option: AjaxOption | null) => {
        if (option) {
            serials.remember(option);
        }

        updateLine(index, 'serial_id', option?.value ?? '');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(adjustments.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                adjustments.update({ company: companyId, id: initialData.id })
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
        needsSecondApproval,
        isRevaluation,
        amountsOf,
        locations,
        selectWarehouse,
        addLine,
        removeLine,
        updateLine,
        setLineItem,
        setLineLot,
        setLineSerial,
        catalog,
        lots,
        serials,
    };
}
