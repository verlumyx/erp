import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import * as LucideIcons from 'lucide-react';
import AppLogo from './app-logo';
import { usePage } from '@inertiajs/react';

// Helper para obtener el componente de icono dinámicamente
const getIconComponent = (iconName: string | null) => {
    if (!iconName) return undefined;
    return (LucideIcons as any)[iconName];
};

// Convierte un menú del backend al formato del frontend. Un grupo padre
// (p. ej. "Catálogo") llega con url null: se mapea a '' porque no navega,
// solo despliega a sus hijos.
const toNavItem = (item: any): NavItem => ({
    title: item.title,
    href: item.url ?? '',
    icon: getIconComponent(item.icon),
    items: item.children?.map(toNavItem),
});

export function AppSidebar() {
    const { menus } = usePage().props as any;

    const mainNavItems: NavItem[] = (menus?.mainNavItems || []).map(toNavItem);
    const footerNavItems: NavItem[] = (menus?.footerNavItems || []).map(
        toNavItem,
    );
    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
