import { Bell, CheckCheck, CreditCard, UserPlus, AlertTriangle, RefreshCw } from 'lucide-react';
import { useState, type ComponentType } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

type NotificationType = 'payment' | 'client' | 'renewal' | 'alert';

interface Notification {
    id: string;
    type: NotificationType;
    title: string;
    description: string;
    time: string;
    read: boolean;
}

const typeConfig: Record<
    NotificationType,
    { icon: ComponentType<{ className?: string }>; className: string }
> = {
    payment: { icon: CreditCard, className: 'bg-ok-soft text-ok' },
    client: { icon: UserPlus, className: 'bg-primary-soft text-primary' },
    renewal: { icon: RefreshCw, className: 'bg-info-soft text-info' },
    alert: { icon: AlertTriangle, className: 'bg-bad-soft text-bad' },
};

const sampleNotifications: Notification[] = [
    {
        id: '1',
        type: 'payment',
        title: 'Pago recibido',
        description: 'María González pagó $12.00 por su perfil de Netflix.',
        time: 'Hace 5 minutos',
        read: false,
    },
    {
        id: '2',
        type: 'client',
        title: 'Nuevo cliente registrado',
        description: 'Carlos Pérez se registró desde la página de contacto.',
        time: 'Hace 1 hora',
        read: false,
    },
    {
        id: '3',
        type: 'renewal',
        title: 'Renovación de cuenta',
        description: 'La cuenta de Disney+ fue renovada por 1 mes.',
        time: 'Hace 3 horas',
        read: false,
    },
    {
        id: '4',
        type: 'alert',
        title: 'Cuenta por vencer',
        description: 'La cuenta de HBO Max vence en 2 días.',
        time: 'Ayer',
        read: true,
    },
    {
        id: '5',
        type: 'payment',
        title: 'Pago recibido',
        description: 'Ana Torres pagó $8.00 por su perfil de Spotify.',
        time: 'Hace 2 días',
        read: true,
    },
];

export function NotificationBell() {
    const [notifications, setNotifications] = useState<Notification[]>(sampleNotifications);

    const unreadCount = notifications.filter((notification) => !notification.read).length;

    const markAllAsRead = () => {
        setNotifications((current) =>
            current.map((notification) => ({ ...notification, read: true })),
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="relative grid size-9 place-items-center rounded-[10px] text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    aria-label="Notificaciones"
                >
                    <Bell className="size-[19px]" />
                    {unreadCount > 0 && (
                        <span className="absolute top-2 right-2.5 size-[7px] rounded-full bg-bad ring-2 ring-card" />
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between px-3 py-2.5">
                    <DropdownMenuLabel className="p-0 text-sm font-semibold">
                        Notificaciones
                        {unreadCount > 0 && (
                            <span className="ml-2 rounded-full bg-primary-soft px-1.5 py-0.5 text-[11px] font-semibold text-primary">
                                {unreadCount} nuevas
                            </span>
                        )}
                    </DropdownMenuLabel>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            onClick={markAllAsRead}
                            className="flex items-center gap-1 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                        >
                            <CheckCheck className="size-3.5" />
                            Marcar leídas
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator className="my-0" />
                <div className="max-h-80 overflow-y-auto">
                    {notifications.length === 0 ? (
                        <div className="px-3 py-8 text-center text-sm text-muted-foreground">
                            No tienes notificaciones.
                        </div>
                    ) : (
                        notifications.map((notification) => {
                            const { icon: Icon, className } = typeConfig[notification.type];

                            return (
                                <div
                                    key={notification.id}
                                    className={cn(
                                        'flex items-start gap-3 px-3 py-3 transition-colors hover:bg-muted',
                                        !notification.read && 'bg-muted/40',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 grid size-8 shrink-0 place-items-center rounded-full',
                                            className,
                                        )}
                                    >
                                        <Icon className="size-4" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
                                            {notification.title}
                                            {!notification.read && (
                                                <span className="size-1.5 shrink-0 rounded-full bg-primary" />
                                            )}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {notification.description}
                                        </p>
                                        <p className="mt-0.5 text-[11px] text-muted-foreground/70">
                                            {notification.time}
                                        </p>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
