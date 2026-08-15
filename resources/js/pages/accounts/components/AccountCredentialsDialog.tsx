import { Check, Copy, Eye, EyeOff, KeyRound, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';
import accounts from '@/routes/accounts';
import type { Account } from '../types/Account';

/**
 * Formatea las credenciales como texto listo para enviar al cliente.
 */
function formatCredentials(creds: Credentials): string {
    return `Email: ${creds.email}\nContraseña: ${creds.password}`;
}

interface Credentials {
    email: string;
    password: string;
}

interface AccountCredentialsDialogProps {
    companyId: string;
    account: Account | null;
    onClose: () => void;
}

/**
 * Modal de credenciales para el listado de cuentas. Permite ver email y
 * contraseña descifrada sin entrar a la pantalla de detalle. Consume el endpoint
 * JSON dedicado (GET /accounts/{id}/credentials), que descifra la contraseña y
 * registra el acceso en el backend; por eso usa fetch en lugar de router.
 */
export function AccountCredentialsDialog({
    companyId,
    account,
    onClose,
}: AccountCredentialsDialogProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [creds, setCreds] = useState<Credentials | null>(null);
    const [visible, setVisible] = useState(false);
    const [copied, setCopied] = useState(false);
    const [, copy] = useClipboard();

    const handleCopy = async () => {
        if (!creds) {
            return;
        }

        if (await copy(formatCredentials(creds))) {
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        }
    };

    useEffect(() => {
        if (account === null) {
            return;
        }

        let active = true;

        setLoading(true);
        setError(null);
        setCreds(null);
        setVisible(false);

        const load = async () => {
            try {
                const response = await fetch(
                    accounts.credentials({
                        company: companyId,
                        id: account.id,
                    }).url,
                    {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    },
                );

                if (!active) {
                    return;
                }

                if (response.status === 403) {
                    setError('No tienes permiso para ver las credenciales.');
                    return;
                }

                if (!response.ok) {
                    setError('No se pudieron obtener las credenciales.');
                    return;
                }

                setCreds((await response.json()) as Credentials);
                setVisible(true);
            } catch {
                if (active) {
                    setError('Error de red al obtener las credenciales.');
                }
            } finally {
                if (active) {
                    setLoading(false);
                }
            }
        };

        void load();

        return () => {
            active = false;
        };
    }, [account, companyId]);

    return (
        <Dialog
            open={account !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="inline-flex items-center gap-2">
                        <KeyRound className="size-4 text-muted-foreground" />
                        Credenciales
                    </DialogTitle>
                    <DialogDescription>
                        {account
                            ? `${account.code} · ${account.email}`
                            : 'Credenciales de la cuenta'}
                    </DialogDescription>
                </DialogHeader>

                {loading && (
                    <div className="flex items-center justify-center gap-2 py-6 text-sm text-muted-foreground">
                        <Loader2 className="size-4 animate-spin" />
                        Obteniendo credenciales…
                    </div>
                )}

                {error && <p className="py-2 text-sm text-bad">{error}</p>}

                {creds && (
                    <div className="flex flex-col gap-2.5">
                        <div className="flex items-center justify-between gap-3 text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Email
                            </span>
                            <b className="font-bold break-all select-all">
                                {creds.email}
                            </b>
                        </div>
                        <div className="flex items-center justify-between gap-3 border-t border-dashed border-input pt-2.5 text-[13.5px]">
                            <span className="font-medium text-muted-foreground">
                                Contraseña
                            </span>
                            <b className="font-mono font-bold break-all select-all">
                                {visible ? creds.password : '••••••••'}
                            </b>
                        </div>
                        <div className="mt-1 flex justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                className="rounded-[10px] bg-card font-semibold"
                                onClick={handleCopy}
                            >
                                {copied ? (
                                    <Check className="size-4 text-ok" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                                {copied ? 'Copiado' : 'Copiar'}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="rounded-[10px] bg-card font-semibold"
                                onClick={() => setVisible((v) => !v)}
                            >
                                {visible ? (
                                    <EyeOff className="size-4" />
                                ) : (
                                    <Eye className="size-4" />
                                )}
                                {visible ? 'Ocultar' : 'Mostrar'}
                            </Button>
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
