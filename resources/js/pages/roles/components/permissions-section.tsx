import React from 'react';
import { useRoleFormContext } from '../contexts/RoleFormContext';
import { PermissionsTree } from './permissions-tree';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Info } from 'lucide-react';
import { usePage } from '@inertiajs/react';

export function PermissionsSection() {
    const { data, setData } = useRoleFormContext();
    const { permissions } = usePage().props as any;
    const allPermissions: string[] = permissions?.all || [];

    const handlePermissionsChange = (permissions: string[]) => {
        setData('permissions', permissions);
    };

    const isDisabled = data.permission_type === 'all';

    return (
        <div className="space-y-4">
            {data.permission_type === 'all' && (
                <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                        Este rol tiene acceso a <strong>todos los permisos</strong>.
                        Cambia el tipo de permisos a "Personalizados" para seleccionar permisos específicos.
                    </AlertDescription>
                </Alert>
            )}

            {data.permission_type === 'custom' && (
                <>
                    <div className="flex items-center justify-between mb-4">
                        <div className="text-sm text-muted-foreground">
                            Selecciona los permisos específicos para este rol
                        </div>
                    </div>

                    <div className={isDisabled ? 'opacity-50 pointer-events-none' : ''}>
                        <PermissionsTree
                            selectedPermissions={data.permissions}
                            onPermissionsChange={handlePermissionsChange}
                            disabled={isDisabled}
                        />
                    </div>
                </>
            )}
        </div>
    );
}
