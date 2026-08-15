import { ImageIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ServiceLogoProps {
    name: string;
    logoUrl: string | null;
    /** Tailwind size + rounding classes for the wrapper. */
    className?: string;
    iconClassName?: string;
}

/**
 * Logo de un servicio con fallback al ícono por defecto cuando la URL
 * está vacía o la imagen no carga (404, URL inválida, etc.).
 */
export function ServiceLogo({
    name,
    logoUrl,
    className = 'size-10 rounded-[11px]',
    iconClassName = 'size-5',
}: ServiceLogoProps) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [logoUrl]);

    const showImage = logoUrl && !failed;

    return (
        <span
            className={`grid shrink-0 place-items-center overflow-hidden border bg-muted text-muted-foreground ${className}`}
        >
            {showImage ? (
                <img
                    src={logoUrl}
                    alt={name}
                    className="size-full object-cover"
                    onError={() => setFailed(true)}
                />
            ) : (
                <ImageIcon className={iconClassName} />
            )}
        </span>
    );
}
