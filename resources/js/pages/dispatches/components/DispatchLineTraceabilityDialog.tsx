import { Plus, X } from 'lucide-react';
import { Select2Ajax } from '@/components/select2-ajax';
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
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useDispatchFormContext } from '../contexts/DispatchFormContext';
import { assignedToLots } from '../hooks/useDispatchForm';

/** Valor del select cuando la serie no sale de ningún lote en concreto. */
const NO_LOT = 'none';

interface DispatchLineTraceabilityDialogProps {
    /** La línea que se está detallando; `null` cierra el modal. */
    index: number | null;
    onClose: () => void;
}

/**
 * El lote y las series con las que sale una línea.
 *
 * A diferencia de la entrada, aquí no se estrena nada: el lote y la serie se
 * eligen del maestro, porque el despacho saca mercancía que ya existe.
 */
export function DispatchLineTraceabilityDialog({
    index,
    onClose,
}: DispatchLineTraceabilityDialogProps) {
    const {
        data,
        errors,
        lots,
        serials,
        addLineLot,
        setLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerial,
        setLineSerial,
        setLineSerialLot,
        removeLineSerial,
    } = useDispatchFormContext();

    const line = index !== null ? data.lines[index] : undefined;

    if (index === null || !line) {
        return null;
    }

    const activeLots = line.lots.filter((lot) => lot.status === 'active');
    const activeSerials = line.serials.filter(
        (serial) => serial.status === 'active',
    );

    const assigned = assignedToLots(line);

    /** Los lotes de la línea, para decir de cuál sale cada serie. */
    const lotOptions: OptionType[] = [
        { value: NO_LOT, label: 'Sin lote' },
        ...activeLots
            .filter((lot) => lot.lot_id !== '')
            .map((lot) => ({
                value: lot.lot_id,
                label: lots.optionOf(lot.lot_id)?.label ?? 'Lote',
            })),
    ];

    const fieldError = (field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Trazabilidad de la línea</DialogTitle>
                    <DialogDescription>
                        De qué lotes sale la mercancía y qué unidades concretas
                        se van. Los lotes tienen que sumar la cantidad que se
                        despacha.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex flex-col gap-5">
                    <section className="flex flex-col gap-3">
                        <div className="flex items-center justify-between">
                            <Label className="text-[13px] font-semibold">
                                Lotes
                            </Label>
                            <span
                                className={
                                    activeLots.length > 0 &&
                                    assigned !== line.quantity
                                        ? 'text-[12px] font-semibold text-bad'
                                        : 'text-[12px] text-muted-foreground'
                                }
                            >
                                {assigned} de {line.quantity} repartidas
                            </span>
                        </div>

                        {fieldError('lots') && (
                            <p className="text-sm text-bad">
                                {fieldError('lots')}
                            </p>
                        )}

                        {activeLots.length === 0 && (
                            <p className="text-[13px] text-muted-foreground">
                                Sin lotes: la mercancía sale sin identificar la
                                caja de la que se toma.
                            </p>
                        )}

                        {line.lots.map((lot, lotIndex) =>
                            lot.status !== 'active' ? null : (
                                <div
                                    key={lot.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[2.4fr_1fr_auto]"
                                >
                                    <div className="flex flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Lote
                                        </Label>
                                        <Select2Ajax
                                            url={lots.url}
                                            params={{ item_id: line.item_id }}
                                            value={lots.optionOf(lot.lot_id)}
                                            onChange={(option) =>
                                                setLineLot(
                                                    index,
                                                    lotIndex,
                                                    option,
                                                )
                                            }
                                            isDisabled={line.item_id === ''}
                                            size="md"
                                            placeholder="Busca el lote"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Cantidad
                                        </Label>
                                        <NumberInput
                                            value={lot.quantity}
                                            onValueChange={(value) =>
                                                updateLineLot(
                                                    index,
                                                    lotIndex,
                                                    value,
                                                )
                                            }
                                            min={0}
                                            decimals={4}
                                            className="h-[42px] rounded-[10px]"
                                        />
                                    </div>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-[42px] rounded-[10px] bg-card"
                                        onClick={() =>
                                            removeLineLot(index, lotIndex)
                                        }
                                        aria-label={`Quitar el lote ${lotIndex + 1}`}
                                    >
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            ),
                        )}

                        <Button
                            type="button"
                            variant="outline"
                            className="h-9 w-max rounded-[10px] bg-card font-semibold"
                            onClick={() => addLineLot(index)}
                            disabled={line.item_id === ''}
                        >
                            <Plus />
                            Agregar lote
                        </Button>
                    </section>

                    <section className="flex flex-col gap-3 border-t pt-5">
                        <div className="flex items-center justify-between">
                            <Label className="text-[13px] font-semibold">
                                Series
                            </Label>
                            <span className="text-[12px] text-muted-foreground">
                                {activeSerials.length} para {line.quantity}{' '}
                                unidad{line.quantity !== 1 ? 'es' : ''}
                            </span>
                        </div>

                        {fieldError('serials') && (
                            <p className="text-sm text-bad">
                                {fieldError('serials')}
                            </p>
                        )}

                        {line.serials.map((serial, serialIndex) =>
                            serial.status !== 'active' ? null : (
                                <div
                                    key={serial.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[2.4fr_1.6fr_auto]"
                                >
                                    <Select2Ajax
                                        url={serials.url}
                                        params={{ item_id: line.item_id }}
                                        value={serials.optionOf(
                                            serial.serial_id,
                                        )}
                                        onChange={(option) =>
                                            setLineSerial(
                                                index,
                                                serialIndex,
                                                option,
                                            )
                                        }
                                        isDisabled={line.item_id === ''}
                                        size="md"
                                        placeholder="Busca la serie"
                                    />

                                    {lotOptions.length > 1 ? (
                                        <Select2
                                            options={lotOptions}
                                            value={
                                                lotOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        (serial.lot_id ||
                                                            NO_LOT),
                                                ) ?? null
                                            }
                                            onChange={(option) =>
                                                setLineSerialLot(
                                                    index,
                                                    serialIndex,
                                                    !option ||
                                                        option.value === NO_LOT
                                                        ? ''
                                                        : option.value,
                                                )
                                            }
                                            size="md"
                                            placeholder="Sin lote"
                                        />
                                    ) : (
                                        <span />
                                    )}

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        className="size-[42px] rounded-[10px] bg-card"
                                        onClick={() =>
                                            removeLineSerial(index, serialIndex)
                                        }
                                        aria-label={`Quitar la serie ${serialIndex + 1}`}
                                    >
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            ),
                        )}

                        <Button
                            type="button"
                            variant="outline"
                            className="h-9 w-max rounded-[10px] bg-card font-semibold"
                            onClick={() => addLineSerial(index)}
                            disabled={line.item_id === ''}
                        >
                            <Plus />
                            Agregar serie
                        </Button>
                    </section>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        onClick={onClose}
                        className="h-10 rounded-[11px] font-semibold"
                    >
                        Listo
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
