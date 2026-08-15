import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

function navHref(item: NavItem): string {
    return typeof item.href === 'string' ? item.href : item.href.url;
}

// Un grupo se considera activo (y por tanto abierto) si la URL actual
// coincide con el padre o con cualquiera de sus descendientes.
function isItemActive(item: NavItem, currentUrl: string): boolean {
    if (currentUrl.startsWith(navHref(item))) {
        return true;
    }
    return (item.items ?? []).some((child) => isItemActive(child, currentUrl));
}

// Componente recursivo para renderizar submenús en el sidebar expandido
function NavSubMenu({ items }: { items: NavItem[] }) {
    const page = usePage();

    return (
        <SidebarMenuSub>
            {items.map((item) => {
                const hasChildren = item.items && item.items.length > 0;
                const itemHref =
                    typeof item.href === 'string' ? item.href : item.href.url;
                const isActive = page.url.startsWith(itemHref);

                // Si tiene hijos, renderizar como Collapsible anidado
                if (hasChildren) {
                    return (
                        <Collapsible
                            key={item.title}
                            asChild
                            defaultOpen={isItemActive(item, page.url)}
                            className="group/collapsible"
                        >
                            <SidebarMenuSubItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuSubButton>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuSubButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <NavSubMenu items={item.items!} />
                                </CollapsibleContent>
                            </SidebarMenuSubItem>
                        </Collapsible>
                    );
                }

                // Si no tiene hijos, renderizar como item normal
                return (
                    <SidebarMenuSubItem key={item.title}>
                        <SidebarMenuSubButton asChild isActive={isActive}>
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                );
            })}
        </SidebarMenuSub>
    );
}

// Componente recursivo para renderizar submenús en el dropdown (sidebar colapsado)
function DropdownSubMenu({ items }: { items: NavItem[] }) {
    const page = usePage();

    return (
        <>
            {items.map((item) => {
                const hasChildren = item.items && item.items.length > 0;
                const itemHref =
                    typeof item.href === 'string' ? item.href : item.href.url;
                const isActive = page.url.startsWith(itemHref);

                // Si tiene hijos, renderizar como DropdownMenuSub
                if (hasChildren) {
                    return (
                        <DropdownMenuSub key={item.title}>
                            <DropdownMenuSubTrigger>
                                {item.icon && <item.icon className="mr-2 h-4 w-4" />}
                                <span>{item.title}</span>
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                <DropdownSubMenu items={item.items!} />
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                    );
                }

                // Si no tiene hijos, renderizar como item normal
                return (
                    <DropdownMenuItem key={item.title} asChild>
                        <Link
                            href={item.href}
                            prefetch
                            className={isActive ? 'bg-accent' : ''}
                        >
                            {item.icon && <item.icon className="mr-2 h-4 w-4" />}
                            <span>{item.title}</span>
                        </Link>
                    </DropdownMenuItem>
                );
            })}
        </>
    );
}

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const { state } = useSidebar();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Plataforma</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const hasChildren = item.items && item.items.length > 0;
                    const itemHref =
                        typeof item.href === 'string'
                            ? item.href
                            : item.href.url;
                    const isActive = page.url.startsWith(itemHref);

                    // Si tiene hijos y el sidebar está colapsado, usar DropdownMenu
                    if (hasChildren && state === 'collapsed') {
                        return (
                            <DropdownMenu key={item.title}>
                                <DropdownMenuTrigger asChild>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton
                                            tooltip={{
                                                children: item.title,
                                            }}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    side="right"
                                    align="start"
                                    className="min-w-56"
                                >
                                    <DropdownSubMenu items={item.items!} />
                                </DropdownMenuContent>
                            </DropdownMenu>
                        );
                    }

                    // Si tiene hijos y el sidebar está expandido, usar Collapsible
                    if (hasChildren) {
                        return (
                            <Collapsible
                                key={item.title}
                                asChild
                                defaultOpen={isItemActive(item, page.url)}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            tooltip={{
                                                children: item.title,
                                            }}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <NavSubMenu items={item.items!} />
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    }

                    // Si no tiene hijos, renderizar como item normal
                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
