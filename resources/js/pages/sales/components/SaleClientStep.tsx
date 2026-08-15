import { Check, Contact, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useSaleFormContext } from '../contexts/SaleFormContext';
import type { ClientOption } from '../types/Sale';

/**
 * Paso 1 del wizard: seleccionar el cliente (solo activos). La búsqueda se
 * resuelve en el servidor (GET sales.clients.search) con debounce, de modo que
 * no se cargan todos los clientes de la compañía: la lista inicial es un lote
 * pequeño y al escribir se consultan los coincidentes.
 */
export function SaleClientStep() {
    const { companyId, clients, form, selectClient } = useSaleFormContext();

    const [search, setSearch] = useState('');
    const [serverResults, setServerResults] = useState<ClientOption[]>([]);
    const [loading, setLoading] = useState(false);
    const [selectedClient, setSelectedClient] = useState<ClientOption | null>(
        clients.find((c) => c.id === form.data.client_id) ?? null,
    );

    const term = search.trim();

    useEffect(() => {
        // Sin término: la lista mostrada es el lote inicial; no se consulta nada.
        if (term === '') {
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => {
            setLoading(true);
            const url = `/${companyId}/sales/clients/search?q=${encodeURIComponent(term)}`;
            fetch(url, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: controller.signal,
            })
                .then((response) =>
                    response.ok ? response.json() : { data: [] },
                )
                .then((payload) => setServerResults(payload.data ?? []))
                .catch(() => {
                    /* abortado o error de red: se ignora */
                })
                .finally(() => setLoading(false));
        }, 300);

        return () => {
            controller.abort();
            clearTimeout(timeout);
        };
    }, [term, companyId]);

    // El lote inicial cuando no se busca; los resultados del servidor al escribir.
    const results = term === '' ? clients : serverResults;

    const pick = (client: ClientOption) => {
        setSelectedClient(client);
        selectClient(client.id);
    };

    // El cliente seleccionado puede no estar en los resultados actuales: se ancla arriba.
    const showSelectedSeparately =
        selectedClient !== null &&
        !results.some((c) => c.id === selectedClient.id);

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="client-search">Buscar cliente</Label>
                <div className="relative">
                    <Input
                        id="client-search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Nombre o código del cliente..."
                    />
                    {loading && (
                        <Loader2 className="absolute top-1/2 right-3 size-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                    )}
                </div>
            </div>

            {form.errors.client_id && (
                <p className="text-sm text-bad">{form.errors.client_id}</p>
            )}

            <div className="flex max-h-80 flex-col gap-2 overflow-auto">
                {showSelectedSeparately && (
                    <ClientRow
                        client={selectedClient!}
                        selected
                        onClick={() => pick(selectedClient!)}
                    />
                )}
                {results.map((client) => (
                    <ClientRow
                        key={client.id}
                        client={client}
                        selected={form.data.client_id === client.id}
                        onClick={() => pick(client)}
                    />
                ))}
                {!loading && results.length === 0 && (
                    <p className="p-6 text-center text-sm text-muted-foreground">
                        {search.trim() === ''
                            ? 'No hay clientes activos.'
                            : 'No hay clientes activos que coincidan.'}
                    </p>
                )}
            </div>
        </div>
    );
}

function ClientRow({
    client,
    selected,
    onClick,
}: {
    client: ClientOption;
    selected: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`flex items-center justify-between gap-3 rounded-[12px] border p-3.5 text-left transition-colors ${
                selected ? 'border-primary bg-primary/5' : 'hover:bg-muted'
            }`}
        >
            <span className="flex items-center gap-3">
                <span className="grid size-10 place-items-center rounded-[10px] border bg-muted text-muted-foreground">
                    <Contact className="size-5" />
                </span>
                <span className="flex flex-col">
                    <span className="font-bold">{client.name}</span>
                    <span className="text-[12.5px] text-muted-foreground">
                        {client.code ?? '—'}
                    </span>
                </span>
            </span>
            {selected && <Check className="size-5 text-primary" />}
        </button>
    );
}
