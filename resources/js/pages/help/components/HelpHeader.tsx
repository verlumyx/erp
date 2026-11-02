import React from 'react';
import { ArrowLeft, BookOpen, Menu, Printer, Search, Sparkles, X } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface HelpHeaderProps {
    companyId?: string | null;
    searchQuery: string;
    onSearchChange: (query: string) => void;
    onPrint: () => void;
    mobileMenuOpen: boolean;
    onToggleMobileMenu: () => void;
}

export function HelpHeader({
    companyId,
    searchQuery,
    onSearchChange,
    onPrint,
    mobileMenuOpen,
    onToggleMobileMenu,
}: HelpHeaderProps) {
    const dashboardUrl = companyId ? `/${companyId}/dashboard` : '/dashboard';

    return (
        <header className="fixed top-0 left-0 right-0 z-40 flex h-16 w-full shrink-0 items-center justify-between border-b border-border/80 bg-background/95 px-4 backdrop-blur-md md:px-8 shadow-xs">
            {/* Left: Logo & Mobile Toggle */}
            <div className="flex items-center gap-3">
                <Button
                    variant="ghost"
                    size="icon"
                    className="md:hidden"
                    onClick={onToggleMobileMenu}
                    aria-label="Abrir menú"
                >
                    {mobileMenuOpen ? <X className="size-5" /> : <Menu className="size-5" />}
                </Button>

                <div className="flex items-center gap-2.5">
                    <div
                        className="flex size-9 items-center justify-center rounded-xl text-white shadow-sm"
                        style={{
                            background: 'linear-gradient(135deg, #4f46e5, #3b82f6)',
                        }}
                    >
                        <Sparkles className="size-4.5" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-base font-bold tracking-tight text-foreground">
                                Valolabs <span className="text-primary font-extrabold">Guía</span>
                            </span>
                            <span className="hidden rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary sm:inline-block">
                                Documentación ERP
                            </span>
                        </div>
                        <p className="hidden text-xs text-muted-foreground sm:block">
                            Manual interactivo y centro de aprendizaje
                        </p>
                    </div>
                </div>
            </div>

            {/* Center: Search Bar */}
            <div className="relative mx-4 hidden max-w-md flex-1 md:block">
                <Search className="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    type="search"
                    value={searchQuery}
                    onChange={(e) => onSearchChange(e.target.value)}
                    placeholder="Buscar tema, módulo o palabra clave (ej. inventario, kardex, iva)…"
                    className="h-9.5 w-full rounded-xl border border-border/80 bg-muted/60 pl-9.5 pr-10 text-xs sm:text-sm text-foreground outline-none transition-all placeholder:text-muted-foreground focus:border-primary focus:bg-background focus:ring-2 focus:ring-primary/20"
                />
                {searchQuery ? (
                    <button
                        onClick={() => onSearchChange('')}
                        className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <X className="size-4" />
                    </button>
                ) : (
                    <kbd className="absolute right-3 top-1/2 -translate-y-1/2 rounded border border-border/60 bg-background px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground">
                        ⌘K
                    </kbd>
                )}
            </div>

            {/* Right: Actions */}
            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={onPrint}
                    className="hidden gap-1.5 text-xs font-medium sm:flex print:hidden hover:border-primary/40 hover:bg-primary/5"
                    title="Imprimir o guardar la guía en formato PDF"
                >
                    <Printer className="size-3.5 text-primary" />
                    <span>Guardar PDF / Imprimir</span>
                </Button>

                <a
                    href={dashboardUrl}
                    className="inline-flex h-8.5 items-center gap-1.5 rounded-lg bg-primary px-3 text-xs font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
                    <ArrowLeft className="size-3.5" />
                    <span>Volver al Sistema</span>
                </a>
            </div>
        </header>
    );
}
