import React from 'react';
import { ArrowRight, Search, Sparkles } from 'lucide-react';
import { HelpTopic } from '../types';

interface HelpSearchResultsProps {
    query: string;
    results: HelpTopic[];
    onSelectTopic: (topicId: string) => void;
    onClearSearch: () => void;
}

export function HelpSearchResults({
    query,
    results,
    onSelectTopic,
    onClearSearch,
}: HelpSearchResultsProps) {
    return (
        <div className="max-w-4xl mx-auto space-y-6 pb-16">
            <div className="flex items-center justify-between border-b border-border/70 pb-4">
                <div>
                    <h2 className="text-xl font-bold text-foreground flex items-center gap-2">
                        <Search className="size-5 text-primary" />
                        Resultados de búsqueda
                    </h2>
                    <p className="text-xs sm:text-sm text-muted-foreground mt-0.5">
                        Mostrando {results.length} resultado{results.length === 1 ? '' : 's'} para "{query}"
                    </p>
                </div>
                <button
                    onClick={onClearSearch}
                    className="text-xs text-primary font-semibold hover:underline"
                >
                    Limpiar búsqueda
                </button>
            </div>

            {results.length === 0 ? (
                <div className="rounded-2xl border border-dashed border-border p-12 text-center space-y-3">
                    <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                        <Search className="size-6" />
                    </div>
                    <h3 className="text-base font-bold text-foreground">
                        No se encontraron temas para "{query}"
                    </h3>
                    <p className="text-xs sm:text-sm text-muted-foreground max-w-sm mx-auto">
                        Intenta con términos más generales como "factura", "kardex", "cliente", "lote" o navega por los módulos del menú lateral.
                    </p>
                </div>
            ) : (
                <div className="space-y-3">
                    {results.map((topic) => (
                        <button
                            key={topic.id}
                            onClick={() => onSelectTopic(topic.id)}
                            className="group flex w-full items-start justify-between rounded-2xl border border-border/80 bg-card p-5 text-left transition-all hover:border-primary/50 hover:shadow-xs"
                        >
                            <div className="space-y-1.5 pr-4">
                                <div className="flex items-center gap-2">
                                    <span className="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-bold text-primary uppercase tracking-wide">
                                        {topic.moduleName}
                                    </span>
                                    <h3 className="text-base font-bold text-foreground group-hover:text-primary transition-colors">
                                        {topic.title}
                                    </h3>
                                </div>
                                <p className="text-xs text-muted-foreground line-clamp-2">
                                    {topic.summary}
                                </p>
                            </div>
                            <ArrowRight className="size-5 shrink-0 text-muted-foreground group-hover:text-primary group-hover:translate-x-1 transition-all mt-1" />
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
