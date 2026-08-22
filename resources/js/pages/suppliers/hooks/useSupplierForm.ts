import { useForm, usePage } from '@inertiajs/react';
import { useConfiguration } from '@/hooks/use-configuration';
import { generateUUID } from '@/lib/utils';
import suppliers from '@/routes/suppliers';
import type {
    AddressType,
    DocumentType,
    Supplier,
    YesNo,
} from '../types/Supplier';

interface UseSupplierFormProps {
    mode: 'create' | 'edit';
    initialData?: Supplier;
    onSuccess?: () => void;
}

export interface SupplierContactRow {
    id: string;
    name: string;
    position: string;
    email: string;
    phone: string;
    is_primary: YesNo;
}

export interface SupplierAddressRow {
    id: string;
    type: AddressType;
    address: string;
    city: string;
    state: string;
    country: string;
    is_default: YesNo;
}

interface SupplierFormData {
    id: string;
    supplier_type_id: string;
    name: string;
    legal_name: string;
    document_type: DocumentType;
    document_number: string;
    email: string;
    phone: string;
    mobile: string;
    website: string;
    address: string;
    city: string;
    state: string;
    country: string;
    currency: string;
    payment_term_days: number;
    credit_limit: number;
    lead_time_days: number;
    notes: string;
    contacts: SupplierContactRow[];
    addresses: SupplierAddressRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function emptyContact(): SupplierContactRow {
    return {
        id: generateUUID(),
        name: '',
        position: '',
        email: '',
        phone: '',
        is_primary: 'no',
    };
}

function emptyAddress(): SupplierAddressRow {
    return {
        id: generateUUID(),
        type: 'billing',
        address: '',
        city: '',
        state: '',
        country: '',
        is_default: 'no',
    };
}

/**
 * Solo se editan las filas activas del detalle: las inactivas se conservan en
 * la base por la política de no borrado, pero no vuelven al formulario.
 */
function contactRows(supplier?: Supplier): SupplierContactRow[] {
    return (supplier?.contacts ?? [])
        .filter((contact) => contact.status === 'active')
        .map((contact) => ({
            id: contact.id,
            name: contact.name,
            position: contact.position ?? '',
            email: contact.email ?? '',
            phone: contact.phone ?? '',
            is_primary: contact.is_primary,
        }));
}

function addressRows(supplier?: Supplier): SupplierAddressRow[] {
    return (supplier?.addresses ?? [])
        .filter((address) => address.status === 'active')
        .map((address) => ({
            id: address.id,
            type: address.type,
            address: address.address,
            city: address.city ?? '',
            state: address.state ?? '',
            country: address.country ?? '',
            is_default: address.is_default,
        }));
}

export function useSupplierForm({
    mode,
    initialData,
    onSuccess,
}: UseSupplierFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const configuration = useConfiguration();

    const { data, setData, post, put, processing, errors, reset } =
        useForm<SupplierFormData>({
            id: initialData?.id ?? generateUUID(),
            supplier_type_id: initialData?.supplier_type_id ?? '',
            name: initialData?.name ?? '',
            legal_name: initialData?.legal_name ?? '',
            document_type: initialData?.document_type ?? 'J',
            document_number: initialData?.document_number ?? '',
            email: initialData?.email ?? '',
            phone: initialData?.phone ?? '',
            mobile: initialData?.mobile ?? '',
            website: initialData?.website ?? '',
            address: initialData?.address ?? '',
            city: initialData?.city ?? '',
            state: initialData?.state ?? '',
            country: initialData?.country ?? '',
            /** Un proveedor nace en la moneda en la que la empresa lleva sus cifras. */
            currency:
                initialData?.currency ?? configuration?.base_currency ?? '',
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            credit_limit: Number(initialData?.credit_limit ?? 0),
            lead_time_days: Number(initialData?.lead_time_days ?? 0),
            notes: initialData?.notes ?? '',
            contacts: contactRows(initialData),
            addresses: addressRows(initialData),
        });

    const addContact = () =>
        setData('contacts', [...data.contacts, emptyContact()]);

    const removeContact = (index: number) =>
        setData(
            'contacts',
            data.contacts.filter((_, i) => i !== index),
        );

    const updateContact = <K extends keyof SupplierContactRow>(
        index: number,
        field: K,
        value: SupplierContactRow[K],
    ) =>
        setData(
            'contacts',
            data.contacts.map((contact, i) =>
                i === index ? { ...contact, [field]: value } : contact,
            ),
        );

    /** El contacto principal es único: marcar uno desmarca al anterior. */
    const setPrimaryContact = (index: number) =>
        setData(
            'contacts',
            data.contacts.map((contact, i) => ({
                ...contact,
                is_primary: (i === index ? 'yes' : 'no') as YesNo,
            })),
        );

    const addAddress = () =>
        setData('addresses', [...data.addresses, emptyAddress()]);

    const removeAddress = (index: number) =>
        setData(
            'addresses',
            data.addresses.filter((_, i) => i !== index),
        );

    const updateAddress = <K extends keyof SupplierAddressRow>(
        index: number,
        field: K,
        value: SupplierAddressRow[K],
    ) =>
        setData(
            'addresses',
            data.addresses.map((address, i) =>
                i === index ? { ...address, [field]: value } : address,
            ),
        );

    /** La dirección sugerida es una por tipo. */
    const setDefaultAddress = (index: number) =>
        setData(
            'addresses',
            data.addresses.map((address, i) => {
                if (i === index) {
                    return { ...address, is_default: 'yes' as YesNo };
                }

                return address.type === data.addresses[index].type
                    ? { ...address, is_default: 'no' as YesNo }
                    : address;
            }),
        );

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(suppliers.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                suppliers.update({ company: companyId, id: initialData.id })
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
        addContact,
        removeContact,
        updateContact,
        setPrimaryContact,
        addAddress,
        removeAddress,
        updateAddress,
        setDefaultAddress,
    };
}
