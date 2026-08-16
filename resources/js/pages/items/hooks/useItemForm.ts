import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import items from '@/routes/items';
import type { CostMethod, Item, ItemType, YesNo } from '../types/Item';

interface UseItemFormProps {
    mode: 'create' | 'edit';
    initialData?: Item;
    onSuccess?: () => void;
}

export interface ItemUnitRow {
    measurement_unit_id: string;
    is_base: YesNo;
    conversion_factor: number;
}

export interface ItemPriceRow {
    price_list_id: string;
    price: number;
    currency: string;
}

interface ItemFormData {
    id: string;
    sku: string;
    barcode: string;
    name: string;
    description: string;
    type: ItemType;
    category_id: string;
    cost_method: CostMethod;
    standard_cost: number;
    min_price: number;
    is_purchasable: YesNo;
    is_sellable: YesNo;
    min_stock: number;
    max_stock: number;
    reorder_quantity: number;
    weight: number;
    volume: number;
    notes: string;
    units: ItemUnitRow[];
    prices: ItemPriceRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

const emptyUnit: ItemUnitRow = {
    measurement_unit_id: '',
    is_base: 'no',
    conversion_factor: 1,
};

const emptyPrice: ItemPriceRow = {
    price_list_id: '',
    price: 0,
    currency: 'USD',
};

/**
 * Solo se editan las filas activas del detalle: las inactivas se conservan en
 * la base por la política de no borrado, pero no vuelven al formulario.
 */
function unitRows(item?: Item): ItemUnitRow[] {
    const rows = (item?.units ?? [])
        .filter((unit) => unit.status === 'active')
        .map((unit) => ({
            measurement_unit_id: unit.measurement_unit_id,
            is_base: unit.is_base,
            conversion_factor: Number(unit.conversion_factor),
        }));

    return rows.length > 0 ? rows : [{ ...emptyUnit, is_base: 'yes' }];
}

function priceRows(item?: Item): ItemPriceRow[] {
    return (item?.prices ?? [])
        .filter((price) => price.status === 'active')
        .map((price) => ({
            price_list_id: price.price_list_id,
            price: Number(price.price),
            currency: price.currency,
        }));
}

export function useItemForm({
    mode,
    initialData,
    onSuccess,
}: UseItemFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, post, put, processing, errors, reset } =
        useForm<ItemFormData>({
            id: initialData?.id ?? generateUUID(),
            sku: initialData?.sku ?? '',
            barcode: initialData?.barcode ?? '',
            name: initialData?.name ?? '',
            description: initialData?.description ?? '',
            type: initialData?.type ?? 'inventoried',
            category_id: initialData?.category_id ?? '',
            cost_method: initialData?.cost_method ?? 'average',
            standard_cost: Number(initialData?.standard_cost ?? 0),
            min_price: Number(initialData?.min_price ?? 0),
            is_purchasable: initialData?.is_purchasable ?? 'yes',
            is_sellable: initialData?.is_sellable ?? 'yes',
            min_stock: Number(initialData?.min_stock ?? 0),
            max_stock: Number(initialData?.max_stock ?? 0),
            reorder_quantity: Number(initialData?.reorder_quantity ?? 0),
            weight: Number(initialData?.weight ?? 0),
            volume: Number(initialData?.volume ?? 0),
            notes: initialData?.notes ?? '',
            units: unitRows(initialData),
            prices: priceRows(initialData),
        });

    const addUnit = () => setData('units', [...data.units, { ...emptyUnit }]);

    const removeUnit = (index: number) =>
        setData(
            'units',
            data.units.filter((_, i) => i !== index),
        );

    const updateUnit = <K extends keyof ItemUnitRow>(
        index: number,
        field: K,
        value: ItemUnitRow[K],
    ) =>
        setData(
            'units',
            data.units.map((unit, i) =>
                i === index ? { ...unit, [field]: value } : unit,
            ),
        );

    /** La unidad base es única y siempre convierte 1:1. */
    const setBaseUnit = (index: number) =>
        setData(
            'units',
            data.units.map((unit, i) =>
                i === index
                    ? {
                          ...unit,
                          is_base: 'yes' as YesNo,
                          conversion_factor: 1,
                      }
                    : { ...unit, is_base: 'no' as YesNo },
            ),
        );

    const addPrice = () =>
        setData('prices', [...data.prices, { ...emptyPrice }]);

    const removePrice = (index: number) =>
        setData(
            'prices',
            data.prices.filter((_, i) => i !== index),
        );

    const updatePrice = <K extends keyof ItemPriceRow>(
        index: number,
        field: K,
        value: ItemPriceRow[K],
    ) =>
        setData(
            'prices',
            data.prices.map((price, i) =>
                i === index ? { ...price, [field]: value } : price,
            ),
        );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(items.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(items.update({ company: companyId, id: initialData.id }).url, {
                onSuccess,
            });
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
        addUnit,
        removeUnit,
        updateUnit,
        setBaseUnit,
        addPrice,
        removePrice,
        updatePrice,
    };
}
