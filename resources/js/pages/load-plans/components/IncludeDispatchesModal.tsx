import { useCallback, useEffect, useMemo, useState } from 'react';
import {
    Check,
    FileText,
    Loader2,
    Package,
    Plus,
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

interface PendingDispatchRow {
    id: string;
    code: string;
    dispatch_date: string | null;
    route_id: string | null;
    route_name: string | null;
    recipient_name: string;
    recipient_code: string | null;
    address: string;
    order_code: string | null;
    invoice_code: string | null;
    total_quantity: number;
    total_weight: number;
    total_volume: number;
    status: string;
}

interface IncludeDispatchesModalProps {
    isOpen: boolean;
    onClose: () => void;
    companyId: string;
    routeId?: string;
    currentPlanId?: string;
    alreadyAddedIds: string[];
    onInclude: (items: LoadPlanDispatchItem[]) => void;
}

export function IncludeDispatchesModal({
    isOpen,
    onClose,
    companyId,
    routeId,
    currentPlanId,
    alreadyAddedIds,
    onInclude,
}: IncludeDispatchesModalProps) {
    const [loading, setLoading] = useState(false);
    const [dispatches, setDispatches] = useState<PendingDispatchRow[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());

    const fetchPendingDispatches = useCallback(async () => {
        if (!isOpen) return;

        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (routeId) params.append('route_id', routeId);
            if (currentPlanId) params.append('exclude_plan_id', currentPlanId);

            const url = `/${companyId}/load-plans/pending-dispatches?${params.toString()}`;
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const json = await response.json();
                setDispatches(json.data || []);
            }
        } catch (err) {
            console.error('Error fetching pending dispatches:', err);
        } finally {
            setLoading(false);
        }
    }, [isOpen, companyId, routeId, currentPlanId]);

    useEffect(() => {
        if (isOpen) {
            setSelectedIds(new Set());
            setSearchQuery('');
            fetchPendingDispatches();
        }
    }, [isOpen, fetchPendingDispatches]);

    // Exclude already added dispatches
    const availableDispatches = useMemo(() => {
        const addedSet = new Set(alreadyAddedIds);
        return dispatches.filter((d) => !addedSet.has(d.id));
    }, [dispatches, alreadyAddedIds]);

    // Apply search query
    const filteredDispatches = useMemo(() => {
        if (!searchQuery.trim()) return availableDispatches;
        const q = searchQuery.toLowerCase();
        return availableDispatches.filter((d) => {
            return (
                d.code.toLowerCase().includes(q) ||
                d.recipient_name.toLowerCase().includes(q) ||
                (d.order_code && d.order_code.toLowerCase().includes(q)) ||
                (d.invoice_code && d.invoice_code.toLowerCase().includes(q)) ||
                (d.address && d.address.toLowerCase().includes(q))
            );
        });
    }, [availableDispatches, searchQuery]);

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
            setSelectedIds(new Set(filteredDispatches.map((d) => d.id)));
        }
    };

    // Calculate totals of selected items
    const selectedStats = useMemo(() => {
        let weight = 0;
        let volume = 0;
        let quantity = 0;

        for (const d of availableDispatches) {
            if (selectedIds.has(d.id)) {
                weight += Number(d.total_weight) || 0;
                volume += Number(d.total_volume) || 0;
                quantity += Number(d.total_quantity) || 0;
            }
        }

        return {
            count: selectedIds.size,
            weight: Math.round(weight * 100) / 100,
            volume: Math.round(volume * 1000) / 1000,
            quantity,
        };
    }, [availableDispatches, selectedIds]);

    const handleInclude = () => {
        const selectedRows = availableDispatches.filter((d) =>
            selectedIds.has(d.id),
        );
        const mapped: LoadPlanDispatchItem[] = selectedRows.map((d) => ({
            dispatch_id: d.id,
            sequence: 0,
            dispatch_code: d.code,
            dispatch_date: d.dispatch_date ?? undefined,
            recipient_name: d.recipient_name,
            recipient_code: d.recipient_code,
            address: d.address,
            order_code: d.order_code,
            invoice_code: d.invoice_code,
            total_quantity: d.total_quantity,
            total_weight: d.total_weight,
            total_volume: d.total_volume,
            dispatch_status: d.status,
        }));

        onInclude(mapped);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="w-[96vw] sm:max-w-4xl md:max-w-5xl lg:max-w-6xl xl:max-w-6xl max-h-[92vh] sm:max-h-[88vh] flex flex-col overflow-hidden p-0">
                <DialogHeader className="p-6 pb-4 border-b">
                    <DialogTitle className="flex items-center gap-2 text-lg font-bold">
                        <Truck className="size-5 text-primary" />
                        <span>Incluir Despachos al Plan de Carga</span>
                        {!loading && availableDispatches.length > 0 && (
                            <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary font-normal">
                                {filteredDispatches.length}{' '}
                                {filteredDispatches.length === 1
                                    ? 'disponible'
                                    : 'disponibles'}
                            </span>
                        )}
                    </DialogTitle>
                    <DialogDescription className="text-xs text-muted-foreground">
                        Seleccione los despachos pendientes asignados a esta ruta
                        comercial para incluirlos en el camión.
                    </DialogDescription>

                    {/* Search & Select All Bar */}
                    <div className="mt-3 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
                            <Input
                                placeholder="Buscar por código, cliente, factura o pedido..."
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
                                    : 'Seleccionar todos'}
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                {/* Body Table */}
                <div className="flex-1 overflow-y-auto px-6 py-4 min-h-[320px] sm:min-h-[380px]">
                    {loading ? (
                        <div className="flex flex-col items-center justify-center py-16 gap-3">
                            <Loader2 className="size-8 animate-spin text-primary" />
                            <p className="text-xs text-muted-foreground">
                                Buscando despachos pendientes...
                            </p>
                        </div>
                    ) : filteredDispatches.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-center">
                            <Package className="size-10 text-muted-foreground/50 mb-2" />
                            <p className="text-sm font-semibold">
                                No hay despachos disponibles
                            </p>
                            <p className="text-xs text-muted-foreground max-w-sm mt-1">
                                {!routeId
                                    ? 'Seleccione una ruta en la cabecera para ver los despachos asociados.'
                                    : 'No se encontraron despachos pendientes de entrega para esta ruta.'}
                            </p>
                        </div>
                    ) : (
                        <div className="border rounded-[10px] overflow-hidden">
                            <table className="w-full text-xs text-left">
                                <thead className="bg-muted/50 border-b font-semibold text-muted-foreground uppercase text-[11px]">
                                    <tr>
                                        <th className="p-3 w-12 text-center"></th>
                                        <th className="p-3 w-36">Despacho</th>
                                        <th className="p-3">Cliente / Destino</th>
                                        <th className="p-3 w-44">Factura / Pedido</th>
                                        <th className="p-3 text-right w-32">Peso (kg)</th>
                                        <th className="p-3 text-right w-32">Volumen (m³)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {filteredDispatches.map((row) => {
                                        const isChecked = selectedIds.has(row.id);
                                        return (
                                            <tr
                                                key={row.id}
                                                onClick={() => toggleSelect(row.id)}
                                                className={`cursor-pointer transition-colors hover:bg-muted/30 ${
                                                    isChecked ? 'bg-primary/5' : ''
                                                }`}
                                            >
                                                <td
                                                    className="p-3 text-center"
                                                    onClick={(e) => e.stopPropagation()}
                                                >
                                                    <Checkbox
                                                        checked={isChecked}
                                                        onCheckedChange={() => toggleSelect(row.id)}
                                                        aria-label={`Seleccionar ${row.code}`}
                                                    />
                                                </td>
                                                <td className="p-3 font-semibold text-primary">
                                                    <div>{row.code}</div>
                                                    <div className="text-[11px] text-muted-foreground font-normal">
                                                        {row.dispatch_date || 'Sin fecha'}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <div className="font-semibold text-foreground">
                                                        {row.recipient_name}
                                                        {row.recipient_code && (
                                                            <span className="ml-1.5 text-[11px] font-normal text-muted-foreground">
                                                                ({row.recipient_code})
                                                            </span>
                                                        )}
                                                    </div>
                                                    {row.address ? (
                                                        <div
                                                            className="text-[11px] text-muted-foreground line-clamp-2 mt-0.5"
                                                            title={row.address}
                                                        >
                                                            {row.address}
                                                        </div>
                                                    ) : (
                                                        <div className="text-[11px] text-muted-foreground/60 italic mt-0.5">
                                                            Sin dirección registrada
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
                                                <td className="p-3 text-right font-semibold tabular-nums">
                                                    {Number(row.total_weight || 0).toLocaleString('es-VE', {
                                                        minimumFractionDigits: 2,
                                                        maximumFractionDigits: 2,
                                                    })}
                                                </td>
                                                <td className="p-3 text-right tabular-nums text-muted-foreground">
                                                    {Number(row.total_volume || 0).toLocaleString('es-VE', {
                                                        minimumFractionDigits: 3,
                                                        maximumFractionDigits: 3,
                                                    })}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Footer with Selection Stats */}
                <DialogFooter className="p-4 px-6 border-t bg-muted/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-xs">
                        <span className="font-medium">
                            Seleccionados:{' '}
                            <strong className="text-primary font-bold">
                                {selectedStats.count}
                            </strong>
                        </span>
                        <span className="text-muted-foreground">|</span>
                        <span>
                            Peso sumado:{' '}
                            <strong className="font-bold">
                                {selectedStats.weight.toLocaleString('es-VE')} kg
                            </strong>
                        </span>
                        <span className="text-muted-foreground">|</span>
                        <span>
                            Volumen sumado:{' '}
                            <strong className="font-bold">
                                {selectedStats.volume.toLocaleString('es-VE')} m³
                            </strong>
                        </span>
                        {selectedStats.quantity > 0 && (
                            <>
                                <span className="text-muted-foreground">|</span>
                                <span>
                                    Unidades:{' '}
                                    <strong className="font-bold">
                                        {selectedStats.quantity.toLocaleString('es-VE')}
                                    </strong>
                                </span>
                            </>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={onClose}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            disabled={selectedStats.count === 0}
                            onClick={handleInclude}
                            className="gap-1.5"
                        >
                            <Plus className="size-4" />
                            Incluir {selectedStats.count > 0 ? `(${selectedStats.count})` : ''}
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
