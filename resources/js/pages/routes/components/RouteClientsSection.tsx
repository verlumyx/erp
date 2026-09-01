import { ArrowDown, ArrowUp, Plus, X } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useRouteFormContext } from '../contexts/RouteFormContext';

/** Valor del select cuando la parada usa la dirección por defecto del cliente. */
const DEFAULT_ADDRESS = 'default';

/**
 * Los clientes fijos de la ruta, en el orden en que se visitan.
 *
 * Es la plantilla, no el recorrido de un día: aquí no hay fecha ni hora. De
 * esta lista —más los despachos pendientes asignados a la ruta— salen las
 * paradas cuando se planifica una fecha concreta.
 */
export function RouteClientsSection() {
    const {
        data,
        errors,
        clientOptions,
        addressesOf,
        addRow,
        removeRow,
        updateRow,
        setRowClient,
        moveRow,
    } = useRouteFormContext();

    const fieldError = (index: number, field: string) =>
        (errors as Record<string, string | undefined>)[
            `clients.${index}.${field}`
        ];

    const addressOptions = (clientId: string): OptionType[] => [
        { value: DEFAULT_ADDRESS, label: 'Dirección por defecto' },
        ...addressesOf(clientId).map((address) => ({
            value: address.id,
            label:
                address.is_default === 'yes'
                    ? `${address.name} (por defecto)`
                    : address.name,
        })),
    ];

    return (
        <div className="flex flex-col gap-4 p-5">
            {errors.clients && (
                <p className="text-sm text-bad">{errors.clients}</p>
            )}

            {data.clients.map((row, index) => {
                const addresses = addressOptions(row.client_id);

                return (
                    <div
                        key={row.id}
                        className="grid grid-cols-1 items-end gap-3 rounded-[12px] border p-4 sm:grid-cols-2 lg:grid-cols-[auto_2.6fr_1.8fr_auto]"
                    >
                        <div className="flex items-center gap-2">
                            <span className="grid size-[30px] shrink-0 place-items-center rounded-[9px] bg-primary-soft text-sm font-extrabold text-primary tabular-nums">
                                {index + 1}
                            </span>
                            <div className="flex flex-col">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-[20px] rounded-[6px] bg-card"
                                    onClick={() => moveRow(index, -1)}
                                    disabled={index === 0}
                                    aria-label="Subir en el recorrido"
                                >
                                    <ArrowUp className="size-3" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="mt-0.5 size-[20px] rounded-[6px] bg-card"
                                    onClick={() => moveRow(index, 1)}
                                    disabled={index === data.clients.length - 1}
                                    aria-label="Bajar en el recorrido"
                                >
                                    <ArrowDown className="size-3" />
                                </Button>
                            </div>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Cliente *
                            </Label>
                            <Select2Ajax
                                url={clientOptions.url}
                                value={clientOptions.optionOf(row.client_id)}
                                onChange={(option) =>
                                    setRowClient(index, option)
                                }
                                error={!!fieldError(index, 'client_id')}
                                size="md"
                                placeholder="Busca por código o nombre"
                            />
                            {fieldError(index, 'client_id') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'client_id')}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <Label className="text-[13px] font-semibold">
                                Dirección
                            </Label>
                            <Select2
                                options={addresses}
                                value={
                                    addresses.find(
                                        (option) =>
                                            option.value ===
                                            (row.client_address_id ||
                                                DEFAULT_ADDRESS),
                                    ) ?? null
                                }
                                onChange={(option) =>
                                    updateRow(
                                        index,
                                        'client_address_id',
                                        !option ||
                                            option.value === DEFAULT_ADDRESS
                                            ? ''
                                            : option.value,
                                    )
                                }
                                isDisabled={row.client_id === ''}
                                error={!!fieldError(index, 'client_address_id')}
                                size="md"
                                placeholder="Dirección por defecto"
                            />
                            {fieldError(index, 'client_address_id') && (
                                <p className="text-sm text-bad">
                                    {fieldError(index, 'client_address_id')}
                                </p>
                            )}
                        </div>

                        <div className="flex items-end">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                className="size-[42px] rounded-[10px] bg-card"
                                onClick={() => removeRow(index)}
                                aria-label="Quitar cliente de la ruta"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>
                    </div>
                );
            })}

            {data.clients.length === 0 && (
                <p className="rounded-[12px] border border-dashed p-6 text-center text-sm text-muted-foreground">
                    La ruta todavía no tiene clientes fijos. Sin ellos, al
                    planificar un día solo aparecerán los clientes con despachos
                    asignados a la ruta.
                </p>
            )}

            <Button
                type="button"
                variant="outline"
                className="h-10 w-max rounded-[11px] bg-card font-semibold"
                onClick={addRow}
            >
                <Plus />
                Agregar cliente
            </Button>
        </div>
    );
}
