import { Loader2, Plus, Sparkles, Trash2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
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
        companyId,
        data,
        errors,
        addLineLot,
        updateLineLot,
        removeLineLot,
        addLineSerials,
        updateLineSerial,
        removeLineSerial,
        removeAllLineSerials,
        catalog,
    } = useEntryFormContext();

    const [draft, setDraft] = useState('');

    const line = index !== null ? data.lines[index] : undefined;

    const lots = useMemo(
        () => (line ? line.lots.filter((lot) => lot.status === 'active') : []),
        [line?.lots],
    );
    const serials = useMemo(
        () =>
            line
                ? line.serials.filter((serial) => serial.status === 'active')
                : [],
        [line?.serials],
    );

    const assigned = line ? assignedToLots(line) : 0;
    const accepted = line ? acceptedQuantity(line) : 0;
    const remaining = Math.max(0, accepted - serials.length);

    const catalogItem =
        line && line.item_id && catalog
            ? catalog.itemOf(line.item_id)
            : undefined;
    const defaultPrefix = useMemo(() => {
        if (catalogItem?.sku) {
            return `${catalogItem.sku}-`;
        }
        if (catalogItem?.code) {
            return `${catalogItem.code}-`;
        }
        return 'SN-';
    }, [catalogItem?.sku, catalogItem?.code]);

    const [isAuto, setIsAuto] = useState(false);
    const [autoPrefix, setAutoPrefix] = useState(defaultPrefix);
    const [prefixManuallyEdited, setPrefixManuallyEdited] = useState(false);

    // Consulta la última serie generada para este artículo en la empresa
    const [backendSequence, setBackendSequence] = useState<{
        item_id: string;
        last_serial_number: string | null;
        prefix: string;
        next_number: number;
        pad_length: number;
        suggested_serial: string;
        existing_serials: string[];
    } | null>(null);
    const [loadingSequence, setLoadingSequence] = useState(false);

    useEffect(() => {
        if (!line?.item_id || !companyId) {
            return;
        }

        let isMounted = true;
        setLoadingSequence(true);

        fetch(
            `/${companyId}/item-serials/last-sequence?item_id=${encodeURIComponent(line.item_id)}`,
            {
                headers: { Accept: 'application/json' },
            },
        )
            .then((res) => {
                if (!res.ok) throw new Error('Error al consultar secuencia');
                return res.json();
            })
            .then((resData) => {
                if (!isMounted) return;
                setBackendSequence(resData);
                if (!prefixManuallyEdited && resData.prefix) {
                    setAutoPrefix(resData.prefix);
                }
                if (!autoStartDirty && resData.next_number) {
                    setAutoStart(resData.next_number);
                }
            })
            .catch(() => {
                // Conservar defaults si falla
            })
            .finally(() => {
                if (isMounted) setLoadingSequence(false);
            });

        return () => {
            isMounted = false;
        };
    }, [line?.item_id, companyId]);

    useEffect(() => {
        if (!prefixManuallyEdited && defaultPrefix && !backendSequence?.prefix) {
            setAutoPrefix(defaultPrefix);
        }
    }, [defaultPrefix, prefixManuallyEdited, backendSequence?.prefix]);

    // Series ya ingresadas en el formulario actual para este mismo artículo en otras líneas
    const otherFormSerials = useMemo(() => {
        const set = new Set<string>();
        if (!line?.item_id) return set;
        data.lines.forEach((l, lIdx) => {
            if (l.item_id === line.item_id && lIdx !== index) {
                l.serials.forEach((s) => {
                    if (
                        s.status === 'active' &&
                        s.serial_number.trim() !== ''
                    ) {
                        set.add(s.serial_number.trim());
                    }
                });
            }
        });
        return set;
    }, [data.lines, line?.item_id, index]);

    // Todas las series que ya existen para este artículo (en BD + en otras líneas de esta entrada)
    const allExistingSerials = useMemo(() => {
        const set = new Set<string>(backendSequence?.existing_serials ?? []);
        otherFormSerials.forEach((s) => set.add(s));
        return set;
    }, [backendSequence?.existing_serials, otherFormSerials]);

    // Determina el siguiente número consecutivo libre a partir de la última serie del artículo
    const nextSuggestedStart = useMemo(() => {
        const cleanPrefix = autoPrefix.trim();
        let maxNum = 0;

        // Comprobar las series activas de esta línea
        serials.forEach((s) => {
            if (s.serial_number.startsWith(cleanPrefix)) {
                const rest = s.serial_number.slice(cleanPrefix.length);
                if (/^\d+$/.test(rest)) {
                    const num = parseInt(rest, 10);
                    if (!isNaN(num) && num > maxNum) {
                        maxNum = num;
                    }
                }
            }
        });

        // Comprobar las series existentes en BD y en otras líneas
        allExistingSerials.forEach((s) => {
            if (cleanPrefix === '' || s.startsWith(cleanPrefix)) {
                const rest = s.slice(cleanPrefix.length);
                if (/^\d+$/.test(rest)) {
                    const num = parseInt(rest, 10);
                    if (!isNaN(num) && num > maxNum) {
                        maxNum = num;
                    }
                }
            }
        });

        if (maxNum > 0) {
            return maxNum + 1;
        }

        if (backendSequence && backendSequence.prefix === cleanPrefix) {
            return backendSequence.next_number;
        }

        return 1;
    }, [autoPrefix, serials, allExistingSerials, backendSequence]);

    const [autoStart, setAutoStart] = useState<number>(1);
    const [autoStartDirty, setAutoStartDirty] = useState(false);

    useEffect(() => {
        if (!autoStartDirty) {
            setAutoStart(nextSuggestedStart);
        }
    }, [nextSuggestedStart, autoStartDirty]);

    const [autoCount, setAutoCount] = useState<number>(remaining || 1);
    const [autoCountDirty, setAutoCountDirty] = useState(false);

    useEffect(() => {
        if (!autoCountDirty) {
            setAutoCount(remaining > 0 ? remaining : 1);
        }
    }, [remaining, autoCountDirty]);

    const [autoLot, setAutoLot] = useState<string>(NO_LOT);

    useEffect(() => {
        if (lots.length === 1 && lots[0].lot_number) {
            setAutoLot(lots[0].lot_number);
        }
    }, [lots]);

    const previewExample = useMemo(() => {
        const cleanPrefix = autoPrefix.trim();
        const start = Math.max(1, Math.floor(autoStart || 1));
        const count = Math.max(1, Math.floor(autoCount || 1));
        const endCandidate = start + count - 1;
        const padLength = Math.max(
            backendSequence?.pad_length ?? 4,
            String(endCandidate).length,
        );

        let current = start;
        let first = '';
        let last = '';

        for (let i = 0; i < count; i++) {
            let candidate = `${cleanPrefix}${String(current).padStart(padLength, '0')}`;
            while (allExistingSerials.has(candidate)) {
                current++;
                candidate = `${cleanPrefix}${String(current).padStart(padLength, '0')}`;
            }
            if (i === 0) first = candidate;
            last = candidate;
            current++;
        }

        if (count === 1) {
            return first;
        }
        return `${first} … ${last}`;
    }, [
        autoPrefix,
        autoStart,
        autoCount,
        allExistingSerials,
        backendSequence?.pad_length,
    ]);

    if (index === null || !line) {
        return null;
    }

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

    const handleGenerateSerials = () => {
        const count = Math.max(1, Math.floor(autoCount || 1));
        let current = Math.max(1, Math.floor(autoStart || 1));
        const cleanPrefix = autoPrefix.trim();
        const endCandidate = current + count - 1;
        const padLength = Math.max(
            backendSequence?.pad_length ?? 4,
            String(endCandidate).length,
        );

        const generated: string[] = [];
        const usedInBatch = new Set<string>();

        for (let i = 0; i < count; i++) {
            let candidate = `${cleanPrefix}${String(current).padStart(padLength, '0')}`;
            // Saltar cualquier serie que ya exista para este artículo para garantizar unicidad estricta
            while (
                allExistingSerials.has(candidate) ||
                usedInBatch.has(candidate)
            ) {
                current++;
                candidate = `${cleanPrefix}${String(current).padStart(padLength, '0')}`;
            }
            generated.push(candidate);
            usedInBatch.add(candidate);
            current++;
        }

        const lotToAssign = autoLot === NO_LOT ? '' : autoLot;
        addLineSerials(index, generated, lotToAssign);

        setAutoStart(current);
        setAutoStartDirty(false);
        setAutoCountDirty(false);
    };

    const isDuplicateSerial = (serialNumber: string, serialId: string) => {
        const trimmed = serialNumber.trim();
        if (!trimmed) return false;

        // Repetida dentro de esta misma línea
        const inLineCount = line.serials.filter(
            (s) => s.status === 'active' && s.serial_number.trim() === trimmed,
        ).length;
        if (inLineCount > 1) return true;

        // Presente en otra línea de esta misma entrada
        if (otherFormSerials.has(trimmed)) return true;

        // Presente en base de datos para este artículo
        if (
            (backendSequence?.existing_serials ?? []).includes(trimmed) &&
            !line.serials.some(
                (s) =>
                    s.id === serialId &&
                    s.serial_number === trimmed &&
                    s.status === 'active',
            )
        ) {
            return true;
        }

        return false;
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[88vh] overflow-y-auto sm:max-w-4xl">
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
                            <span
                                className={
                                    serials.length === accepted && accepted > 0
                                        ? 'text-[12px] font-medium text-ok'
                                        : serials.length > accepted
                                          ? 'text-[12px] font-semibold text-bad'
                                          : 'text-[12px] text-muted-foreground'
                                }
                            >
                                {serials.length} de {accepted} unidad
                                {accepted !== 1 ? 'es' : ''} aceptada
                                {accepted !== 1 ? 's' : ''}
                            </span>
                        </div>

                        {fieldError('serials') && (
                            <p className="text-sm text-bad">
                                {fieldError('serials')}
                            </p>
                        )}

                        {/* Opción para indicar ingreso manual o autogenerado */}
                        <div className="flex flex-col gap-3 rounded-xl border border-border/80 bg-muted/20 p-3.5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <label
                                    htmlFor="auto-generate-serials-toggle"
                                    className="flex cursor-pointer items-center gap-2.5 text-[13px] font-medium text-foreground select-none"
                                >
                                    <Checkbox
                                        id="auto-generate-serials-toggle"
                                        checked={isAuto}
                                        onCheckedChange={(checked) =>
                                            setIsAuto(Boolean(checked))
                                        }
                                    />
                                    <span>
                                        Generar series automáticamente
                                        (autogeneradas)
                                    </span>
                                </label>

                                {isAuto && (
                                    <span className="text-[12px] text-muted-foreground">
                                        {remaining > 0 ? (
                                            <>
                                                Faltan{' '}
                                                <b className="font-semibold text-foreground">
                                                    {remaining}
                                                </b>{' '}
                                                por asignar
                                            </>
                                        ) : (
                                            <span className="font-medium text-ok">
                                                Todas las unidades cubiertas
                                            </span>
                                        )}
                                    </span>
                                )}
                            </div>

                            {isAuto ? (
                                <div className="flex flex-col gap-3 pt-1">
                                    {/* Historial de la última serie registrada para este artículo */}
                                    <div className="flex flex-wrap items-center gap-2 rounded-lg border border-border/60 bg-background/70 px-3 py-2 text-[12px] text-muted-foreground">
                                        {loadingSequence ? (
                                            <span className="flex items-center gap-1.5 text-muted-foreground">
                                                <Loader2 className="size-3.5 animate-spin text-primary" />
                                                Buscando última serie registrada del artículo...
                                            </span>
                                        ) : (
                                            <>
                                                <span>
                                                    Última serie del artículo:
                                                </span>
                                                {backendSequence?.last_serial_number ? (
                                                    <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-[11px] font-bold text-foreground">
                                                        {
                                                            backendSequence.last_serial_number
                                                        }
                                                    </code>
                                                ) : (
                                                    <span className="font-medium text-foreground">
                                                        Ninguna previa (inicia
                                                        secuencia en 1)
                                                    </span>
                                                )}
                                                <span className="text-muted-foreground/50">
                                                    ·
                                                </span>
                                                <span>Siguiente libre:</span>
                                                <code className="rounded bg-primary/10 px-1.5 py-0.5 font-mono text-[11px] font-bold text-primary">
                                                    {previewExample.split(
                                                        ' … ',
                                                    )[0]}
                                                </code>
                                            </>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4">
                                        <div className="flex flex-col gap-1.5">
                                            <Label className="text-[12px] font-medium text-muted-foreground">
                                                Prefijo
                                            </Label>
                                            <Input
                                                value={autoPrefix}
                                                onChange={(e) => {
                                                    setPrefixManuallyEdited(
                                                        true,
                                                    );
                                                    setAutoPrefix(
                                                        e.target.value,
                                                    );
                                                }}
                                                placeholder="SN-"
                                                maxLength={30}
                                                className="h-[38px] rounded-[10px]"
                                            />
                                        </div>

                                        <div className="flex flex-col gap-1.5">
                                            <Label className="text-[12px] font-medium text-muted-foreground">
                                                Número inicial
                                            </Label>
                                            <NumberInput
                                                value={autoStart}
                                                onValueChange={(val) => {
                                                    setAutoStartDirty(true);
                                                    setAutoStart(
                                                        Math.max(
                                                            1,
                                                            Math.floor(val),
                                                        ),
                                                    );
                                                }}
                                                min={1}
                                                decimals={0}
                                                className="h-[38px] rounded-[10px]"
                                            />
                                        </div>

                                        <div className="flex flex-col gap-1.5">
                                            <Label className="text-[12px] font-medium text-muted-foreground">
                                                Cantidad a generar
                                            </Label>
                                            <NumberInput
                                                value={autoCount}
                                                onValueChange={(val) => {
                                                    setAutoCountDirty(true);
                                                    setAutoCount(
                                                        Math.max(
                                                            1,
                                                            Math.floor(val),
                                                        ),
                                                    );
                                                }}
                                                min={1}
                                                decimals={0}
                                                className="h-[38px] rounded-[10px]"
                                            />
                                        </div>

                                        {lotOptions.length > 1 ? (
                                            <div className="flex flex-col gap-1.5">
                                                <Label className="text-[12px] font-medium text-muted-foreground">
                                                    Asignar al lote
                                                </Label>
                                                <Select2
                                                    options={lotOptions}
                                                    value={
                                                        lotOptions.find(
                                                            (opt) =>
                                                                opt.value ===
                                                                autoLot,
                                                        ) ?? null
                                                    }
                                                    onChange={(opt) =>
                                                        setAutoLot(
                                                            opt
                                                                ? opt.value
                                                                : NO_LOT,
                                                        )
                                                    }
                                                    size="sm"
                                                    placeholder="Sin lote"
                                                />
                                            </div>
                                        ) : (
                                            <div className="hidden md:block" />
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-border/50 pt-2">
                                        <p className="text-[12px] text-muted-foreground">
                                            Ejemplo:{' '}
                                            <code className="rounded bg-muted px-1.5 py-0.5 font-mono text-[11px] font-semibold text-foreground">
                                                {previewExample}
                                            </code>
                                        </p>

                                        <Button
                                            type="button"
                                            className="h-9 gap-1.5 rounded-[10px] font-semibold"
                                            onClick={handleGenerateSerials}
                                            disabled={autoCount <= 0}
                                        >
                                            <Sparkles className="size-3.5" />
                                            Generar {autoCount}{' '}
                                            {autoCount === 1
                                                ? 'serie'
                                                : 'series'}
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-wrap items-end gap-2">
                                    <div className="flex min-w-[240px] flex-1 flex-col gap-1.5">
                                        <Label className="text-[12px] font-medium text-muted-foreground">
                                            Agregar series manualmente
                                        </Label>
                                        <Input
                                            value={draft}
                                            onChange={(e) =>
                                                setDraft(e.target.value)
                                            }
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
                                    {remaining > 0 && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="h-[42px] gap-1.5 rounded-[10px] bg-card font-semibold text-primary hover:text-primary"
                                            onClick={() => setIsAuto(true)}
                                            title="Cambiar a modo autogenerado"
                                        >
                                            <Sparkles className="size-3.5" />
                                            Autogenerar restantes ({remaining})
                                        </Button>
                                    )}
                                </div>
                            )}
                        </div>

                        {serials.length > 0 && (
                            <div className="flex items-center justify-between pt-1">
                                <span className="text-[12px] font-semibold text-muted-foreground">
                                    Series registradas ({serials.length})
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 gap-1 px-2 text-[12px] text-muted-foreground hover:bg-bad/10 hover:text-bad"
                                    onClick={() => removeAllLineSerials(index)}
                                    title="Quitar todas las series activas de esta línea"
                                >
                                    <Trash2 className="size-3.5" />
                                    Quitar todas
                                </Button>
                            </div>
                        )}

                        {line.serials.map((serial, serialIndex) => {
                            if (serial.status !== 'active') return null;
                            const isDup = isDuplicateSerial(
                                serial.serial_number,
                                serial.id,
                            );

                            return (
                                <div
                                    key={serial.id}
                                    className="grid grid-cols-1 items-end gap-3 sm:grid-cols-[2fr_1.6fr_auto]"
                                >
                                    <div className="flex flex-col gap-1">
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
                                            className={`h-[42px] rounded-[10px] ${
                                                isDup
                                                    ? 'border-bad text-bad focus-visible:ring-bad'
                                                    : ''
                                            }`}
                                        />
                                        {isDup && (
                                            <span className="text-[11px] font-medium text-bad">
                                                Esta serie ya existe para este
                                                artículo
                                            </span>
                                        )}
                                    </div>

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
                            );
                        })}
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
