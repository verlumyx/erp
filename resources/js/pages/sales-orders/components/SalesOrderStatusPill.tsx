import { StatusPill, type StatusKind } from '@/components/status-pill';
import { STATUS_LABELS, type SalesOrderStatus } from '../types/SalesOrder';

/**
 * Los cinco estados de un documento se mapean a las pastillas existentes:
 * el ciclo del pedido no necesita una paleta propia.
 */
const PILL_KIND: Record<SalesOrderStatus, StatusKind> = {
    draft: 'pendiente',
    confirmed: 'libre',
    partial: 'porvencer',
    completed: 'pagado',
    cancelled: 'inactivo',
};

export function SalesOrderStatusPill({ status }: { status: SalesOrderStatus }) {
    return (
        <StatusPill kind={PILL_KIND[status]}>
            {STATUS_LABELS[status]}
        </StatusPill>
    );
}
