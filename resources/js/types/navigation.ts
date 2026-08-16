import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: string;
};

export type NavItem = {
    title: string;
    /** Un grupo padre (p. ej. "Catálogo") no tiene URL propia: usa ''. */
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Hijos del grupo; ausente o vacío en un item normal. */
    items?: NavItem[];
};
