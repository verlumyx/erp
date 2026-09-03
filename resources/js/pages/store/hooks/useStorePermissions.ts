import { usePage } from '@inertiajs/react';

interface PageProps {
    auth?: { permissions?: string[] };
    [key: string]: unknown;
}

/**
 * Permisos del usuario en la empresa activa, tal como los comparte
 * `HandleInertiaRequests` (`auth.permissions`). Un rol con acceso total
 * trae todos los permisos resueltos, así que la comprobación es la misma.
 */
export function useStorePermissions() {
    const { auth } = usePage<PageProps>().props;
    const permissions = auth?.permissions ?? [];

    const can = (permission: string): boolean =>
        permissions.includes(permission);

    return { can };
}
