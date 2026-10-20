import { useState } from 'react';
import { ArrowLeft, Clock, DollarSign, LogOut, TrendingDown, TrendingUp, User, Monitor, HelpCircle } from 'lucide-react';
import { usePosSession } from '../context/PosSessionContext';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface PosHeaderProps {
    onOpenCashMovement: () => void;
    onOpenReadingX: () => void;
    onOpenCloseSession: () => void;
    onOpenShortcuts: () => void;
}

export function PosHeader({
    onOpenCashMovement,
    onOpenReadingX,
    onOpenCloseSession,
    onOpenShortcuts,
}: PosHeaderProps) {
    const { session, rate, companyId } = usePosSession();

    return (
        <header className="flex h-16 w-full items-center justify-between border-b bg-card px-4 shadow-xs">
            <div className="flex items-center gap-4">
                <Link
                    href={`/${companyId}/pos-sessions`}
                    className="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors"
                    title="Volver a sesiones"
                >
                    <ArrowLeft className="h-4 w-4" />
                    <span>Salir</span>
                </Link>

                <div className="h-6 w-px bg-border" />

                <div className="flex items-center gap-2">
                    <Monitor className="h-4 w-4 text-primary" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Caja:</span>
                    <span className="text-sm font-bold text-foreground">
                        {session.pos_register_name || 'Mostrador'}
                    </span>
                </div>

                <div className="h-6 w-px bg-border hidden sm:block" />

                <div className="hidden sm:flex items-center gap-2">
                    <User className="h-4 w-4 text-muted-foreground" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Cajero:</span>
                    <span className="text-sm font-medium text-foreground">{session.user_name}</span>
                </div>

                <div className="h-6 w-px bg-border hidden md:block" />

                <div className="hidden md:flex items-center gap-2">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Turno:</span>
                    <span className="rounded bg-primary/10 px-2 py-0.5 font-mono text-xs font-bold text-primary">
                        {session.code}
                    </span>
                </div>
            </div>

            <div className="flex items-center gap-3">
                <div className="flex items-center gap-1.5 rounded-lg border bg-background px-3 py-1 shadow-2xs">
                    <DollarSign className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    <span className="text-xs text-muted-foreground">Tasa Oficial:</span>
                    <span className="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">
                        {rate.toFixed(2)} VES/USD
                    </span>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    onClick={onOpenShortcuts}
                    className="hidden sm:flex items-center gap-1.5 h-8 text-xs font-medium text-foreground hover:text-primary hover:border-primary/40 transition-colors"
                    title="Ver atajos de teclado y ayuda"
                >
                    <HelpCircle className="h-3.5 w-3.5 text-primary" />
                    <span>Ayuda</span>
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    onClick={onOpenCashMovement}
                    className="hidden lg:flex items-center gap-1.5 h-8 text-xs font-medium"
                >
                    <TrendingDown className="h-3.5 w-3.5 text-amber-500" />
                    <span>Movimiento [F10]</span>
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    onClick={onOpenReadingX}
                    className="hidden lg:flex items-center gap-1.5 h-8 text-xs font-medium"
                >
                    <TrendingUp className="h-3.5 w-3.5 text-blue-500" />
                    <span>Lectura X [F11]</span>
                </Button>

                <Button
                    variant="destructive"
                    size="sm"
                    onClick={onOpenCloseSession}
                    className="flex items-center gap-1.5 h-8 text-xs font-medium"
                >
                    <LogOut className="h-3.5 w-3.5" />
                    <span>Cerrar Turno</span>
                </Button>
            </div>
        </header>
    );
}
