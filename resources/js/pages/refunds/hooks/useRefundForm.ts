import { useForm, usePage } from '@inertiajs/react';
import { generateUUID } from '@/lib/utils';
import refunds from '@/routes/refunds';
import type { Refund } from '../types/Refund';

interface UseRefundFormProps {
    mode: 'create' | 'edit';
    refund?: Refund;
}

interface RefundFormData {
    id: string;
    sale_id: string;
    amount: number;
    reason: string;
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

/**
 * Lógica del formulario de reembolso (crear/editar). En modo edición solo se
 * envía monto y razón; la venta asociada es inmutable.
 */
export function useRefundForm({ mode, refund }: UseRefundFormProps) {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const form = useForm<RefundFormData>({
        id: refund?.id ?? generateUUID(),
        sale_id: refund?.sale_id ?? '',
        amount: refund ? Number(refund.amount) : 0,
        reason: refund?.reason ?? '',
    });

    const { data, setData, post, put, processing, errors, transform } = form;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (mode === 'create') {
            post(refunds.store(companyId).url, { preserveScroll: true });

            return;
        }

        if (refund) {
            // En edición no se reenvía id ni sale_id.
            transform((current) => ({
                amount: current.amount,
                reason: current.reason,
            }));
            put(refunds.update({ company: companyId, id: refund.id }).url, {
                preserveScroll: true,
            });
        }
    };

    return { data, setData, processing, errors, handleSubmit, mode };
}

export type RefundFormState = ReturnType<typeof useRefundForm>;
