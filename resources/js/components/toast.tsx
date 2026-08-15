import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, X, XCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

type ToastType = 'success' | 'error';

interface ToastItem {
    id: number;
    type: ToastType;
    message: string;
}

const AUTO_DISMISS_MS = 5000;

export function Toaster() {
    const { flash } = usePage<SharedData>().props;
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const idRef = useRef(0);
    const lastRef = useRef<{ message: string; time: number } | null>(null);

    const dismiss = (id: number) => {
        setToasts((current) => current.filter((toast) => toast.id !== id));
    };

    const push = (type: ToastType, message: string) => {
        // Guard against React StrictMode firing the effect twice for the same flash.
        const now = Date.now();
        if (lastRef.current && lastRef.current.message === message && now - lastRef.current.time < 500) {
            return;
        }
        lastRef.current = { message, time: now };

        const id = ++idRef.current;
        setToasts((current) => [...current, { id, type, message }]);
        window.setTimeout(() => dismiss(id), AUTO_DISMISS_MS);
    };

    useEffect(() => {
        if (flash?.error) {
            push('error', flash.error);
        }
        if (flash?.success) {
            push('success', flash.success);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash?.error, flash?.success]);

    if (toasts.length === 0) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed top-4 right-4 z-[100] flex w-full max-w-sm flex-col gap-2">
            {toasts.map((toast) => {
                const Icon = toast.type === 'success' ? CheckCircle2 : XCircle;

                return (
                    <div
                        key={toast.id}
                        role="alert"
                        className="pointer-events-auto flex items-start gap-3 rounded-lg border bg-background p-4 shadow-lg"
                    >
                        <Icon
                            className={cn(
                                'mt-0.5 h-5 w-5 shrink-0',
                                toast.type === 'success' ? 'text-emerald-500' : 'text-destructive',
                            )}
                        />
                        <p className="flex-1 text-sm text-foreground">{toast.message}</p>
                        <button
                            type="button"
                            onClick={() => dismiss(toast.id)}
                            className="shrink-0 text-muted-foreground transition-colors hover:text-foreground"
                            aria-label="Cerrar"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
