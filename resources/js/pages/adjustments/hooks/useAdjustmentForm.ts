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
    type AdjustmentLine,
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

/** Uno de los lotes que la línea contó; siempre elegido del maestro. */
export interface AdjustmentLineLotRow {
    id: string;
    lot_id: string;
    /** Lo que se encontró de ese lote, en la unidad de la línea. */
    counted_quantity: number;
    status: 'active' | 'inactive';
}

/**
 * Una de las unidades con serie que la línea nombra. `lot_id` dice de cuál de
 * sus lotes sale, cuando la línea cuenta más de uno.
 */
export interface AdjustmentLineSerialRow {
    id: string;
    serial_id: string;
    lot_id: string;
    status: 'active' | 'inactive';
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
    lots: AdjustmentLineLotRow[];
    serials: AdjustmentLineSerialRow[];
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
        lots: [],
        serials: [],
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
            lots: lotRows(line),
            serials: serialRows(line),
            reason: line.reason ?? '',
            counted_by: line.counted_by ?? '',
            notes: line.notes ?? '',
        }));

    return rows.length > 0 ? rows : [emptyLine()];
}

/** Los lotes activos de una línea guardada. */
function lotRows(line: AdjustmentLine): AdjustmentLineLotRow[] {
    return (line.lots ?? [])
        .filter((lot) => lot.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((lot) => ({
            id: lot.id,
            lot_id: lot.lot_id,
            counted_quantity: Number(lot.counted_quantity),
            status: 'active' as const,
        }));
}

/** Las series activas de una línea guardada, cada una atada a su lote. */
function serialRows(line: AdjustmentLine): AdjustmentLineSerialRow[] {
    const lotIdOf = new Map(
        (line.lots ?? []).map((lot) => [lot.id, lot.lot_id]),
    );

    return (line.serials ?? [])
        .filter((serial) => serial.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((serial) => ({
            id: serial.id,
            serial_id: serial.serial_id,
            lot_id: serial.adjustment_line_lot_id
                ? (lotIdOf.get(serial.adjustment_line_lot_id) ?? '')
                : '',
            status: 'active' as const,
        }));
}

/** Cuánto de lo contado en la línea se repartió ya en lotes. */
export function countedInLots(line: AdjustmentLineRow): number {
    return round4(
        line.lots
            .filter((lot) => lot.status === 'active')
            .reduce((total, lot) => total + lot.counted_quantity, 0),
    );
}

/** Los lotes que de verdad cuentan: activos y con lote elegido. */
export function activeLots(line: AdjustmentLineRow): AdjustmentLineLotRow[] {
    return line.lots.filter(
        (lot) => lot.status === 'active' && lot.lot_id !== '',
    );
}

/**
 * ¿Esa fila de trazabilidad ya está guardada? Una que nunca llegó a la base se
 * puede quitar sin más; una que sí, se desactiva.
 */
function isSaved(
    id: string,
    model: Adjustment | undefined,
    collection: 'lots' | 'serials',
): boolean {
    return (model?.lines ?? []).some((line) =>
        (line[collection] ?? []).some((row) => row.id === id),
    );
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

    /** Lotes y series elegidos en las líneas, aplanados de sus colecciones. */
    const lotSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).flatMap((line) =>
                    (line.lots ?? []).map((lot) => ({
                        id: lot.lot_id,
                        label: lot.lot_number,
                    })),
                ),
                data.lines.flatMap((line) =>
                    line.lots.map((lot) => lot.lot_id),
                ),
            ),
        [initialData, data.lines],
    );

    const serialSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.lines ?? []).flatMap((line) =>
                    (line.serials ?? []).map((serial) => ({
                        id: serial.serial_id,
                        label: serial.serial_number,
                    })),
                ),
                data.lines.flatMap((line) =>
                    line.serials.map((serial) => serial.serial_id),
                ),
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

    /**
     * La existencia con la que se compara cada línea y cada uno de sus lotes:
     * un lote es una existencia aparte, así que se pregunta aparte.
     */
    const { stockOf } = useAdjustmentStock({
        companyId,
        warehouseId: data.warehouse_id,
        keys: data.lines.flatMap((line) => [
            {
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                location_id: line.location_id,
                lot_id: '',
            },
            ...activeLots(line).map((lot) => ({
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                location_id: line.location_id,
                lot_id: lot.lot_id,
            })),
        ]),
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
     * Contra qué se compara la línea. Sin lotes es el saldo de su ubicación;
     * con lotes es la suma de los que cuenta, valorados a su promedio
     * ponderado: la línea no está contando nada más que esos lotes.
     */
    const systemOf = (line: AdjustmentLineRow) => {
        const whole = stockOf({
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            location_id: line.location_id,
            lot_id: '',
        });

        const lots = activeLots(line);

        if (lots.length === 0) {
            return whole;
        }

        const entries = lots.map((lot) =>
            stockOf({
                item_id: line.item_id,
                measurement_unit_id: line.measurement_unit_id,
                location_id: line.location_id,
                lot_id: lot.lot_id,
            }),
        );

        const system = round4(
            entries.reduce((total, entry) => total + entry.system, 0),
        );

        const value = entries.reduce(
            (total, entry) => total + entry.system * entry.average,
            0,
        );

        return {
            system,
            average: system > 0 ? value / system : whole.average,
        };
    };

    /** Lo que dice el sistema de uno de los lotes que la línea cuenta. */
    const lotAmountsOf = (
        line: AdjustmentLineRow,
        lot: AdjustmentLineLotRow,
    ) => {
        const stock = stockOf({
            item_id: line.item_id,
            measurement_unit_id: line.measurement_unit_id,
            location_id: line.location_id,
            lot_id: lot.lot_id,
        });

        return {
            system: stock.system,
            difference: round4(lot.counted_quantity - stock.system),
        };
    };

    /**
     * Espejo del cálculo del backend: sirve para enseñar la diferencia y su
     * impacto mientras se cuenta. Lo que se guarda siempre lo recalcula el
     * servidor contra la existencia del momento.
     */
    const amountsOf = (line: AdjustmentLineRow): AdjustmentLineAmounts => {
        const stock = systemOf(line);
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
                          lots: [],
                          serials: [],
                          counted_quantity: 0,
                          unit_cost: 0,
                      }
                    : line,
            ),
        );
    };

    /** ---- Trazabilidad de la línea: lotes y series ---- */

    const mapLine = (
        index: number,
        change: (line: AdjustmentLineRow) => AdjustmentLineRow,
    ) =>
        setData(
            'lines',
            data.lines.map((line, i) => (i === index ? change(line) : line)),
        );

    const addLineLot = (index: number) =>
        mapLine(index, (line) => ({
            ...line,
            lots: [
                ...line.lots,
                {
                    id: generateUUID(),
                    lot_id: '',
                    /** Lo que falta por repartir: casi siempre es todo. */
                    counted_quantity: Math.max(
                        round4(line.counted_quantity - countedInLots(line)),
                        0,
                    ),
                    status: 'active' as const,
                },
            ],
        }));

    const setLineLot = (
        index: number,
        lotIndex: number,
        option: AjaxOption | null,
    ) => {
        if (option) {
            lots.remember(option);
        }

        mapLine(index, (line) => ({
            ...line,
            lots: line.lots.map((lot, i) =>
                i === lotIndex ? { ...lot, lot_id: option?.value ?? '' } : lot,
            ),
        }));
    };

    const updateLineLot = (
        index: number,
        lotIndex: number,
        countedQuantity: number,
    ) =>
        mapLine(index, (line) => ({
            ...line,
            lots: line.lots.map((lot, i) =>
                i === lotIndex
                    ? { ...lot, counted_quantity: countedQuantity }
                    : lot,
            ),
        }));

    /**
     * Una fila que nunca se guardó desaparece; una que ya existe se desactiva.
     * La política de no borrado también alcanza a la trazabilidad.
     */
    const removeLineLot = (index: number, lotIndex: number) =>
        mapLine(index, (line) => {
            const lot = line.lots[lotIndex];

            if (!lot) {
                return line;
            }

            /** Las series que salían de ese lote se quedan sin lote. */
            const serialRows = line.serials.map((serial) =>
                serial.lot_id === lot.lot_id
                    ? { ...serial, lot_id: '' }
                    : serial,
            );

            return isSaved(lot.id, initialData, 'lots')
                ? {
                      ...line,
                      serials: serialRows,
                      lots: line.lots.map((current, i) =>
                          i === lotIndex
                              ? { ...current, status: 'inactive' as const }
                              : current,
                      ),
                  }
                : {
                      ...line,
                      serials: serialRows,
                      lots: line.lots.filter((_, i) => i !== lotIndex),
                  };
        });

    const addLineSerial = (index: number) =>
        mapLine(index, (line) => ({
            ...line,
            serials: [
                ...line.serials,
                {
                    id: generateUUID(),
                    serial_id: '',
                    lot_id: '',
                    status: 'active' as const,
                },
            ],
        }));

    const setLineSerial = (
        index: number,
        serialIndex: number,
        option: AjaxOption | null,
    ) => {
        if (option) {
            serials.remember(option);
        }

        mapLine(index, (line) => ({
            ...line,
            serials: line.serials.map((serial, i) =>
                i === serialIndex
                    ? { ...serial, serial_id: option?.value ?? '' }
                    : serial,
            ),
        }));
    };

    const setLineSerialLot = (
        index: number,
        serialIndex: number,
        lotId: string,
    ) =>
        mapLine(index, (line) => ({
            ...line,
            serials: line.serials.map((serial, i) =>
                i === serialIndex ? { ...serial, lot_id: lotId } : serial,
            ),
        }));

    const removeLineSerial = (index: number, serialIndex: number) =>
        mapLine(index, (line) => {
            const serial = line.serials[serialIndex];

            if (!serial) {
                return line;
            }

            return isSaved(serial.id, initialData, 'serials')
                ? {
                      ...line,
                      serials: line.serials.map((current, i) =>
                          i === serialIndex
                              ? { ...current, status: 'inactive' as const }
                              : current,
                      ),
                  }
                : {
                      ...line,
                      serials: line.serials.filter((_, i) => i !== serialIndex),
                  };
        });

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
        addLineLot,
        setLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerial,
        setLineSerial,
        setLineSerialLot,
        removeLineSerial,
        lotAmountsOf,
        catalog,
        lots,
        serials,
    };
}
