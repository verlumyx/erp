import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { AjaxOption } from '@/components/select2-ajax';
import itemLots from '@/routes/item-lots';
import suppliers from '@/routes/suppliers';
import type { ItemLot } from '../types/ItemLot';

interface UseItemLotFormProps {
    initialData: ItemLot;
    onSuccess?: () => void;
}

interface ItemLotFormData {
    item_id: string;
    lot_number: string;
    manufactured_at: string;
    expires_at: string;
    supplier_id: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * Solo edición: el lote nace en el documento que recibe la mercancía, así que
 * esta pantalla corrige lo ya registrado y nunca da de alta.
 *
 * El artículo y el proveedor viajan por `Select2Ajax`. La opción inicial se
 * arma con lo que el Resource ya devuelve, sin una segunda ida al servidor.
 */
export function useItemLotForm({
    initialData,
    onSuccess,
}: UseItemLotFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const { data, setData, put, processing, errors, reset } =
        useForm<ItemLotFormData>({
            item_id: initialData.item_id,
            lot_number: initialData.lot_number,
            manufactured_at: initialData.manufactured_at ?? '',
            expires_at: initialData.expires_at ?? '',
            supplier_id: initialData.supplier_id ?? '',
        });

    /** El artículo no se cambia: la pantalla no reasigna un lote a otro artículo. */
    const itemOption: AjaxOption = {
        value: initialData.item_id,
        label: `${initialData.item_code ?? ''} — ${initialData.item_name ?? ''}`,
    };

    const [supplierOption, setSupplierOption] = useState<AjaxOption | null>(
        initialData.supplier_id
            ? {
                  value: initialData.supplier_id,
                  label: initialData.supplier_name ?? '',
              }
            : null,
    );

    const selectSupplier = (option: AjaxOption | null) => {
        setSupplierOption(option);
        setData('supplier_id', option?.value ?? '');
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        put(itemLots.update({ company: companyId, id: initialData.id }).url, {
            onSuccess,
        });
    };

    return {
        data,
        setData,
        processing,
        errors,
        handleSubmit,
        reset,
        itemOption,
        supplierOption,
        selectSupplier,
        supplierLookupUrl: suppliers.lookup(companyId).url,
    };
}
