import { MessageSquare } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

interface LineNotePopoverProps {
    value: string;
    onValueChange: (value: string) => void;
    ariaLabel: string;
    placeholder?: string;
    maxLength?: number;
}

/**
 * Nota de una línea de documento. Vive en un popover anclado al icono para no
 * gastar una columna del formulario; el punto sobre el icono avisa que la línea
 * ya tiene una nota escrita.
 */
export function LineNotePopover({
    value,
    onValueChange,
    ariaLabel,
    placeholder = 'Escribe una nota para esta línea…',
    maxLength = 500,
}: LineNotePopoverProps) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const hasNote = value.trim() !== '';

    useEffect(() => {
        if (!open) {
            return;
        }

        textareaRef.current?.focus();

        const closeOnOutside = (event: MouseEvent) => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', closeOnOutside);
        document.addEventListener('keydown', closeOnEscape);

        return () => {
            document.removeEventListener('mousedown', closeOnOutside);
            document.removeEventListener('keydown', closeOnEscape);
        };
    }, [open]);

    return (
        <div ref={containerRef} className="relative">
            <Button
                type="button"
                variant="outline"
                size="icon"
                className={cn(
                    'relative size-[42px] rounded-[10px] bg-card',
                    hasNote && 'text-primary',
                )}
                onClick={() => setOpen((current) => !current)}
                aria-label={ariaLabel}
                aria-expanded={open}
            >
                <MessageSquare className="size-4" />
                {hasNote && (
                    <span className="absolute top-1.5 right-1.5 size-[7px] rounded-full bg-primary ring-2 ring-card" />
                )}
            </Button>

            {open && (
                <div className="absolute top-[calc(100%+6px)] right-0 z-50 w-72 rounded-[12px] border bg-card p-3 shadow-lg">
                    <Label className="text-[13px] font-semibold">
                        Nota de la línea
                    </Label>
                    <Textarea
                        ref={textareaRef}
                        value={value}
                        onChange={(e) => onValueChange(e.target.value)}
                        placeholder={placeholder}
                        maxLength={maxLength}
                        className="mt-1.5 min-h-[90px] resize-none rounded-[10px]"
                    />
                    <p className="mt-1 text-right text-[11px] text-muted-foreground">
                        {value.length}/{maxLength}
                    </p>
                </div>
            )}
        </div>
    );
}
