import { Check, Copy, KeyRound } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useClipboard } from '@/hooks/use-clipboard';

interface StoreApiKeyDialogProps {
    apiKey: string | null;
    onClose: () => void;
}

/**
 * La llave en claro se muestra una sola vez, recién generada: el ERP guarda
 * solo su hash y no puede volver a enseñarla.
 */
export function StoreApiKeyDialog({ apiKey, onClose }: StoreApiKeyDialogProps) {
    const [, copy] = useClipboard();
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        if (!apiKey) {
            return;
        }

        const ok = await copy(apiKey);
        setCopied(ok);
    };

    return (
        <Dialog
            open={apiKey !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <KeyRound className="size-4" />
                        Llave de acceso de la tienda
                    </DialogTitle>
                    <DialogDescription>
                        Cópiala ahora y guárdala en la variable{' '}
                        <code className="rounded bg-muted px-1">
                            STORE_API_KEY
                        </code>{' '}
                        de la tienda. No se volverá a mostrar: si se pierde hay
                        que generar otra, y la anterior deja de funcionar.
                    </DialogDescription>
                </DialogHeader>
                <div className="flex items-center gap-2">
                    <code className="min-w-0 flex-1 truncate rounded-[10px] border bg-muted px-3 py-2.5 font-mono text-[13px]">
                        {apiKey}
                    </code>
                    <Button
                        type="button"
                        variant="outline"
                        className="h-10 rounded-[10px]"
                        onClick={handleCopy}
                    >
                        {copied ? <Check /> : <Copy />}
                        {copied ? 'Copiada' : 'Copiar'}
                    </Button>
                </div>
                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        Ya la guardé
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
