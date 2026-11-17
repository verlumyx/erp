import { useState } from 'react';
import {
    AlertTriangle,
    ArrowDown,
    ArrowUp,
    FileText,
    Package,
    Plus,
    Trash2,
    Truck,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useLoadPlanFormContext } from '../contexts/LoadPlanFormContext';
import { IncludeDispatchesModal } from './IncludeDispatchesModal';

export function LoadPlanDispatchesSection() {
    const {
        data,
        currentVehicle,
        totalWeight,
        totalVolume,
        totalDispatches,
        weightPercentage,
        volumePercentage,
        addDispatches,
        removeDispatch,
        moveDispatch,
        companyId,
    } = useLoadPlanFormContext();

    const [modalOpen, setModalOpen] = useState(false);

    const isWeightOverload =
        currentVehicle?.capacity_weight &&
        totalWeight > currentVehicle.capacity_weight;
    const isVolumeOverload =
        currentVehicle?.capacity_volume &&
        totalVolume > currentVehicle.capacity_volume;

    const alreadyAddedIds = data.dispatches.map((d) => d.dispatch_id);

    return (
        <div className="flex flex-col gap-6 p-5">
            {/* Live Truck Capacity Bars */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-[12px] bg-muted/40 border">
                {/* Truck Info Card */}
                <div className="flex items-center gap-3">
                    <div className="p-3 rounded-[10px] bg-primary/10 text-primary">
                        <Truck className="size-6" />
                    </div>
                    <div>
                        <div className="text-xs text-muted-foreground uppercase font-semibold">
                            Vehículo Asignado
                        </div>
                        {currentVehicle ? (
                            <div className="font-bold text-sm">
                                {currentVehicle.plate} — {currentVehicle.name}
                            </div>
                        ) : (
                            <div className="text-xs italic text-muted-foreground">
                                Ningún camión seleccionado
                            </div>
                        )}
                        <div className="text-[11px] text-muted-foreground">
                            {totalDispatches} despacho(s) en la carga
                        </div>
                    </div>
                </div>

                {/* Weight Capacity Progress */}
                <div className="flex flex-col justify-center gap-1.5">
                    <div className="flex justify-between items-center text-xs">
                        <span className="font-semibold flex items-center gap-1">
                            Capacidad Peso:
                            {isWeightOverload && (
                                <AlertTriangle className="size-3.5 text-bad animate-pulse" />
                            )}
                        </span>
                        <span className="tabular-nums font-bold">
                            {totalWeight.toLocaleString('es-VE', {
                                maximumFractionDigits: 1,
                            })}{' '}
                            /{' '}
                            {currentVehicle?.capacity_weight
                                ? Number(
                                      currentVehicle.capacity_weight,
                                  ).toLocaleString('es-VE')
                                : '—'}{' '}
                            kg ({weightPercentage}%)
                        </span>
                    </div>
                    <div className="h-2 w-full bg-muted rounded-full overflow-hidden">
                        <div
                            className={`h-full transition-all duration-300 rounded-full ${
                                isWeightOverload
                                    ? 'bg-bad'
                                    : weightPercentage > 85
                                      ? 'bg-warning'
                                      : 'bg-primary'
                            }`}
                            style={{
                                width: `${Math.min(weightPercentage, 100)}%`,
                            }}
                        />
                    </div>
                    {isWeightOverload && (
                        <p className="text-[11px] font-medium text-bad">
                            ¡Sobrecarga de peso detectada!
                        </p>
                    )}
                </div>

                {/* Volume Capacity Progress */}
                <div className="flex flex-col justify-center gap-1.5">
                    <div className="flex justify-between items-center text-xs">
                        <span className="font-semibold flex items-center gap-1">
                            Capacidad Volumen:
                            {isVolumeOverload && (
                                <AlertTriangle className="size-3.5 text-bad animate-pulse" />
                            )}
                        </span>
                        <span className="tabular-nums font-bold">
                            {totalVolume.toLocaleString('es-VE', {
                                maximumFractionDigits: 2,
                            })}{' '}
                            /{' '}
                            {currentVehicle?.capacity_volume
                                ? Number(
                                      currentVehicle.capacity_volume,
                                  ).toLocaleString('es-VE')
                                : '—'}{' '}
                            m³ ({volumePercentage}%)
                        </span>
                    </div>
                    <div className="h-2 w-full bg-muted rounded-full overflow-hidden">
                        <div
                            className={`h-full transition-all duration-300 rounded-full ${
                                isVolumeOverload
                                    ? 'bg-bad'
                                    : volumePercentage > 85
                                      ? 'bg-warning'
                                      : 'bg-emerald-500'
                            }`}
                            style={{
                                width: `${Math.min(volumePercentage, 100)}%`,
                            }}
                        />
                    </div>
                    {isVolumeOverload && (
                        <p className="text-[11px] font-medium text-bad">
                            ¡Volumen excedido respecto a la capacidad del camión!
                        </p>
                    )}
                </div>
            </div>

            {/* Header with "Incluir Despachos" Button */}
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-sm font-bold">
                        Despachos en el Plan de Carga
                    </h3>
                    <p className="text-xs text-muted-foreground">
                        Organice el orden de entrega y revise los totales a
                        transportar.
                    </p>
                </div>
                <Button
                    type="button"
                    onClick={() => setModalOpen(true)}
                    className="gap-1.5 h-9 text-xs"
                >
                    <Plus className="size-4" />
                    Incluir Despachos
                </Button>
            </div>

            {/* Dispatches List / Table */}
            {data.dispatches.length === 0 ? (
                <div className="flex flex-col items-center justify-center p-12 border-2 border-dashed rounded-[12px] text-center">
                    <Package className="size-10 text-muted-foreground/40 mb-3" />
                    <h4 className="text-sm font-semibold">
                        Aún no se han incluido despachos
                    </h4>
                    <p className="text-xs text-muted-foreground max-w-sm mt-1 mb-4">
                        Haga clic en &ldquo;Incluir Despachos&rdquo; para
                        seleccionar los despachos pendientes asignados a esta
                        ruta comercial.
                    </p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => setModalOpen(true)}
                        className="gap-1.5"
                    >
                        <Plus className="size-4" />
                        Incluir Despachos
                    </Button>
                </div>
            ) : (
                <div className="border rounded-[10px] overflow-hidden">
                    <table className="w-full text-xs text-left">
                        <thead className="bg-muted/50 border-b font-semibold text-muted-foreground uppercase text-[11px]">
                            <tr>
                                <th className="p-3 w-16 text-center">Orden</th>
                                <th className="p-3">Despacho</th>
                                <th className="p-3">Cliente / Destino</th>
                                <th className="p-3">Factura / Pedido</th>
                                <th className="p-3 text-right">Peso (kg)</th>
                                <th className="p-3 text-right">Volumen (m³)</th>
                                <th className="p-3 w-14 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {data.dispatches.map((row, index) => (
                                <tr
                                    key={row.dispatch_id}
                                    className="hover:bg-muted/20 transition-colors"
                                >
                                    <td className="p-3 text-center">
                                        <div className="flex items-center justify-center gap-1.5">
                                            <span className="grid size-6 place-items-center rounded-md bg-primary-soft text-xs font-bold text-primary">
                                                {index + 1}
                                            </span>
                                            <div className="flex flex-col">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        moveDispatch(index, -1)
                                                    }
                                                    disabled={index === 0}
                                                    className="text-muted-foreground hover:text-foreground disabled:opacity-20"
                                                    title="Subir orden"
                                                >
                                                    <ArrowUp className="size-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        moveDispatch(index, 1)
                                                    }
                                                    disabled={
                                                        index ===
                                                        data.dispatches.length -
                                                            1
                                                    }
                                                    className="text-muted-foreground hover:text-foreground disabled:opacity-20"
                                                    title="Bajar orden"
                                                >
                                                    <ArrowDown className="size-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-3 font-semibold text-primary">
                                        <div>
                                            {row.dispatch_code ||
                                                row.dispatch_id.slice(0, 8)}
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
                                        </div>
                                        {row.address && (
                                            <div className="text-[11px] text-muted-foreground truncate max-w-xs">
                                                {row.address}
                                            </div>
                                        )}
                                    </td>
                                    <td className="p-3">
                                        {row.invoice_code ? (
                                            <div className="flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400">
                                                <FileText className="size-3.5" />
                                                <span>{row.invoice_code}</span>
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                        {row.order_code && (
                                            <div className="text-[11px] text-muted-foreground">
                                                Ped: {row.order_code}
                                            </div>
                                        )}
                                    </td>
                                    <td className="p-3 text-right font-semibold tabular-nums">
                                        {Number(
                                            row.total_weight || 0,
                                        ).toLocaleString('es-VE', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })}
                                    </td>
                                    <td className="p-3 text-right tabular-nums text-muted-foreground">
                                        {Number(
                                            row.total_volume || 0,
                                        ).toLocaleString('es-VE', {
                                            minimumFractionDigits: 3,
                                            maximumFractionDigits: 3,
                                        })}
                                    </td>
                                    <td className="p-3 text-center">
                                        <button
                                            type="button"
                                            onClick={() => removeDispatch(index)}
                                            className="p-1 rounded text-muted-foreground hover:text-bad hover:bg-bad/10 transition-colors"
                                            title="Quitar despacho"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot className="bg-muted/40 border-t font-semibold">
                            <tr>
                                <td colSpan={4} className="p-3 text-right">
                                    Totales consolidables:
                                </td>
                                <td className="p-3 text-right font-bold tabular-nums">
                                    {totalWeight.toLocaleString('es-VE', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2,
                                    })}{' '}
                                    kg
                                </td>
                                <td className="p-3 text-right font-bold tabular-nums">
                                    {totalVolume.toLocaleString('es-VE', {
                                        minimumFractionDigits: 3,
                                        maximumFractionDigits: 3,
                                    })}{' '}
                                    m³
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            )}

            {/* Modal for Including Pending Dispatches */}
            <IncludeDispatchesModal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                companyId={companyId}
                routeId={data.route_id}
                currentPlanId={data.id}
                alreadyAddedIds={alreadyAddedIds}
                onInclude={addDispatches}
            />
        </div>
    );
}
