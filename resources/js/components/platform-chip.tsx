import { PLATFORMS } from '@/lib/crm-demo';
import type { PerfilDemo, PlatformId } from '@/types/streaming';

interface PlatformBadgeProps {
    plat: PlatformId;
    size?: number;
    className?: string;
}

/** Cuadrito de color de marca con las iniciales de la plataforma (sin logos). */
export function PlatformBadge({
    plat,
    size = 28,
    className = '',
}: PlatformBadgeProps) {
    const p = PLATFORMS[plat];
    return (
        <span
            className={`grid shrink-0 place-items-center rounded-lg font-extrabold tracking-tight text-white ${className}`}
            style={{
                width: size,
                height: size,
                fontSize: size * 0.42,
                background: p.color,
                boxShadow: `0 2px 6px color-mix(in srgb, ${p.color} 30%, transparent)`,
            }}
            title={p.name}
        >
            {p.short}
        </span>
    );
}

interface PlatformChipProps {
    plat: PlatformId;
    size?: 'sm' | 'md' | 'lg';
    showName?: boolean;
}

/** Chip de plataforma: badge de color + nombre. */
export function PlatformChip({
    plat,
    size = 'md',
    showName = true,
}: PlatformChipProps) {
    const dims = size === 'sm' ? 22 : size === 'lg' ? 34 : 28;
    return (
        <span className="inline-flex items-center gap-2">
            <PlatformBadge plat={plat} size={dims} />
            {showName && (
                <span className="text-[13.5px] font-bold">
                    {PLATFORMS[plat].name}
                </span>
            )}
        </span>
    );
}

interface PlatformStackProps {
    perfiles: Pick<PerfilDemo, 'plat'>[];
    max?: number;
}

/** Pila de badges de plataforma superpuestos (para filas de tabla). */
export function PlatformStack({ perfiles, max = 4 }: PlatformStackProps) {
    if (perfiles.length === 0) {
        return <span className="text-input">—</span>;
    }
    return (
        <div className="flex">
            {perfiles.slice(0, max).map((p, i) => (
                <PlatformBadge
                    key={i}
                    plat={p.plat}
                    size={24}
                    className={`rounded-[7px] ring-2 ring-card ${i > 0 ? '-ml-1.5' : ''}`}
                />
            ))}
        </div>
    );
}
