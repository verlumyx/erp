import { useForm, usePage } from '@inertiajs/react';
import { joinPhone, splitPhone } from '@/lib/phone';
import { generateUUID } from '@/lib/utils';
import clients from '@/routes/clients';
import type { AddressType, Client, DocumentType, YesNo } from '../types/Client';

interface UseClientFormProps {
    mode: 'create' | 'edit';
    initialData?: Client;
    onSuccess?: () => void;
}

export interface ClientContactRow {
    id: string;
    name: string;
    position: string;
    email: string;
    phone: string;
    is_primary: YesNo;
}

export interface ClientAddressRow {
    id: string;
    type: AddressType;
    name: string;
    address: string;
    city: string;
    state: string;
    country: string;
    latitude: string;
    longitude: string;
    is_default: YesNo;
}

interface ClientFormData {
    id: string;
    client_type_id: string;
    price_list_id: string;
    name: string;
    legal_name: string;
    document_type: DocumentType;
    document_number: string;
    phone_prefix: string;
    phone: string;
    mobile: string;
    email: string;
    address: string;
    city: string;
    state: string;
    country: string;
    payment_term_days: number;
    credit_limit: number;
    credit_blocked: YesNo;
    discount_percent: number;
    salesperson_id: string;
    latitude: string;
    longitude: string;
    notes: string;
    contacts: ClientContactRow[];
    addresses: ClientAddressRow[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function emptyContact(): ClientContactRow {
    return {
        id: generateUUID(),
        name: '',
        position: '',
        email: '',
        phone: '',
        is_primary: 'no',
    };
}

function emptyAddress(): ClientAddressRow {
    return {
        id: generateUUID(),
        type: 'shipping',
        name: '',
        address: '',
        city: '',
        state: '',
        country: '',
        latitude: '',
        longitude: '',
        is_default: 'no',
    };
}

/**
 * Solo se editan las filas activas del detalle: las inactivas se conservan en
 * la base por la política de no borrado, pero no vuelven al formulario.
 */
function contactRows(client?: Client): ClientContactRow[] {
    return (client?.contacts ?? [])
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

function addressRows(client?: Client): ClientAddressRow[] {
    return (client?.addresses ?? [])
        .filter((address) => address.status === 'active')
        .map((address) => ({
            id: address.id,
            type: address.type,
            name: address.name,
            address: address.address,
            city: address.city ?? '',
            state: address.state ?? '',
            country: address.country ?? '',
            latitude: address.latitude ?? '',
            longitude: address.longitude ?? '',
            is_default: address.is_default,
        }));
}

export function useClientForm({
    mode,
    initialData,
    onSuccess,
}: UseClientFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const initialPhone = splitPhone(initialData?.phone);

    const { data, setData, transform, post, put, processing, errors, reset } =
        useForm<ClientFormData>({
            id: initialData?.id ?? generateUUID(),
            client_type_id: initialData?.client_type_id ?? '',
            price_list_id: initialData?.price_list_id ?? '',
            name: initialData?.name ?? '',
            legal_name: initialData?.legal_name ?? '',
            document_type: initialData?.document_type ?? 'V',
            document_number: initialData?.document_number ?? '',
            phone_prefix: initialPhone.prefix,
            phone: initialPhone.number,
            mobile: initialData?.mobile ?? '',
            email: initialData?.email ?? '',
            address: initialData?.address ?? '',
            city: initialData?.city ?? '',
            state: initialData?.state ?? '',
            country: initialData?.country ?? '',
            payment_term_days: Number(initialData?.payment_term_days ?? 0),
            credit_limit: Number(initialData?.credit_limit ?? 0),
            credit_blocked: initialData?.credit_blocked ?? 'no',
            discount_percent: Number(initialData?.discount_percent ?? 0),
            salesperson_id: initialData?.salesperson_id ?? '',
            latitude: initialData?.latitude ?? '',
            longitude: initialData?.longitude ?? '',
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

    const updateContact = <K extends keyof ClientContactRow>(
        index: number,
        field: K,
        value: ClientContactRow[K],
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

    const updateAddress = <K extends keyof ClientAddressRow>(
        index: number,
        field: K,
        value: ClientAddressRow[K],
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

        transform(({ phone_prefix, phone, ...rest }) => ({
            ...rest,
            phone: joinPhone(phone_prefix, phone),
        }));

        if (mode === 'create') {
            post(clients.store(companyId).url, {
                onSuccess: () => {
                    reset();
                    onSuccess?.();
                },
            });
        } else if (mode === 'edit' && initialData) {
            put(
                clients.update({ company: companyId, id: initialData.id }).url,
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
