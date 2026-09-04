import { Boxes, Power } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { useImportFormContext } from '../contexts/ImportFormContext';
import { formatAmount, type ImportLine } from '../types/Import';

/**
 * 6.4 Los ítems. No se capturan: cada línea viva de cada recepción que mueva
 * existencia entra aquí con su cantidad aceptada y su costo de entrada.
 *
 * Lo único editable es el estado: sacar una línea del reparto cuando ese ítem
 * no viajó en ese embarque. El resto lo calcula el backend, y un valor enviado
 * desde el cliente se ignora.
 */
export function ImportLinesSection() {
    const { data, initialData, toggleLine } = useImportFormContext();

    const lines = (initialData?.lines ?? [])
        .slice()
        .sort((a, b) => a.line_number - b.line_number);

    const currency = data.currency;

    /** El estado que la pantalla tiene ahora, que puede no ser el guardado. */
    const statusOf = (line: ImportLine) =>
        data.lines.find((row) => row.entry_line_id === line.entry_line_id)
            ?.status ?? line.status;

    if (lines.length === 0) {
        return (
            <div className="flex flex-col items-center gap-2 p-8 text-center">
                <Boxes className="size-6 text-muted-foreground" />
                <p className="max-w-lg text-[13.5px] text-muted-foreground">
                    Los ítems se derivan de las recepciones al guardar. Un
                    artículo de servicio o no inventariado no entra: no lleva
                    existencia, así que no hay costo que reexpresar.
                </p>
            </div>
        );
    }

    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Artículo</TableHead>
                        <TableHead className="text-right">Aceptado</TableHead>
                        <TableHead className="text-right">En bodega</TableHead>
                        <TableHead className="text-right">Costo</TableHead>
                        <TableHead className="text-right">Gasto</TableHead>
                        <TableHead className="text-right">
                            Costo nuevo
                        </TableHead>
                        <TableHead className="text-right">
                            Capitalizado
                        </TableHead>
                        <TableHead className="text-right">Varianza</TableHead>
                        <TableHead className="text-right">Reparto</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {lines.map((line) => {
                        const active = statusOf(line) === 'active';

                        return (
                            <TableRow
                                key={line.id}
                                className={cn(!active && 'opacity-50')}
                            >
                                <TableCell>
                                    <div className="font-semibold">
                                        {line.item_code
                                            ? `${line.item_code} — ${line.item_name}`
                                            : line.item_name}
                                    </div>
                                    <div className="text-[12.5px] text-muted-foreground">
                                        {[
                                            line.entry_code,
                                            line.location_name,
                                            (line.lots ?? []).filter(
                                                (lot) =>
                                                    lot.status === 'active',
                                            ).length > 0
                                                ? `${(line.lots ?? []).filter((lot) => lot.status === 'active').length} lotes`
                                                : null,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </div>
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {line.base_quantity}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {line.remaining_quantity}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatAmount(
                                        Number(line.unit_cost),
                                        currency,
                                    )}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatAmount(
                                        Number(line.allocated_amount),
                                        currency,
                                    )}
                                </TableCell>
                                <TableCell className="text-right font-bold tabular-nums">
                                    {formatAmount(
                                        Number(line.new_unit_cost),
                                        currency,
                                    )}
                                </TableCell>
                                <TableCell className="text-right text-ok tabular-nums">
                                    {formatAmount(
                                        Number(line.capitalized_amount),
                                        currency,
                                    )}
                                </TableCell>
                                <TableCell className="text-right text-warn tabular-nums">
                                    {formatAmount(
                                        Number(line.variance_amount),
                                        currency,
                                    )}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="rounded-[10px] bg-card"
                                        onClick={() =>
                                            toggleLine(line.entry_line_id)
                                        }
                                    >
                                        <Power className="size-3.5" />
                                        {active ? 'Sacar' : 'Devolver'}
                                    </Button>
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}
