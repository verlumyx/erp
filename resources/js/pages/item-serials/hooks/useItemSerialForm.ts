import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import itemLots from '@/routes/item-lots';
import itemSerials from '@/routes/item-serials';
import type { ItemSerial, WarehouseOption } from '../types/ItemSerial';

interface UseItemSerialFormProps {
    warehouses: WarehouseOption[];
    initialData: ItemSerial;
    onSuccess?: () => void;
}

interface ItemSerialFormData {
    item_id: string;
    serial_number: string;
    lot_id: string;
    warehouse_id: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * Solo edición: la serie nace en el documento que recibe la mercancía, así que
 * esta pantalla corrige lo ya registrado y nunca da de alta.
 *
 * El lote viaja por `Select2Ajax` acotado al artículo de la serie —el backend
 * rechaza un lote de otro artículo—; la bodega es un catálogo acotado y llega
 * en las props.
 */
export function useItemSerialForm({
    warehouses,
    initialData,
    onSuccess,
}: UseItemSerialFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, put, processing, errors, reset } =
        useForm<ItemSerialFormData>({
            item_id: initialData.item_id,
            serial_number: initialData.serial_number,
            lot_id: initialData.lot_id ?? '',
            warehouse_id: initialData.warehouse_id ?? '',
        });

    /** El artículo no se cambia: la pantalla no reasigna una serie a otro artículo. */
    const itemOption: AjaxOption = {
        value: initialData.item_id,
        label: `${initialData.item_code ?? ''} — ${initialData.item_name ?? ''}`,
    };

    const [lotOption, setLotOption] = useState<AjaxOption | null>(
        initialData.lot_id
            ? {
                  value: initialData.lot_id,
                  label: initialData.lot_number ?? '',
              }
            : null,
    );

    const selectLot = (option: AjaxOption | null) => {
        setLotOption(option);
        setData('lot_id', option?.value ?? '');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(
            itemSerials.update({ company: companyId, id: initialData.id }).url,
            {
                onSuccess,
            },
        );
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        warehouses,
        itemOption,
        lotOption,
        selectLot,
        lotLookupUrl: itemLots.lookup(companyId).url,
    };
}
