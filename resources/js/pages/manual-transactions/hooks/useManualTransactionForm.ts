import { useForm, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { generateUUID } from '@/lib/utils';
import manualTransactions from '@/routes/manual-transactions';
import type { ManualTransactionLineDraft } from '../types/ManualTransaction';

interface ManualTransactionFormData {
    id: string;
    date: string;
    payment_method: string;
    currency: string;
    reference: string;
    description: string;
    notes: string;
    lines: ManualTransactionLineDraft[];
    [key: string]: string | ManualTransactionLineDraft[];
}

interface PageProps {
    currentCompany?: { id: string; name: string } | null;
    [key: string]: unknown;
}

function emptyLine(): ManualTransactionLineDraft {
    return { category: '', amount: 0, description: '' };
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

/**
 * Lógica del formulario de transacción manual (cabecera + líneas). El tipo
 * (ingreso/egreso) de cada línea lo deriva el backend desde la categoría.
 */
export function useManualTransactionForm() {
    const { currentCompany } = usePage<PageProps>().props;
    const companyId = currentCompany!.id;

    const form = useForm<ManualTransactionFormData>({
        id: generateUUID(),
        date: today(),
        payment_method: 'cash',
        currency: 'USD',
        reference: '',
        description: '',
        notes: '',
        lines: [emptyLine()],
    });

    const { data, setData, processing, errors } = form;

    const total = useMemo(
        () => data.lines.reduce((sum, line) => sum + (Number(line.amount) || 0), 0),
        [data.lines],
    );

    const addLine = () => {
        setData('lines', [...data.lines, emptyLine()]);
    };

    const removeLine = (index: number) => {
        if (data.lines.length <= 1) {
            return;
        }
        setData(
            'lines',
            data.lines.filter((_, i) => i !== index),
        );
    };

    const updateLine = (
        index: number,
        patch: Partial<ManualTransactionLineDraft>,
    ) => {
        setData(
            'lines',
            data.lines.map((line, i) =>
                i === index ? { ...line, ...patch } : line,
            ),
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(manualTransactions.store(companyId).url, {
            preserveScroll: true,
        });
    };

    return {
        data,
        setData,
        processing,
        errors,
        total,
        addLine,
        removeLine,
        updateLine,
        handleSubmit,
    };
}

export type ManualTransactionFormState = ReturnType<
    typeof useManualTransactionForm
>;
