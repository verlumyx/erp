import { useForm, usePage } from '@inertiajs/react';
import { FileCheck } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import clients from '@/routes/clients';
import storeOrders from '@/routes/store-orders';
import {
    DOCUMENT_TYPE_OPTIONS,
    type DocumentType,
    type StoreOrder,
    type YesNo,
} from '../types/Store';

interface StoreOrderConvertDialogProps {
    order: StoreOrder;
    open: boolean;
    onClose: () => void;
    /** Cliente con el mismo correo del comprador, si lo hay. */
    suggestedClient: AjaxOption | null;
    /** El comprador no tiene RIF y hay que pedirlo para crear el cliente. */
    customerNeedsDocument: boolean;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

interface ConvertFormData {
    client_id: string;
    create_client: YesNo;
    document_type: DocumentType | '';
    document_number: string;
}

/**
 * Primer pedido de un comprador sin vínculo: un solo campo obligatorio, el
 * cliente. Al confirmar, el vínculo se guarda en el comprador y no vuelve a
 * preguntarse.
 */
export function StoreOrderConvertDialog({
    order,
    open,
    onClose,
    suggestedClient,
    customerNeedsDocument,
}: StoreOrderConvertDialogProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [client, setClient] = useState<AjaxOption | null>(suggestedClient);

    const { data, setData, put, processing, errors, transform } =
        useForm<ConvertFormData>({
            client_id: suggestedClient?.value ?? '',
            create_client: 'no',
            document_type: '',
            document_number: '',
        });

    const creating = data.create_client === 'yes';
    const asksDocument = creating && customerNeedsDocument;

    const canSubmit = creating
        ? !asksDocument ||
          (data.document_type !== '' && data.document_number.trim() !== '')
        : data.client_id !== '';

    const submit = () => {
        transform((current) =>
            current.create_client === 'yes'
                ? {
                      create_client: 'yes',
                      ...(customerNeedsDocument
                          ? {
                                document_type: current.document_type,
                                document_number: current.document_number,
                            }
                          : {}),
                  }
                : { client_id: current.client_id },
        );

        put(storeOrders.convert({ company: companyId, id: order.id }).url, {
            preserveScroll: true,
        });
    };

    const errorText = (key: string): string | undefined =>
        (errors as Record<string, string | undefined>)[key];

    return (
        <Dialog open={open} onOpenChange={(value) => !value && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileCheck className="size-4" />
                        Convertir {order.code}
                    </DialogTitle>
                    <DialogDescription>
                        Es el primer pedido de {order.buyer_name}. Indica qué
                        cliente es: el vínculo se guarda en su cuenta y los
                        próximos pedidos se convierten con un clic.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-1.5">
                        <Label
                            htmlFor="convert-client"
                            className="text-[13px] font-semibold"
                        >
                            Cliente *
                        </Label>
                        <Select2Ajax
                            inputId="convert-client"
                            url={clients.lookup(companyId).url}
                            params={{ status: 'active' }}
                            value={client}
                            onChange={(option) => {
                                setClient(option);
                                setData('client_id', option?.value ?? '');
                            }}
                            isDisabled={creating}
                            error={!!errorText('client_id')}
                            size="md"
                            placeholder="Busca por código, nombre o RIF"
                        />
                        {suggestedClient && !creating && (
                            <p className="text-[12.5px] text-muted-foreground">
                                Sugerido por tener el mismo correo que el
                                comprador.
                            </p>
                        )}
                        {errorText('client_id') && (
                            <p className="text-sm text-bad">
                                {errorText('client_id')}
                            </p>
                        )}
                    </div>

                    <label className="flex items-start gap-3 rounded-[12px] border border-dashed p-3.5 text-[13.5px]">
                        <Checkbox
                            checked={creating}
                            onCheckedChange={(checked) =>
                                setData(
                                    'create_client',
                                    checked === true ? 'yes' : 'no',
                                )
                            }
                            className="mt-0.5"
                        />
                        <span className="flex flex-col gap-0.5">
                            <span className="font-semibold">
                                Crear cliente con los datos del comprador
                            </span>
                            <span className="text-[12.5px] text-muted-foreground">
                                Nombre, RIF, correo y teléfono salen de la
                                cuenta; el tipo, la lista y el vendedor de los
                                ajustes de la tienda.
                            </span>
                        </span>
                    </label>

                    {asksDocument && (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-[1.4fr_1fr]">
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="convert-doc-type"
                                    className="text-[13px] font-semibold"
                                >
                                    Tipo de RIF *
                                </Label>
                                <Select2
                                    inputId="convert-doc-type"
                                    options={
                                        DOCUMENT_TYPE_OPTIONS as OptionType[]
                                    }
                                    value={
                                        DOCUMENT_TYPE_OPTIONS.find(
                                            (option) =>
                                                option.value ===
                                                data.document_type,
                                        ) ?? null
                                    }
                                    onChange={(option) =>
                                        setData(
                                            'document_type',
                                            (option?.value ?? '') as
                                                | DocumentType
                                                | '',
                                        )
                                    }
                                    error={!!errorText('document_type')}
                                    size="md"
                                    isSearchable={false}
                                />
                                {errorText('document_type') && (
                                    <p className="text-sm text-bad">
                                        {errorText('document_type')}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label
                                    htmlFor="convert-doc-number"
                                    className="text-[13px] font-semibold"
                                >
                                    Número *
                                </Label>
                                <Input
                                    id="convert-doc-number"
                                    inputMode="numeric"
                                    maxLength={15}
                                    value={data.document_number}
                                    placeholder="Solo dígitos"
                                    onChange={(e) =>
                                        setData(
                                            'document_number',
                                            e.target.value.replace(/\D/g, ''),
                                        )
                                    }
                                    className={`h-[42px] rounded-[10px] ${errorText('document_number') ? 'border-bad' : ''}`}
                                />
                                {errorText('document_number') && (
                                    <p className="text-sm text-bad">
                                        {errorText('document_number')}
                                    </p>
                                )}
                            </div>
                        </div>
                    )}

                    {errorText('status') && (
                        <p className="text-sm text-bad">
                            {errorText('status')}
                        </p>
                    )}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={processing || !canSubmit}
                    >
                        {processing ? 'Convirtiendo…' : 'Convertir en orden'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
