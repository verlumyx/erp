import { Link, router, usePage } from '@inertiajs/react';
import { ExternalLink, Send, Store } from 'lucide-react';
import { useEffect, useState } from 'react';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import storeCustomers from '@/routes/store-customers';
import { useStorePermissions } from '../hooks/useStorePermissions';
import {
    CUSTOMER_STATUS_LABELS,
    CUSTOMER_STATUS_PILL,
    type StoreCustomer,
} from '../types/Store';

interface StoreClientCardProps {
    clientId: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

type LoadState =
    | { kind: 'loading' }
    | { kind: 'loaded'; customer: StoreCustomer | null }
    | { kind: 'error' };

/**
 * Tarjeta «Tienda» de la ficha del cliente: su comprador vinculado, o el
 * botón para invitarlo. Pide el dato por su cuenta para no tocar el
 * controlador de clientes.
 */
export function StoreClientCard({ clientId }: StoreClientCardProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;
    const { can } = useStorePermissions();

    const [state, setState] = useState<LoadState>({ kind: 'loading' });
    const [inviting, setInviting] = useState(false);

    const url = storeCustomers.byClient({
        company: companyId,
        client: clientId,
    }).url;

    useEffect(() => {
        const abort = new AbortController();

        void (async () => {
            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: abort.signal,
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = (await response.json()) as {
                    data: StoreCustomer | null;
                };
                setState({ kind: 'loaded', customer: payload.data });
            } catch (error) {
                if ((error as Error).name !== 'AbortError') {
                    setState({ kind: 'error' });
                }
            }
        })();

        return () => abort.abort();
    }, [url]);

    const invite = () => {
        setInviting(true);
        router.post(
            storeCustomers.invite({ company: companyId, client: clientId }).url,
            {},
            { preserveScroll: true, onFinish: () => setInviting(false) },
        );
    };

    if (!can('store-customers.list') && !can('store-customers.link')) {
        return null;
    }

    return (
        <Card className="gap-3 rounded-2xl px-[18px] py-4">
            <div className="flex items-center gap-2 text-[13px] font-bold text-muted-foreground">
                <Store className="size-[15px]" />
                Tienda
            </div>

            {state.kind === 'loading' && (
                <p className="text-sm text-muted-foreground">Consultando…</p>
            )}

            {state.kind === 'error' && (
                <p className="text-sm text-muted-foreground">
                    No se pudo consultar el comprador de este cliente.
                </p>
            )}

            {state.kind === 'loaded' && state.customer && (
                <div className="flex flex-col gap-2.5">
                    <div className="flex items-center justify-between gap-4 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Comprador
                        </span>
                        <Link
                            href={
                                storeCustomers.show({
                                    company: companyId,
                                    id: state.customer.id,
                                }).url
                            }
                            className="inline-flex items-center gap-1 font-bold text-primary hover:underline"
                        >
                            {state.customer.code}
                            <ExternalLink className="size-3.5" />
                        </Link>
                    </div>
                    <div className="flex items-center justify-between gap-4 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Correo
                        </span>
                        <b className="truncate font-bold">
                            {state.customer.email}
                        </b>
                    </div>
                    <div className="flex items-center justify-between gap-4 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Estado
                        </span>
                        <StatusPill
                            kind={CUSTOMER_STATUS_PILL[state.customer.status]}
                        >
                            {CUSTOMER_STATUS_LABELS[state.customer.status]}
                        </StatusPill>
                    </div>
                    <div className="flex items-center justify-between gap-4 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Último acceso
                        </span>
                        <b className="font-bold">
                            {state.customer.last_login_at ?? 'Nunca'}
                        </b>
                    </div>
                </div>
            )}

            {state.kind === 'loaded' && !state.customer && (
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-muted-foreground">
                        Este cliente todavía no compra en la tienda.
                    </p>
                    {can('store-customers.link') && (
                        <Button
                            type="button"
                            variant="outline"
                            className="h-9 rounded-[10px]"
                            onClick={invite}
                            disabled={inviting}
                        >
                            <Send />
                            {inviting ? 'Enviando…' : 'Invitar a la tienda'}
                        </Button>
                    )}
                </div>
            )}
        </Card>
    );
}
