import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
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
import { NumberInput } from '@/components/ui/number-input';
import { Select2, type OptionType } from '@/components/ui/select2';
import { useEntryFormContext } from '../contexts/EntryFormContext';
import { acceptedQuantity, assignedToLots } from '../hooks/useEntryForm';

/** Valor del select cuando la serie no sale de ningún lote en concreto. */
const NO_LOT = 'none';

interface EntryLineTraceabilityDialogProps {
    /** La línea que se está detallando; `null` cierra el modal. */
    index: number | null;
    onClose: () => void;
}

/**
 * Convierte lo que el usuario pega —series separadas por coma, punto y coma o
 * salto de línea— en la lista que se añade a la línea.
 */
function parseSerials(value: string): string[] {
    return value
        .split(/[\n,;]/)
        .map((serial) => serial.trim())
        .filter((serial) => serial !== '');
}

/**
 * El lote y las series con las que llega una línea.
 *
 * Viven fuera de la fila porque una misma línea puede llegar repartida en
 * varias cajas, cada una con su número y su vencimiento, y porque una serie
 * identifica una unidad: ninguna de las dos cosas cabe en una columna.
 */
export function EntryLineTraceabilityDialog({
    index,
    onClose,
}: EntryLineTraceabilityDialogProps) {
    const {
        data,
        errors,
        addLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerials,
        updateLineSerial,
        removeLineSerial,
    } = useEntryFormContext();

    const [draft, setDraft] = useState('');

    const line = index !== null ? data.lines[index] : undefined;

    if (index === null || !line) {
        return null;
    }

    const lots = line.lots.filter((lot) => lot.status === 'active');
    const serials = line.serials.filter((serial) => serial.status === 'active');

    const assigned = assignedToLots(line);
    const accepted = acceptedQuantity(line);

    /** Los lotes de la línea, para decir de cuál sale cada serie. */
    const lotOptions: OptionType[] = [
        { value: NO_LOT, label: 'Sin lote' },
        ...lots
            .filter((lot) => lot.lot_number !== '')
            .map((lot) => ({ value: lot.lot_number, label: lot.lot_number })),
    ];

    const fieldError = (field: string) =>
        (errors as Record<string, string | undefined>)[
            `lines.${index}.${field}`
        ];

    const addSerials = () => {
        const numbers = parseSerials(draft);

        if (numbers.length === 0) {
            return;
        }

        addLineSerials(index, numbers);
        setDraft('');
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Trazabilidad de la línea</DialogTitle>
                    <DialogDescription>
                        El lote se registra con el número que trae la caja: si
                        no existe todavía, se crea al confirmar la entrada. Las
                        series identifican una unidad cada una.
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
                                    lots.length > 0 &&
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

                        {lots.length === 0 && (
                            <p className="text-[13px] text-muted-foreground">
                                Sin lotes: la mercancía entra al inventario sin
                                identificar la caja de la que salió.
                            </p>
                        )}

                        {line.lots.map((lot, lotIndex) =>
                            lot.status !== 'active' ? null : (
                                <div
                                    key={lot.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[2fr_1.4fr_1fr_auto]"
                                >
                                    <div className="flex flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Lote del proveedor
                                        </Label>
                                        <Input
                                            value={lot.lot_number}
                                            onChange={(e) =>
                                                updateLineLot(
                                                    index,
                                                    lotIndex,
                                                    'lot_number',
                                                    e.target.value,
                                                )
                                            }
                                            maxLength={60}
                                            placeholder="L-2026-04"
                                            className="h-[42px] rounded-[10px]"
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Vencimiento
                                        </Label>
                                        <Input
                                            type="date"
                                            value={lot.expires_at}
                                            onChange={(e) =>
                                                updateLineLot(
                                                    index,
                                                    lotIndex,
                                                    'expires_at',
                                                    e.target.value,
                                                )
                                            }
                                            className="h-[42px] rounded-[10px]"
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
                                                    'quantity',
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
                                {serials.length} para {accepted} unidad
                                {accepted !== 1 ? 'es' : ''} aceptada
                                {accepted !== 1 ? 's' : ''}
                            </span>
                        </div>

                        {fieldError('serials') && (
                            <p className="text-sm text-bad">
                                {fieldError('serials')}
                            </p>
                        )}

                        <div className="flex items-end gap-2">
                            <div className="flex flex-1 flex-col gap-1.5">
                                <Label className="text-[12px] font-medium text-muted-foreground">
                                    Agregar series
                                </Label>
                                <Input
                                    value={draft}
                                    onChange={(e) => setDraft(e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            addSerials();
                                        }
                                    }}
                                    placeholder="Separadas por coma, punto y coma o salto de línea"
                                    className="h-[42px] rounded-[10px]"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-[42px] rounded-[10px] bg-card font-semibold"
                                onClick={addSerials}
                            >
                                <Plus />
                                Agregar
                            </Button>
                        </div>

                        {line.serials.map((serial, serialIndex) =>
                            serial.status !== 'active' ? null : (
                                <div
                                    key={serial.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[2fr_1.6fr_auto]"
                                >
                                    <Input
                                        value={serial.serial_number}
                                        onChange={(e) =>
                                            updateLineSerial(
                                                index,
                                                serialIndex,
                                                'serial_number',
                                                e.target.value,
                                            )
                                        }
                                        maxLength={100}
                                        className="h-[42px] rounded-[10px]"
                                    />

                                    {lotOptions.length > 1 ? (
                                        <Select2
                                            options={lotOptions}
                                            value={
                                                lotOptions.find(
                                                    (option) =>
                                                        option.value ===
                                                        (serial.lot_number ||
                                                            NO_LOT),
                                                ) ?? null
                                            }
                                            onChange={(option) =>
                                                updateLineSerial(
                                                    index,
                                                    serialIndex,
                                                    'lot_number',
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
