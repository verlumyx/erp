import { useEffect, useMemo, useState } from 'react';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    FileText,
    Package,
    Search,
    Truck,
} from 'lucide-react';
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
import type { LoadPlanDispatchItem } from '../types/LoadPlan';

interface ConfirmDeliveryModalProps {
    isOpen: boolean;
    onClose: () => void;
    planCode: string;
    dispatches: LoadPlanDispatchItem[];
    onConfirm: (deliveredDispatchIds: string[]) => void;
    processing?: boolean;
}

export function ConfirmDeliveryModal({
    isOpen,
    onClose,
    planCode,
    dispatches,
    onConfirm,
    processing = false,
}: ConfirmDeliveryModalProps) {
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [searchQuery, setSearchQuery] = useState('');

    // Por defecto, al abrir la modal, todos los despachos vienen marcados como entregados
    useEffect(() => {
        if (isOpen) {
            setSelectedIds(new Set(dispatches.map((d) => d.dispatch_id)));
            setSearchQuery('');
        }
    }, [isOpen, dispatches]);

    const filteredDispatches = useMemo(() => {
        if (!searchQuery.trim()) return dispatches;
        const q = searchQuery.toLowerCase();
        return dispatches.filter((d) => {
            return (
                (d.dispatch_code && d.dispatch_code.toLowerCase().includes(q)) ||
                (d.recipient_name && d.recipient_name.toLowerCase().includes(q)) ||
                (d.order_code && d.order_code.toLowerCase().includes(q)) ||
                (d.invoice_code && d.invoice_code.toLowerCase().includes(q)) ||
                (d.address && d.address.toLowerCase().includes(q))
            );
        });
    }, [dispatches, searchQuery]);

    const toggleSelect = (id: string) => {
        const next = new Set(selectedIds);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        setSelectedIds(next);
    };

    const toggleSelectAll = () => {
        if (selectedIds.size === filteredDispatches.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(filteredDispatches.map((d) => d.dispatch_id)));
        }
    };

    const handleConfirm = () => {
        onConfirm(Array.from(selectedIds));
    };

    const allDelivered = selectedIds.size === dispatches.length;
    const pendingReturnCount = dispatches.length - selectedIds.size;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && !processing && onClose()}>
            <DialogContent className="w-[96vw] sm:max-w-4xl md:max-w-5xl lg:max-w-5xl max-h-[92vh] sm:max-h-[88vh] flex flex-col overflow-hidden p-0">
                <DialogHeader className="p-6 pb-4 border-b bg-muted/10">
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold">
                        <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-500" />
                        <span>Confirmar Entrega del Plan de Carga {planCode}</span>
                    </DialogTitle>
                    <DialogDescription className="text-xs text-muted-foreground mt-1">
                        ¿Todos los despachos fueron entregados correctamente o algún pedido fue devuelto / rechazado?
                        Marque los despachos entregados con éxito. Los que queden desmarcados permanecerán como{' '}
                        <strong className="text-foreground">"Despachado"</strong> para procesar su correspondiente devolución en bodega.
                    </DialogDescription>

                    {/* Barra de búsqueda y seleccionar todos */}
                    <div className="mt-3 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Filtrar por código de despacho, cliente, factura o pedido..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-9 h-9 text-xs"
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={toggleSelectAll}
                                disabled={filteredDispatches.length === 0}
                                className="h-9 text-xs"
                            >
                                <Check className="size-3.5 mr-1" />
                                {selectedIds.size === filteredDispatches.length &&
                                filteredDispatches.length > 0
                                    ? 'Deseleccionar todos'
                                    : 'Marcar todos como entregados'}
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                {/* Tabla de despachos */}
                <div className="flex-1 overflow-y-auto px-6 py-4 min-h-[300px]">
                    {dispatches.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-center">
                            <Package className="size-10 text-muted-foreground/50 mb-2" />
                            <p className="text-sm font-semibold">
                                No hay despachos en este plan de carga
                            </p>
                        </div>
                    ) : (
                        <div className="border rounded-[10px] overflow-hidden">
                            <table className="w-full text-xs text-left">
                                <thead className="bg-muted/50 border-b font-semibold text-muted-foreground uppercase text-[11px]">
                                    <tr>
                                        <th className="p-3 w-12 text-center">Entrega</th>
                                        <th className="p-3 w-36">Despacho</th>
                                        <th className="p-3">Cliente / Destinatario</th>
                                        <th className="p-3 w-44">Factura / Pedido</th>
                                        <th className="p-3 text-right w-28">Peso (kg)</th>
                                        <th className="p-3 text-center w-44">Estado Resultante</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {filteredDispatches.map((row, idx) => {
                                        const isChecked = selectedIds.has(row.dispatch_id);
                                        return (
                                            <tr
                                                key={row.dispatch_id}
                                                onClick={() => toggleSelect(row.dispatch_id)}
                                                className={`cursor-pointer transition-colors hover:bg-muted/30 ${
                                                    isChecked
                                                        ? 'bg-emerald-50/50 dark:bg-emerald-950/20'
                                                        : 'bg-amber-50/50 dark:bg-amber-950/20'
                                                }`}
                                            >
                                                <td
                                                    className="p-3 text-center"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    <Checkbox
                                                        checked={isChecked}
                                                        onCheckedChange={() => toggleSelect(row.dispatch_id)}
                                                        aria-label={`Marcar ${row.dispatch_code} como entregado`}
                                                    />
                                                </td>
                                                <td className="p-3 font-semibold text-primary">
                                                    <div>
                                                        {row.dispatch_code || `Despacho #${idx + 1}`}
                                                    </div>
                                                    {row.dispatch_date && (
                                                        <div className="text-[11px] text-muted-foreground font-normal">
                                                            {row.dispatch_date}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-3">
                                                    <div className="font-semibold text-foreground">
                                                        {row.recipient_name || '—'}
                                                        {row.recipient_code && (
                                                            <span className="ml-1.5 text-[11px] font-normal text-muted-foreground">
                                                                ({row.recipient_code})
                                                            </span>
                                                        )}
                                                    </div>
                                                    {row.address && (
                                                        <div
                                                            className="text-[11px] text-muted-foreground line-clamp-2 mt-0.5"
                                                            title={row.address}
                                                        >
                                                            {row.address}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-3">
                                                    {row.invoice_code ? (
                                                        <div className="flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                                                            <FileText className="size-3.5 shrink-0" />
                                                            <span>{row.invoice_code}</span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
                                                    )}
                                                    {row.order_code && (
                                                        <div className="text-[11px] text-muted-foreground">
                                                            Ped: {row.order_code}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="p-3 text-right tabular-nums font-medium">
                                                    {Number(row.total_weight || 0).toLocaleString('es-VE', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    })}
                                                </td>
                                                <td className="p-3 text-center">
                                                    {isChecked ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 px-2.5 py-0.5 text-[11px] font-bold">
                                                            <Check className="size-3" />
                                                            Entregado
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 px-2.5 py-0.5 text-[11px] font-bold">
                                                            <AlertCircle className="size-3" />
                                                            Queda Despachado
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Banner de resumen y estado resultante */}
                <div className="px-6 py-3 border-t bg-muted/30 text-xs">
                    {allDelivered ? (
                        <div className="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-medium">
                            <CheckCircle2 className="size-4 shrink-0" />
                            <span>
                                Todos los despachos ({selectedIds.size}) se marcarán como <strong>"Entregados"</strong> y el Plan de Carga quedará completado con éxito.
                            </span>
                        </div>
                    ) : (
                        <div className="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-medium">
                            <AlertCircle className="size-4 shrink-0" />
                            <span>
                                <strong>{selectedIds.size}</strong> despacho(s) se marcarán como <strong>"Entregados"</strong>.{' '}
                                <strong>{pendingReturnCount}</strong> despacho(s) permanecerán como <strong>"Despachados"</strong> para gestionar su devolución en bodega o reprogramación.
                            </span>
                        </div>
                    )}
                </div>

                {/* Pie de modal */}
                <DialogFooter className="p-4 px-6 border-t bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-4 text-xs">
                        <span className="font-medium">
                            Total entregas en viaje: <strong>{dispatches.length}</strong>
                        </span>
                        <span className="text-muted-foreground">|</span>
                        <span>
                            Entregados:{' '}
                            <strong className="text-emerald-600 dark:text-emerald-400 font-bold">
                                {selectedIds.size}
                            </strong>
                        </span>
                        <span className="text-muted-foreground">|</span>
                        <span>
                            Devueltos / Pendientes:{' '}
                            <strong className="text-amber-600 dark:text-amber-400 font-bold">
                                {pendingReturnCount}
                            </strong>
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={processing}
                            onClick={onClose}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            disabled={processing}
                            onClick={handleConfirm}
                            className="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                        >
                            <CheckCircle2 className="size-4" />
                            Confirmar viaje ({selectedIds.size}/{dispatches.length})
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
