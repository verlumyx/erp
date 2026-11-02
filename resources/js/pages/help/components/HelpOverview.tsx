import React from 'react';
import {
    ArrowRight,
    BadgePercent,
    Boxes,
    Building2,
    CalendarCheck,
    CheckCircle2,
    CreditCard,
    FileText,
    FolderTree,
    Lightbulb,
    Lock,
    Monitor,
    Package,
    Receipt,
    RefreshCw,
    Scale,
    ShieldCheck,
    ShoppingBag,
    ShoppingCart,
    Sparkles,
    Truck,
    Users,
} from 'lucide-react';
import { HELP_MODULES } from '../data/help-topics';
import { HelpModuleGroup, HelpTopic } from '../types';

interface HelpOverviewProps {
    onSelectTopic: (topicId: string) => void;
    onSearchFocus?: () => void;
}

const MODULE_ICONS: Record<string, React.ElementType> = {
    FolderTree,
    Package,
    ShoppingCart,
    ShoppingBag,
    Truck,
    Monitor,
};

export function HelpOverview({ onSelectTopic }: HelpOverviewProps) {
    const erpWorkflow = [
        {
            step: 1,
            title: 'Catálogo',
            desc: 'Configura categorías, impuestos, unidades y tasas.',
            icon: FolderTree,
            topicId: 'catalogo-categorias',
        },
        {
            step: 2,
            title: 'Compras',
            desc: 'Registra proveedores, órdenes y facturas.',
            icon: ShoppingCart,
            topicId: 'compras-ordenes-compra',
        },
        {
            step: 3,
            title: 'Inventario',
            desc: 'Controla artículos, existencias, lotes y Kardex.',
            icon: Package,
            topicId: 'inventario-existencias',
        },
        {
            step: 4,
            title: 'Logística',
            desc: 'Recepciones de entrada y guías de despacho.',
            icon: Truck,
            topicId: 'logistica-despachos',
        },
        {
            step: 5,
            title: 'Ventas & POS',
            desc: 'Facturación rápida, cobros y cierre de caja.',
            icon: Monitor,
            topicId: 'pos-terminal',
        },
    ];

    return (
        <div className="max-w-5xl mx-auto space-y-12 pb-16">
            {/* Hero Section */}
            <section className="relative overflow-hidden rounded-3xl border border-primary/20 bg-gradient-to-br from-primary/10 via-background to-primary/5 p-8 sm:p-12 shadow-sm text-center">
                <div className="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-3.5 py-1 text-xs font-bold text-primary mb-4">
                    <Sparkles className="size-3.5" />
                    Guía Rápida & Documentación
                </div>
                <h1 className="text-3xl font-black tracking-tight text-foreground sm:text-5xl max-w-2xl mx-auto">
                    Aprende a dominar tu sistema paso a paso
                </h1>
                <p className="mt-4 text-sm sm:text-base text-muted-foreground max-w-xl mx-auto leading-relaxed">
                    Todo lo que necesitas saber para operar los módulos de Catálogo, Inventario, Compras, Ventas, Logística y Punto de Venta de manera fácil y eficiente.
                </p>
            </section>

            {/* ERP Flow Diagram */}
            <section className="space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
                            <span>🔄</span> El Ciclo Operativo del Sistema
                        </h2>
                        <p className="text-xs sm:text-sm text-muted-foreground">
                            Cómo se conectan los módulos entre sí en el día a día
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
                    {erpWorkflow.map((item, index) => {
                        const Icon = item.icon;
                        return (
                            <button
                                key={item.step}
                                onClick={() => onSelectTopic(item.topicId)}
                                className="group relative flex flex-col items-start rounded-2xl border border-border/80 bg-card p-4 text-left transition-all hover:border-primary/50 hover:shadow-xs hover:-translate-y-0.5"
                            >
                                <div className="flex items-center justify-between w-full mb-3">
                                    <div className="flex size-8 items-center justify-center rounded-xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-all">
                                        <Icon className="size-4" />
                                    </div>
                                    <span className="text-[10px] font-extrabold text-muted-foreground px-1.5 py-0.5 rounded bg-muted">
                                        Paso {item.step}
                                    </span>
                                </div>
                                <h3 className="text-sm font-bold text-foreground group-hover:text-primary transition-colors">
                                    {item.title}
                                </h3>
                                <p className="text-xs text-muted-foreground mt-1 leading-snug">
                                    {item.desc}
                                </p>
                            </button>
                        );
                    })}
                </div>
            </section>

            {/* 6 Modules Grid */}
            <section className="space-y-6">
                <div>
                    <h2 className="text-xl font-bold text-foreground">
                        Explorar por Módulos
                    </h2>
                    <p className="text-xs sm:text-sm text-muted-foreground">
                        Selecciona un módulo o tema directo para consultar su guía interactiva
                    </p>
                </div>

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {HELP_MODULES.map((module: HelpModuleGroup) => {
                        const ModuleIcon = MODULE_ICONS[module.icon] || FolderTree;

                        return (
                            <div
                                key={module.id}
                                className="flex flex-col rounded-2xl border border-border/80 bg-card p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-md"
                            >
                                {/* Header */}
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-2.5">
                                        <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <ModuleIcon className="size-4.5" />
                                        </div>
                                        <div>
                                            <h3 className="text-base font-bold text-foreground">
                                                {module.name}
                                            </h3>
                                        </div>
                                    </div>
                                    <span className="rounded-full bg-muted px-2.5 py-0.5 text-[11px] font-semibold text-muted-foreground">
                                        {module.badge}
                                    </span>
                                </div>

                                <p className="text-xs text-muted-foreground leading-relaxed mb-4 flex-1">
                                    {module.description}
                                </p>

                                {/* Topic Buttons list */}
                                <div className="space-y-1.5 pt-3 border-t border-border/60">
                                    {module.topics.map((topic: HelpTopic) => (
                                        <button
                                            key={topic.id}
                                            onClick={() => onSelectTopic(topic.id)}
                                            className="group flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs text-muted-foreground transition-all hover:bg-primary/10 hover:text-primary font-medium"
                                        >
                                            <span className="truncate">{topic.title}</span>
                                            <ArrowRight className="size-3 text-muted-foreground opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" />
                                        </button>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* Quick Principles Banner */}
            <section className="rounded-2xl border border-border/80 bg-card/60 p-6 space-y-4">
                <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                    <ShieldCheck className="size-5 text-primary" />
                    Reglas Fundamentales del Sistema
                </h3>
                <div className="grid gap-4 sm:grid-cols-3">
                    <div className="space-y-1 text-xs">
                        <div className="font-bold text-foreground flex items-center gap-1.5">
                            <Lock className="size-3.5 text-primary" />
                            Preservación Histórica
                        </div>
                        <p className="text-muted-foreground leading-snug">
                            Los registros nunca se borran físicamente. Si ya no usas un artículo o categoría, se desactiva para mantener auditoría.
                        </p>
                    </div>

                    <div className="space-y-1 text-xs">
                        <div className="font-bold text-foreground flex items-center gap-1.5">
                            <RefreshCw className="size-3.5 text-primary" />
                            Tasa Diaria
                        </div>
                        <p className="text-muted-foreground leading-snug">
                            Actualiza la tasa oficial en Catálogo a primera hora del día para que ventas, cobranzas y precios funcionen sincronizados.
                        </p>
                    </div>

                    <div className="space-y-1 text-xs">
                        <div className="font-bold text-foreground flex items-center gap-1.5">
                            <CheckCircle2 className="size-3.5 text-primary" />
                            Apertura y Cierre de Caja
                        </div>
                        <p className="text-muted-foreground leading-snug">
                            Cada cajero debe iniciar con su fondo de caja y realizar el arqueo ciego al terminar el turno para evitar descuadres.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    );
}
