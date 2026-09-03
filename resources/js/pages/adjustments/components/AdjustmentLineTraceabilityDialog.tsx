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
import { cn } from '@/lib/utils';
import { useAdjustmentFormContext } from '../contexts/AdjustmentFormContext';
import { countedInLots } from '../hooks/useAdjustmentForm';

/** Valor del select cuando la serie no sale de ningún lote en concreto. */
const NO_LOT = 'none';

interface AdjustmentLineTraceabilityDialogProps {
    /** La línea que se está detallando; `null` cierra el modal. */
    index: number | null;
    onClose: () => void;
}

/**
 * En qué lotes se contó una línea y qué unidades concretas entran en el conteo.
 *
 * Cada lote se compara contra su propia existencia: por eso el modal enseña lo
 * que el sistema dice de cada uno y lo que sobra o falta. Las series nombran
 * unidades —las que se encontraron o las que no aparecieron—, no llevan
 * cantidad.
 */
export function AdjustmentLineTraceabilityDialog({
    index,
    onClose,
}: AdjustmentLineTraceabilityDialogProps) {
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
        lotAmountsOf,
    } = useAdjustmentFormContext();

    const line = index !== null ? data.lines[index] : undefined;

    if (index === null || !line) {
        return null;
    }

    const activeLots = line.lots.filter((lot) => lot.status === 'active');
    const activeSerials = line.serials.filter(
        (serial) => serial.status === 'active',
    );

    const counted = countedInLots(line);

    /** Los lotes de la línea, para decir a cuál pertenece cada serie. */
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
                        En qué lotes se contó y qué unidades entran en el
                        conteo. Los lotes tienen que sumar lo que se contó en la
                        línea.
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
                                    counted !== line.counted_quantity
                                        ? 'text-[12px] font-semibold text-bad'
                                        : 'text-[12px] text-muted-foreground'
                                }
                            >
                                {counted} de {line.counted_quantity} repartidas
                            </span>
                        </div>

                        {fieldError('lots') && (
                            <p className="text-sm text-bad">
                                {fieldError('lots')}
                            </p>
                        )}

                        {activeLots.length === 0 && (
                            <p className="text-[13px] text-muted-foreground">
                                Sin lotes: la línea se compara contra el saldo
                                de la ubicación entera.
                            </p>
                        )}

                        {line.lots.map((lot, lotIndex) => {
                            if (lot.status !== 'active') {
                                return null;
                            }

                            const amounts = lotAmountsOf(line, lot);

                            return (
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
                                        <span className="text-[12px] text-muted-foreground">
                                            El sistema dice {amounts.system}
                                            {lot.lot_id !== '' && (
                                                <>
                                                    {' · '}
                                                    <b
                                                        className={cn(
                                                            'font-bold tabular-nums',
                                                            amounts.difference <
                                                                0
                                                                ? 'text-bad'
                                                                : amounts.difference >
                                                                    0
                                                                  ? 'text-ok'
                                                                  : 'text-foreground',
                                                        )}
                                                    >
                                                        {amounts.difference > 0
                                                            ? '+'
                                                            : ''}
                                                        {amounts.difference}
                                                    </b>
                                                </>
                                            )}
                                        </span>
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Contado
                                        </Label>
                                        <NumberInput
                                            value={lot.counted_quantity}
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
                                        className="mb-6 size-[42px] rounded-[10px] bg-card"
                                        onClick={() =>
                                            removeLineLot(index, lotIndex)
                                        }
                                        aria-label={`Quitar el lote ${lotIndex + 1}`}
                                    >
                                        <X className="size-4" />
                                    </Button>
                                </div>
                            );
                        })}

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
                                {activeSerials.length} unidad
                                {activeSerials.length !== 1 ? 'es' : ''}{' '}
                                nombrada
                                {activeSerials.length !== 1 ? 's' : ''}
                            </span>
                        </div>

                        <p className="text-[12px] text-muted-foreground">
                            Una serie es una unidad: nombra las que se contaron
                            o las que faltan.
                        </p>

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
