import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';

export interface PaginationMeta {
    total: number;
    limit: number;
    offset: number;
    has_more?: boolean;
}

export interface PaginationProps {
    meta: PaginationMeta;
    onPageChange: (offset: number) => void;
    label?: string;
    labelPlural?: string;
    className?: string;
}

function getPageNumbers(
    currentPage: number,
    totalPages: number,
): (number | 'ellipsis')[] {
    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
    }

    if (currentPage <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis', totalPages];
    }

    if (currentPage >= totalPages - 3) {
        return [
            1,
            'ellipsis',
            totalPages - 4,
            totalPages - 3,
            totalPages - 2,
            totalPages - 1,
            totalPages,
        ];
    }

    return [
        1,
        'ellipsis',
        currentPage - 1,
        currentPage,
        currentPage + 1,
        'ellipsis',
        totalPages,
    ];
}

export function Pagination({
    meta,
    onPageChange,
    label = 'registro',
    labelPlural,
    className = '',
}: PaginationProps) {
    const limit = meta.limit > 0 ? meta.limit : 10;
    const offset = meta.offset >= 0 ? meta.offset : 0;
    const total = meta.total >= 0 ? meta.total : 0;

    const currentPage = Math.floor(offset / limit) + 1;
    const totalPages = Math.max(1, Math.ceil(total / limit));

    const start = total === 0 ? 0 : offset + 1;
    const end = Math.min(offset + limit, total);

    const plural =
        labelPlural ??
        (label.endsWith('n') ||
        label.endsWith('r') ||
        label.endsWith('l') ||
        label.endsWith('d')
            ? `${label}es`
            : `${label}s`);
    const entityText = total === 1 ? label : plural;

    const pages = getPageNumbers(currentPage, totalPages);

    return (
        <div
            className={`flex flex-col items-center justify-between gap-3 border-t px-5 py-3 text-[13px] sm:flex-row ${className}`}
        >
            <div className="font-semibold text-muted-foreground">
                {total === 0 ? (
                    <span>0 {plural}</span>
                ) : (
                    <span>
                        Mostrando {start} a {end} de {total} {entityText}
                    </span>
                )}
            </div>

            {totalPages > 1 && (
                <div className="flex items-center gap-1.5">
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 px-2.5 text-xs font-medium"
                        onClick={() =>
                            onPageChange(Math.max(0, offset - limit))
                        }
                        disabled={currentPage <= 1}
                    >
                        <ChevronLeft className="mr-1 h-3.5 w-3.5" />
                        Anterior
                    </Button>

                    <div className="flex items-center gap-1">
                        {pages.map((p, idx) => {
                            if (p === 'ellipsis') {
                                return (
                                    <span
                                        key={`ellipsis-${idx}`}
                                        className="select-none px-1 text-muted-foreground"
                                    >
                                        …
                                    </span>
                                );
                            }

                            const isActive = p === currentPage;
                            return (
                                <Button
                                    key={p}
                                    variant={isActive ? 'default' : 'outline'}
                                    size="sm"
                                    className={`h-8 min-w-[32px] px-2 text-xs font-semibold ${
                                        isActive
                                            ? 'pointer-events-none'
                                            : ''
                                    }`}
                                    onClick={() =>
                                        onPageChange((p - 1) * limit)
                                    }
                                >
                                    {p}
                                </Button>
                            );
                        })}
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        className="h-8 px-2.5 text-xs font-medium"
                        onClick={() => onPageChange(offset + limit)}
                        disabled={currentPage >= totalPages}
                    >
                        Siguiente
                        <ChevronRight className="ml-1 h-3.5 w-3.5" />
                    </Button>
                </div>
            )}
        </div>
    );
}
