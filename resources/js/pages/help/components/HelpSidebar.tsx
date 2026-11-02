import React, { useState } from 'react';
import {
    ArrowDownToLine,
    ArrowUpFromLine,
    BadgePercent,
    Barcode,
    BookOpen,
    Boxes,
    Building2,
    CalendarCheck,
    ChevronDown,
    ChevronRight,
    ClipboardList,
    Coins,
    CreditCard,
    FileText,
    FolderTree,
    Layers,
    MapPin,
    Monitor,
    Package,
    Receipt,
    Scale,
    ShoppingBag,
    ShoppingCart,
    Store,
    Truck,
    Users,
    Warehouse,
} from 'lucide-react';
import { HELP_MODULES } from '../data/help-topics';
import { HelpModuleGroup, HelpTopic } from '../types';

interface HelpSidebarProps {
    activeTopicId: string | null;
    onSelectTopic: (topicId: string | null) => void;
    onCloseMobile?: () => void;
}

const ICON_MAP: Record<string, React.ElementType> = {
    FolderTree,
    Scale,
    Truck,
    Users,
    BadgePercent,
    Coins,
    Receipt,
    Package,
    Warehouse,
    MapPin,
    Boxes,
    Layers,
    Barcode,
    ClipboardList,
    Building2,
    ShoppingCart,
    FileText,
    CreditCard,
    ShoppingBag,
    ArrowUpFromLine,
    ArrowDownToLine,
    Monitor,
    CalendarCheck,
    Store,
};

export function HelpSidebar({
    activeTopicId,
    onSelectTopic,
    onCloseMobile,
}: HelpSidebarProps) {
    // Keep modules expanded by default
    const [collapsedModules, setCollapsedModules] = useState<Record<string, boolean>>({});

    const toggleModule = (moduleId: string) => {
        setCollapsedModules((prev) => ({
            ...prev,
            [moduleId]: !prev[moduleId],
        }));
    };

    const handleSelect = (topicId: string | null) => {
        onSelectTopic(topicId);
        if (onCloseMobile) {
            onCloseMobile();
        }
    };

    return (
        <aside className="flex h-full w-full flex-col bg-card/60">
            {/* Top Quick Action */}
            <div className="p-3 border-b border-border/60">
                <button
                    onClick={() => handleSelect(null)}
                    className={`flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold transition-all ${
                        activeTopicId === null
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                    }`}
                >
                    <BookOpen className="size-4 shrink-0" />
                    <span>Vista General de Módulos</span>
                </button>
            </div>

            {/* Modules List */}
            <nav className="flex-1 overflow-y-auto px-2.5 py-3 space-y-4">
                {HELP_MODULES.map((module: HelpModuleGroup) => {
                    const ModuleIcon = ICON_MAP[module.icon] || FolderTree;
                    const isCollapsed = !!collapsedModules[module.id];
                    const hasActiveChild = module.topics.some((t) => t.id === activeTopicId);

                    return (
                        <div key={module.id} className="space-y-1">
                            {/* Module Header Header */}
                            <button
                                onClick={() => toggleModule(module.id)}
                                className={`flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-bold uppercase tracking-wider transition-colors ${
                                    hasActiveChild
                                        ? 'text-primary'
                                        : 'text-muted-foreground hover:text-foreground'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <ModuleIcon className="size-3.5" />
                                    <span>{module.name}</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="text-[10px] lowercase font-normal px-1.5 py-0.2 rounded bg-muted text-muted-foreground">
                                        {module.topics.length}
                                    </span>
                                    {isCollapsed ? (
                                        <ChevronRight className="size-3.5 opacity-60" />
                                    ) : (
                                        <ChevronDown className="size-3.5 opacity-60" />
                                    )}
                                </div>
                            </button>

                            {/* Topics List */}
                            {!isCollapsed && (
                                <div className="ml-2 pl-2 border-l border-border/80 space-y-0.5 pt-0.5">
                                    {module.topics.map((topic: HelpTopic) => {
                                        const TopicIcon = ICON_MAP[topic.moduleIcon] || FolderTree;
                                        const isActive = topic.id === activeTopicId;

                                        return (
                                            <button
                                                key={topic.id}
                                                onClick={() => handleSelect(topic.id)}
                                                className={`group flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-left text-xs transition-all ${
                                                    isActive
                                                        ? 'bg-primary/10 font-semibold text-primary'
                                                        : 'text-muted-foreground hover:bg-accent/80 hover:text-foreground'
                                                }`}
                                            >
                                                <div className="flex items-center gap-2 truncate">
                                                    <TopicIcon
                                                        className={`size-3.5 shrink-0 transition-colors ${
                                                            isActive
                                                                ? 'text-primary'
                                                                : 'text-muted-foreground group-hover:text-foreground'
                                                        }`}
                                                    />
                                                    <span className="truncate">{topic.title}</span>
                                                </div>
                                                {isActive && (
                                                    <span className="size-1.5 rounded-full bg-primary shrink-0" />
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    );
                })}
            </nav>

            {/* Bottom Tip Card */}
            <div className="p-3 border-t border-border/60">
                <div className="rounded-xl bg-primary/5 p-3 text-[11px] text-muted-foreground border border-primary/10">
                    <p className="font-semibold text-foreground mb-1">💡 ¿Tienes dudas rápidas?</p>
                    <p className="leading-snug">
                        Usa la barra de búsqueda superior para encontrar temas o atajos al instante.
                    </p>
                </div>
            </div>
        </aside>
    );
}
