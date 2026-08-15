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

export function AppSidebar() {
    const { menus } = usePage().props as any;

    // Convertir menús del backend al formato del frontend
    const mainNavItems: NavItem[] = (menus?.mainNavItems || []).map((item: any) => ({
        title: item.title,
        href: item.url,
        icon: getIconComponent(item.icon),
        items: item.children?.map((child: any) => ({
            title: child.title,
            href: child.url,
            icon: getIconComponent(child.icon),
        })),
    }));

    const footerNavItems: NavItem[] = (menus?.footerNavItems || []).map((item: any) => ({
        title: item.title,
        href: item.url,
        icon: getIconComponent(item.icon),
        items: item.children?.map((child: any) => ({
            title: child.title,
            href: child.url,
            icon: getIconComponent(child.icon),
        })),
    }));
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
