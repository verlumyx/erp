import { router, usePage } from '@inertiajs/react';
import { Link2 } from 'lucide-react';
import { useState } from 'react';
import { Select2Ajax, type AjaxOption } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import clients from '@/routes/clients';
import storeCustomers from '@/routes/store-customers';
import type { StoreCustomer } from '../types/Store';

interface StoreCustomerLinkDialogProps {
    customer: StoreCustomer | null;
    onClose: () => void;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    errors?: Record<string, string>;
    [key: string]: unknown;
}

/**
 * Vínculo manual de un comprador con un cliente del ERP. Se hace una sola
 * vez: después, todos sus pedidos entran con el cliente resuelto.
 */
export function StoreCustomerLinkDialog({
    customer,
    onClose,
}: StoreCustomerLinkDialogProps) {
    const { currentCompany, errors } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const [client, setClient] = useState<AjaxOption | null>(null);
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        if (!customer || !client) {
            return;
        }

        setProcessing(true);
        router.put(
            storeCustomers.link({ company: companyId, id: customer.id }).url,
            { client_id: client.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setClient(null);
                    onClose();
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Dialog
            open={customer !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Link2 className="size-4" />
                        Vincular comprador
                    </DialogTitle>
                    <DialogDescription>
                        {customer?.name} ({customer?.email}) quedará vinculado
                        al cliente que elijas. Sus pedidos entrarán con ese
                        cliente y verá los precios de su lista.
                    </DialogDescription>
                </DialogHeader>
                <div className="flex flex-col gap-1.5">
                    <Label
                        htmlFor="link-client"
                        className="text-[13px] font-semibold"
                    >
                        Cliente *
                    </Label>
                    <Select2Ajax
                        inputId="link-client"
                        url={clients.lookup(companyId).url}
                        params={{ status: 'active' }}
                        value={client}
                        onChange={setClient}
                        error={!!errors?.client_id}
                        size="md"
                        placeholder="Busca por código, nombre o RIF"
                    />
                    {errors?.client_id && (
                        <p className="text-sm text-bad">{errors.client_id}</p>
                    )}
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={processing || !client}
                    >
                        {processing ? 'Vinculando…' : 'Vincular'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
