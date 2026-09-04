import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import { useConfiguration } from '@/hooks/use-configuration';
import {
    useRemoteOptionSet,
    type RemoteOptionSeed,
} from '@/hooks/use-remote-option-set';
import { generateUUID } from '@/lib/utils';
import imports from '@/routes/imports';
import purchaseInvoices from '@/routes/purchase-invoices';
import suppliers from '@/routes/suppliers';
import type {
    Import,
    ImportAllocationMethod,
    ImportCostConcept,
    ImportOptions,
} from '../types/Import';

interface UseImportFormProps {
    mode: 'create' | 'edit';
    options: ImportOptions;
    initialData?: Import;
    onSuccess?: () => void;
}

/** Uno de los cobros que el expediente reparte, tal como se captura. */
export interface ImportCostRow {
    id: string;
    concept: ImportCostConcept;
    description: string;
    sourceable_type: string;
    sourceable_id: string;
    supplier_id: string;
    currency: string;
    amount: number;
    notes: string;
    status: 'active' | 'inactive';
}

/** Una de las recepciones que absorben el gasto. */
export interface ImportEntryRow {
    id: string;
    entry_id: string;
    status: 'active' | 'inactive';
}

/**
 * El estado de un ítem derivado. Es lo único que la pantalla decide de esa
 * sección: el resto lo calcula el backend a partir de la recepción.
 */
export interface ImportLineRow {
    entry_line_id: string;
    status: 'active' | 'inactive';
}

interface ImportFormData {
    id: string;
    warehouse_id: string;
    import_date: string;
    arrival_date: string;
    reference: string;
    allocation_method: ImportAllocationMethod;
    currency: string;
    exchange_rate: string;
    notes: string;
    costs: ImportCostRow[];
    entries: ImportEntryRow[];
    lines: ImportLineRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function round2(value: number): number {
    return Math.round((value + Number.EPSILON) * 100) / 100;
}

function emptyCost(currency: string): ImportCostRow {
    return {
        id: generateUUID(),
        concept: 'freight',
        description: '',
        sourceable_type: '',
        sourceable_id: '',
        supplier_id: '',
        currency,
        amount: 0,
        notes: '',
        status: 'active',
    };
}

/**
 * Solo se editan las filas activas: las inactivas se conservan en la base por
 * la política de no borrado, pero no vuelven al formulario.
 */
function costRows(
    model: Import | undefined,
    currency: string,
): ImportCostRow[] {
    const rows = (model?.costs ?? [])
        .filter((cost) => cost.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((cost) => ({
            id: cost.id,
            concept: cost.concept,
            description: cost.description ?? '',
            sourceable_type: cost.sourceable_type ?? '',
            sourceable_id: cost.sourceable_id ?? '',
            supplier_id: cost.supplier_id ?? '',
            currency: cost.currency,
            amount: Number(cost.amount),
            notes: cost.notes ?? '',
            status: 'active' as const,
        }));

    return rows.length > 0 ? rows : [emptyCost(currency)];
}

function entryRows(model?: Import): ImportEntryRow[] {
    return (model?.entries ?? [])
        .filter((entry) => entry.status === 'active')
        .sort((a, b) => a.line_number - b.line_number)
        .map((entry) => ({
            id: entry.id,
            entry_id: entry.entry_id,
            status: 'active' as const,
        }));
}

/**
 * El estado con el que vuelve cada ítem derivado. Se manda entero —también los
 * activos— porque el backend vuelve a derivar la sección en cada guardado y lo
 * que no viene se da por activo.
 */
function lineRows(model?: Import): ImportLineRow[] {
    return (model?.lines ?? [])
        .sort((a, b) => a.line_number - b.line_number)
        .map((line) => ({
            entry_line_id: line.entry_line_id,
            status: line.status,
        }));
}

/**
 * Los valores ya elegidos, con la etiqueta que trajo el Resource: así el select
 * los muestra desde el primer render.
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

/** Lo que el expediente estima mientras se captura. */
export interface ImportTotals {
    charges: number;
    baseValue: number;
    landedValue: number;
    capitalized: number;
    variance: number;
}

export function useImportForm({
    mode,
    options,
    initialData,
    onSuccess,
}: UseImportFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    const defaultCurrency =
        initialData?.currency ?? configuration?.base_currency ?? '';

    const { data, setData, post, put, processing, errors, reset } =
        useForm<ImportFormData>({
            id: initialData?.id ?? generateUUID(),
            warehouse_id: initialData?.warehouse_id ?? '',
            import_date:
                initialData?.import_date ??
                new Date().toISOString().slice(0, 10),
            arrival_date: initialData?.arrival_date ?? '',
            reference: initialData?.reference ?? '',
            allocation_method: initialData?.allocation_method ?? 'value',
            currency: defaultCurrency,
            /** Vacía significa automática: la resuelve el catálogo de tasas. */
            exchange_rate: '',
            notes: initialData?.notes ?? '',
            costs: costRows(initialData, defaultCurrency),
            entries: entryRows(initialData),
            lines: lineRows(initialData),
        });

    /** Recepciones, proveedores y facturas ya elegidos, para el primer render. */
    const entrySeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.entries ?? []).map((entry) => ({
                    id: entry.entry_id,
                    label: entry.entry_code,
                })),
                data.entries.map((entry) => entry.entry_id),
            ),
        [initialData, data.entries],
    );

    const supplierSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.costs ?? []).map((cost) => ({
                    id: cost.supplier_id,
                    label: cost.supplier_name,
                })),
                data.costs.map((cost) => cost.supplier_id),
            ),
        [initialData, data.costs],
    );

    const invoiceSeed: RemoteOptionSeed[] = useMemo(
        () =>
            optionSeeds(
                (initialData?.costs ?? []).map((cost) => ({
                    id: cost.sourceable_id,
                    label: cost.sourceable_code,
                })),
                data.costs.map((cost) => cost.sourceable_id),
            ),
        [initialData, data.costs],
    );

    const entryOptions = useRemoteOptionSet({
        url: imports.entries(companyId).url,
        seed: entrySeed,
    });

    const supplierOptions = useRemoteOptionSet({
        url: suppliers.lookup(companyId).url,
        seed: supplierSeed,
    });

    const invoiceOptions = useRemoteOptionSet({
        url: purchaseInvoices.lookup(companyId).url,
        seed: invoiceSeed,
    });

    /**
     * Espejo del cálculo del backend: enseña lo que se está repartiendo
     * mientras se captura. Lo capitalizado y la varianza no se estiman aquí
     * —dependen del saldo vivo de cada línea, que solo el servidor conoce—:
     * salen del último guardado.
     */
    const totals: ImportTotals = useMemo(() => {
        const charges = round2(
            data.costs
                .filter((cost) => cost.status === 'active')
                .reduce(
                    (total, cost) =>
                        total +
                        cost.amount *
                            (cost.currency === data.currency
                                ? 1
                                : Number(
                                      (initialData?.costs ?? []).find(
                                          (saved) => saved.id === cost.id,
                                      )?.exchange_rate ?? 1,
                                  )),
                    0,
                ),
        );

        const baseValue = Number(initialData?.total_base_value ?? 0);

        return {
            charges,
            baseValue,
            landedValue: round2(baseValue + charges),
            capitalized: Number(initialData?.capitalized_amount ?? 0),
            variance: Number(initialData?.variance_amount ?? 0),
        };
    }, [data.costs, data.currency, initialData]);

    const addCost = () =>
        setData('costs', [...data.costs, emptyCost(data.currency)]);

    const updateCost = <K extends keyof ImportCostRow>(
        index: number,
        field: K,
        value: ImportCostRow[K],
    ) =>
        setData(
            'costs',
            data.costs.map((cost, i) =>
                i === index ? { ...cost, [field]: value } : cost,
            ),
        );

    /**
     * Una fila que ya está guardada se desactiva; una que nunca llegó a la base
     * se puede quitar sin más (política de no borrado).
     */
    const removeCost = (index: number) => {
        const cost = data.costs[index];
        const saved = (initialData?.costs ?? []).some(
            (row) => row.id === cost?.id,
        );

        setData(
            'costs',
            saved
                ? data.costs.map((row, i) =>
                      i === index
                          ? { ...row, status: 'inactive' as const }
                          : row,
                  )
                : data.costs.filter((_, i) => i !== index),
        );
    };

    const setCostSupplier = (index: number, option: AjaxOption | null) => {
        if (option) {
            supplierOptions.remember(option);
        }

        updateCost(index, 'supplier_id', option?.value ?? '');
    };

    /**
     * La factura que respalda el cobro. Al elegirla se propone su proveedor:
     * quien cobra el flete es casi siempre quien firma la factura del flete.
     */
    const setCostInvoice = (index: number, option: AjaxOption | null) => {
        if (option) {
            invoiceOptions.remember(option);
        }

        const supplierId = option?.meta?.supplier_id;

        setData(
            'costs',
            data.costs.map((cost, i) =>
                i === index
                    ? {
                          ...cost,
                          sourceable_type: option ? 'purchase_invoice' : '',
                          sourceable_id: option?.value ?? '',
                          supplier_id:
                              typeof supplierId === 'string' &&
                              supplierId !== ''
                                  ? supplierId
                                  : cost.supplier_id,
                      }
                    : cost,
            ),
        );
    };

    const addEntry = (option: AjaxOption | null) => {
        if (!option) {
            return;
        }

        entryOptions.remember(option);

        if (data.entries.some((entry) => entry.entry_id === option.value)) {
            return;
        }

        setData('entries', [
            ...data.entries,
            { id: generateUUID(), entry_id: option.value, status: 'active' },
        ]);
    };

    const removeEntry = (index: number) =>
        setData(
            'entries',
            data.entries.filter((_, i) => i !== index),
        );

    /** Sacar un ítem del reparto: ese artículo no viajó en ese embarque. */
    const toggleLine = (entryLineId: string) =>
        setData(
            'lines',
            data.lines.map((line) =>
                line.entry_line_id === entryLineId
                    ? {
                          ...line,
                          status:
                              line.status === 'active' ? 'inactive' : 'active',
                      }
                    : line,
            ),
        );

    /**
     * La bodega manda sobre las recepciones: cambiarla deja fuera las que
     * llegaron a otra, que es lo que el backend rechazaría al guardar.
     */
    const selectWarehouse = (warehouseId: string) => {
        setData((current) => ({
            ...current,
            warehouse_id: warehouseId,
            entries: [],
            lines: [],
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(imports.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });

            return;
        }

        if (mode === 'edit' && initialData) {
            put(
                imports.update({ company: companyId, id: initialData.id }).url,
                {
                    onSuccess,
                },
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
        companyId,
        options,
        initialData,
        totals,
        addCost,
        updateCost,
        removeCost,
        setCostSupplier,
        setCostInvoice,
        addEntry,
        removeEntry,
        toggleLine,
        selectWarehouse,
        entryOptions,
        supplierOptions,
        invoiceOptions,
    };
}
