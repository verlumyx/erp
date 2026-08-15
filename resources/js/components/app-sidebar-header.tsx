import { Search } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { CompanySwitcher } from '@/components/company-switcher';
import { NotificationBell } from '@/components/notification-bell';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-sidebar-border/50 bg-background/80 px-6 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="mx-auto hidden h-10 w-full max-w-md items-center gap-2.5 rounded-[11px] border bg-muted px-3 text-muted-foreground transition-[border-color,box-shadow] focus-within:border-primary focus-within:ring-[3px] focus-within:ring-primary-soft md:flex">
                <Search className="size-[17px] shrink-0" />
                <input
                    className="w-full bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground"
                    placeholder="Buscar cliente, perfil o cuenta…"
                />
                <kbd className="rounded-md border bg-card px-1.5 py-0.5 text-[11px] font-semibold text-muted-foreground">
                    ⌘K
                </kbd>
            </div>
            <div className="ml-auto flex items-center gap-3 md:ml-0">
                <NotificationBell />
                <CompanySwitcher />
            </div>
        </header>
    );
}
