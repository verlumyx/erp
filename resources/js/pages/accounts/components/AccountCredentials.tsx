import { Check, Copy, Eye, EyeOff, KeyRound, Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useClipboard } from '@/hooks/use-clipboard';
import accounts from '@/routes/accounts';

interface Credentials {
    email: string;
    password: string;
}

/**
 * Formatea las credenciales como texto listo para enviar al cliente.
 */
function formatCredentials(creds: Credentials): string {
    return `Email: ${creds.email}\nContraseña: ${creds.password}`;
}

interface AccountCredentialsProps {
    companyId: string;
    accountId: string;
}

/**
 * Botón «Ver credenciales». Consume el endpoint JSON dedicado
 * (GET /accounts/{id}/credentials), que descifra la contraseña y registra el
 * acceso en el backend. Es un endpoint de solo lectura fuera del flujo Inertia,
 * por eso se usa fetch en lugar de router.
 */
export function AccountCredentials({
    companyId,
    accountId,
}: AccountCredentialsProps) {
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

    const reveal = async () => {
        if (creds) {
            setVisible((v) => !v);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(
                accounts.credentials({ company: companyId, id: accountId }).url,
                {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                },
            );

            if (response.status === 403) {
                setError('No tienes permiso para ver las credenciales.');
                return;
            }

            if (!response.ok) {
                setError('No se pudieron obtener las credentials.');
                return;
            }

            setCreds((await response.json()) as Credentials);
            setVisible(true);
        } catch {
            setError('Error de red al obtener las credentials.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Card className="gap-3 rounded-2xl p-5">
            <div className="flex items-center justify-between gap-3">
                <div className="inline-flex items-center gap-2 text-base font-bold tracking-tight">
                    <KeyRound className="size-4 text-muted-foreground" />
                    Credenciales
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    className="rounded-[10px] bg-card font-semibold"
                    onClick={reveal}
                    disabled={loading}
                >
                    {loading ? (
                        <Loader2 className="size-4 animate-spin" />
                    ) : visible ? (
                        <EyeOff className="size-4" />
                    ) : (
                        <Eye className="size-4" />
                    )}
                    {visible ? 'Ocultar' : 'Ver credenciales'}
                </Button>
            </div>

            {error && <p className="text-sm text-bad">{error}</p>}

            {creds && visible && (
                <div className="flex flex-col gap-2.5 border-t border-dashed border-input pt-3">
                    <div className="flex items-center justify-between gap-3 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Email
                        </span>
                        <b className="font-bold break-all select-all">
                            {creds.email}
                        </b>
                    </div>
                    <div className="flex items-center justify-between gap-3 text-[13.5px]">
                        <span className="font-medium text-muted-foreground">
                            Contraseña
                        </span>
                        <b className="font-mono font-bold break-all select-all">
                            {creds.password}
                        </b>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        className="mt-1 self-end rounded-[10px] bg-card font-semibold"
                        onClick={handleCopy}
                    >
                        {copied ? (
                            <Check className="size-4 text-ok" />
                        ) : (
                            <Copy className="size-4" />
                        )}
                        {copied ? 'Copiado' : 'Copiar'}
                    </Button>
                </div>
            )}
        </Card>
    );
}
