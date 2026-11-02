import React, { useState } from 'react';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    ArrowUpRight,
    Check,
    Clock,
    Copy,
    HelpCircle,
    Info,
    Lightbulb,
    Link as LinkIcon,
    Sparkles,
} from 'lucide-react';
import { ALL_TOPICS } from '../data/help-topics';
import { HelpStep, HelpTopic } from '../types';

interface HelpArticleProps {
    topic: HelpTopic;
    companyId?: string | null;
    onSelectTopic: (topicId: string) => void;
}

export function HelpArticle({
    topic,
    companyId,
    onSelectTopic,
}: HelpArticleProps) {
    const [copied, setCopied] = useState(false);

    // Find previous and next topics
    const currentIndex = ALL_TOPICS.findIndex((t) => t.id === topic.id);
    const prevTopic = currentIndex > 0 ? ALL_TOPICS[currentIndex - 1] : null;
    const nextTopic = currentIndex < ALL_TOPICS.length - 1 ? ALL_TOPICS[currentIndex + 1] : null;

    const handleCopyLink = () => {
        const url = `${window.location.origin}${window.location.pathname}#${topic.id}`;
        navigator.clipboard.writeText(url);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    // Calculate system URL
    const systemUrl = topic.systemPath
        ? companyId
            ? `/${companyId}${topic.systemPath}`
            : topic.systemPath
        : null;

    return (
        <article className="max-w-4xl mx-auto space-y-8 pb-16 print:space-y-4 print:pb-0">
            {/* Breadcrumb & Quick Actions */}
            <div className="flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground print:hidden">
                <nav className="flex items-center gap-1.5 font-medium">
                    <button
                        onClick={() => (window.location.hash = '')}
                        className="hover:text-foreground transition-colors"
                    >
                        Inicio
                    </button>
                    <span>/</span>
                    <span className="text-foreground/80">{topic.moduleName}</span>
                    <span>/</span>
                    <span className="text-primary font-semibold">{topic.title}</span>
                </nav>

                <div className="flex items-center gap-2">
                    <button
                        onClick={handleCopyLink}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-border/80 bg-background px-2.5 py-1 text-xs text-muted-foreground hover:bg-accent hover:text-foreground transition-all"
                        title="Copiar enlace a este tema"
                    >
                        {copied ? (
                            <>
                                <Check className="size-3.5 text-ok" />
                                <span className="text-ok font-medium">¡Enlace copiado!</span>
                            </>
                        ) : (
                            <>
                                <Copy className="size-3.5" />
                                <span>Copiar enlace</span>
                            </>
                        )}
                    </button>

                    {systemUrl && (
                        <a
                            href={systemUrl}
                            className="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary hover:bg-primary/20 transition-all"
                            title="Ir a este módulo en el sistema"
                        >
                            <span>Ir al módulo</span>
                            <ArrowUpRight className="size-3.5" />
                        </a>
                    )}
                </div>
            </div>

            {/* Header Section */}
            <header className="space-y-3 border-b border-border/70 pb-6">
                <div className="flex items-center gap-2">
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary uppercase tracking-wider">
                        <Sparkles className="size-3" />
                        Módulo de {topic.moduleName}
                    </span>
                    <span className="inline-flex items-center gap-1 text-xs text-muted-foreground">
                        <Clock className="size-3.5" />
                        Lectura: 2 min
                    </span>
                </div>

                <h1 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
                    {topic.title}
                </h1>
                <p className="text-base sm:text-lg text-muted-foreground leading-relaxed">
                    {topic.subtitle}
                </p>
            </header>

            {/* Summary Box */}
            <section className="rounded-2xl border border-primary/20 bg-primary/5 p-5 sm:p-6 shadow-sm">
                <div className="flex items-start gap-3.5">
                    <div className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                        <Lightbulb className="size-5" />
                    </div>
                    <div>
                        <h2 className="text-sm font-bold uppercase tracking-wide text-primary mb-1">
                            Resumen rápido
                        </h2>
                        <p className="text-sm sm:text-base text-foreground/90 leading-relaxed font-medium">
                            {topic.summary}
                        </p>
                    </div>
                </div>
            </section>

            {/* What is it and Why it matters */}
            <section className="grid gap-6 md:grid-cols-2">
                <div className="rounded-2xl border border-border/80 bg-card p-6 shadow-xs space-y-3">
                    <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                        <span className="flex size-6 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 text-xs font-bold">
                            ?
                        </span>
                        ¿Qué es y para qué sirve?
                    </h3>
                    <p className="text-sm text-muted-foreground leading-relaxed">
                        {topic.whatIsIt}
                    </p>
                </div>

                <div className="rounded-2xl border border-border/80 bg-card p-6 shadow-xs space-y-3">
                    <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                        <span className="flex size-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-bold">
                            ★
                        </span>
                        ¿Por qué es importante en tu negocio?
                    </h3>
                    <p className="text-sm text-muted-foreground leading-relaxed">
                        {topic.whyItMatters}
                    </p>
                </div>
            </section>

            {/* Key Concepts */}
            {topic.keyConcepts.length > 0 && (
                <section className="space-y-4">
                    <h3 className="text-lg font-bold text-foreground flex items-center gap-2">
                        <span>📌</span> Conceptos clave que debes conocer
                    </h3>
                    <div className="grid gap-3.5 sm:grid-cols-2">
                        {topic.keyConcepts.map((concept, index) => (
                            <div
                                key={index}
                                className="rounded-xl border border-border/70 bg-card/60 p-4 hover:border-border transition-colors"
                            >
                                <h4 className="text-xs font-bold uppercase tracking-wider text-primary mb-1.5">
                                    {concept.term}
                                </h4>
                                <p className="text-xs sm:text-sm text-muted-foreground leading-snug">
                                    {concept.explanation}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Step by Step Guide */}
            {topic.stepByStep.length > 0 && (
                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-bold text-foreground flex items-center gap-2">
                            <span>🚀</span> Paso a paso recomendado
                        </h3>
                        <span className="text-xs text-muted-foreground font-medium">
                            {topic.stepByStep.length} pasos sencillos
                        </span>
                    </div>

                    <div className="space-y-3">
                        {topic.stepByStep.map((step: HelpStep) => (
                            <div
                                key={step.number}
                                className="group flex items-start gap-4 rounded-xl border border-border/80 bg-card p-4 transition-all hover:border-primary/40 hover:shadow-xs"
                            >
                                <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-sm group-hover:bg-primary group-hover:text-primary-foreground transition-all">
                                    {step.number}
                                </div>
                                <div className="flex-1 space-y-1">
                                    <h4 className="text-sm font-bold text-foreground">
                                        {step.title}
                                    </h4>
                                    <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                        {step.description}
                                    </p>
                                    {step.hint && (
                                        <p className="text-[11px] text-primary font-medium mt-1">
                                            💡 Nota: {step.hint}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Tips & Warnings */}
            {topic.tips.length > 0 && (
                <section className="space-y-3">
                    <h3 className="text-base font-bold text-foreground">
                        Consejos útiles y buenas prácticas
                    </h3>
                    <div className="space-y-2.5">
                        {topic.tips.map((tip, index) => {
                            const isWarning = tip.type === 'warning';
                            const isInfo = tip.type === 'info';

                            return (
                                <div
                                    key={index}
                                    className={`flex items-start gap-3 rounded-xl p-4 text-xs sm:text-sm ${
                                        isWarning
                                            ? 'bg-amber-50 text-amber-900 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-200 dark:border-amber-900/60'
                                            : isInfo
                                            ? 'bg-blue-50 text-blue-900 border border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-200 dark:border-blue-900/60'
                                            : 'bg-emerald-50 text-emerald-900 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-900/60'
                                    }`}
                                >
                                    {isWarning ? (
                                        <AlertCircle className="size-4.5 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" />
                                    ) : isInfo ? (
                                        <Info className="size-4.5 shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" />
                                    ) : (
                                        <Lightbulb className="size-4.5 shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
                                    )}
                                    <div className="leading-relaxed">
                                        <span className="font-bold">
                                            {isWarning ? 'Atención: ' : isInfo ? 'Información: ' : 'Consejo Pro: '}
                                        </span>
                                        {tip.text}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </section>
            )}

            {/* FAQs */}
            {topic.faqs && topic.faqs.length > 0 && (
                <section className="space-y-3">
                    <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                        <HelpCircle className="size-4.5 text-primary" />
                        Preguntas frecuentes de este tema
                    </h3>
                    <div className="space-y-2.5">
                        {topic.faqs.map((faq, index) => (
                            <div
                                key={index}
                                className="rounded-xl border border-border/70 bg-card p-4 space-y-1.5"
                            >
                                <h4 className="text-xs sm:text-sm font-bold text-foreground">
                                    {faq.question}
                                </h4>
                                <p className="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                                    {faq.answer}
                                </p>
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Related Modules Connection */}
            {topic.relatedModules.length > 0 && (
                <section className="rounded-2xl border border-border/80 bg-muted/40 p-5 space-y-3 print:hidden">
                    <h3 className="text-sm font-bold uppercase tracking-wider text-foreground flex items-center gap-2">
                        <LinkIcon className="size-4 text-primary" />
                        ¿Cómo se conecta con otros módulos?
                    </h3>
                    <div className="grid gap-2.5 sm:grid-cols-2">
                        {topic.relatedModules.map((rel, index) => (
                            <button
                                key={index}
                                onClick={() => onSelectTopic(rel.topicId)}
                                className="flex items-start justify-between rounded-xl border border-border/70 bg-card p-3 text-left transition-all hover:border-primary/50 hover:bg-card/80 group"
                            >
                                <div className="space-y-0.5">
                                    <p className="text-xs font-bold text-primary group-hover:underline">
                                        {rel.name}
                                    </p>
                                    <p className="text-[11px] text-muted-foreground leading-snug">
                                        {rel.relationship}
                                    </p>
                                </div>
                                <ArrowRight className="size-4 shrink-0 text-muted-foreground group-hover:text-primary group-hover:translate-x-0.5 transition-all mt-0.5" />
                            </button>
                        ))}
                    </div>
                </section>
            )}

            {/* Bottom Pagination: Previous and Next topic */}
            <div className="grid gap-3 sm:grid-cols-2 pt-6 border-t border-border/70 print:hidden">
                {prevTopic ? (
                    <button
                        onClick={() => onSelectTopic(prevTopic.id)}
                        className="flex flex-col items-start rounded-xl border border-border/80 bg-card p-4 text-left transition-all hover:border-primary/40 hover:bg-accent/40"
                    >
                        <span className="text-[11px] font-bold text-muted-foreground uppercase flex items-center gap-1 mb-1">
                            <ArrowLeft className="size-3" /> Tema Anterior
                        </span>
                        <span className="text-sm font-bold text-foreground">
                            {prevTopic.title}
                        </span>
                        <span className="text-xs text-muted-foreground truncate w-full">
                            {prevTopic.moduleName}
                        </span>
                    </button>
                ) : (
                    <div />
                )}

                {nextTopic ? (
                    <button
                        onClick={() => onSelectTopic(nextTopic.id)}
                        className="flex flex-col items-end rounded-xl border border-border/80 bg-card p-4 text-right transition-all hover:border-primary/40 hover:bg-accent/40"
                    >
                        <span className="text-[11px] font-bold text-muted-foreground uppercase flex items-center gap-1 mb-1">
                            Siguiente Tema <ArrowRight className="size-3" />
                        </span>
                        <span className="text-sm font-bold text-foreground">
                            {nextTopic.title}
                        </span>
                        <span className="text-xs text-muted-foreground truncate w-full">
                            {nextTopic.moduleName}
                        </span>
                    </button>
                ) : (
                    <div />
                )}
            </div>
        </article>
    );
}
