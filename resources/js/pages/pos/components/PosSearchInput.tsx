import { forwardRef, useEffect, useRef } from 'react';
import { Search, Barcode } from 'lucide-react';
import { Input } from '@/components/ui/input';

interface PosSearchInputProps {
    value: string;
    onChange: (value: string) => void;
    onEnter: () => void;
    placeholder?: string;
}

export const PosSearchInput = forwardRef<HTMLInputElement, PosSearchInputProps>(
    ({ value, onChange, onEnter, placeholder = 'Buscar por nombre, código o escanear código de barras [F1]...' }, ref) => {
        const localRef = useRef<HTMLInputElement>(null);
        const inputRef = (ref as React.RefObject<HTMLInputElement>) || localRef;

        useEffect(() => {
            inputRef.current?.focus();
        }, [inputRef]);

        const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                onEnter();
            }
        };

        return (
            <div className="relative w-full">
                <Search className="absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                <Input
                    ref={inputRef}
                    type="text"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    className="h-12 w-full pl-11 pr-24 text-base font-medium shadow-xs focus-visible:ring-2 focus-visible:ring-primary rounded-xl bg-card border-border"
                />
                <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 px-2 py-0.5 rounded border bg-muted/60 text-muted-foreground text-xs font-mono">
                    <Barcode className="h-4 w-4" />
                    <span>F1</span>
                </div>
            </div>
        );
    }
);

PosSearchInput.displayName = 'PosSearchInput';
