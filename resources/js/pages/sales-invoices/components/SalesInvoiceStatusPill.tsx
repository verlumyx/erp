import { StatusPill, type StatusKind } from '@/components/status-pill';
import {
    PAYMENT_STATUS_LABELS,
    STATUS_LABELS,
    type PaymentStatus,
    type SalesInvoiceStatus,
} from '../types/SalesInvoice';

/**
 * Los estados del documento se mapean a las pastillas existentes: el ciclo de
 * la factura no necesita una paleta propia.
 */
const PILL_KIND: Record<SalesInvoiceStatus, StatusKind> = {
    draft: 'pendiente',
    confirmed: 'libre',
    completed: 'pagado',
    cancelled: 'inactivo',
};

/** El estado de cobro es otra dimensión: una factura emitida puede estar vencida. */
const PAYMENT_PILL_KIND: Record<PaymentStatus, StatusKind> = {
    pending: 'pendiente',
    partial: 'moroso',
    paid: 'pagado',
    overdue: 'vencido',
};

export function SalesInvoiceStatusPill({
    status,
}: {
    status: SalesInvoiceStatus;
}) {
    return (
        <StatusPill kind={PILL_KIND[status]}>
            {STATUS_LABELS[status]}
        </StatusPill>
    );
}

export function SalesInvoicePaymentPill({ status }: { status: PaymentStatus }) {
    return (
        <StatusPill kind={PAYMENT_PILL_KIND[status]}>
            {PAYMENT_STATUS_LABELS[status]}
        </StatusPill>
    );
}
