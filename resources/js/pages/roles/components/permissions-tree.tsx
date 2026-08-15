import React, { useState, useEffect } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { ChevronRight, ChevronDown, Folder, FolderOpen, FileText } from 'lucide-react';
import { usePage } from '@inertiajs/react';

interface Permission {
    id: string;
    action: string;
    label: string;
}

interface Module {
    id: string;
    name: string;
    label: string;
    icon?: string;
    permissions: Permission[];
}

interface PermissionsTreeProps {
    selectedPermissions?: string[];
    onPermissionsChange?: (permissions: string[]) => void;
    disabled?: boolean;
}

export function PermissionsTree({
    selectedPermissions = [],
    onPermissionsChange,
    disabled = false
}: PermissionsTreeProps) {
    const { permissions } = usePage().props as any;
    const modules: Module[] = permissions?.modules || [];
    
    const [expandedModules, setExpandedModules] = useState<string[]>([]);
    const [selected, setSelected] = useState<string[]>(selectedPermissions);

    // Sincronizar el estado local con las props cuando cambian
    useEffect(() => {
        setSelected(selectedPermissions);
    }, [selectedPermissions]);

    const toggleModule = (moduleId: string) => {
        setExpandedModules(prev =>
            prev.includes(moduleId)
                ? prev.filter(id => id !== moduleId)
                : [...prev, moduleId]
        );
    };

    const isModuleExpanded = (moduleId: string) => expandedModules.includes(moduleId);

    const isPermissionSelected = (permissionAction: string) => selected.includes(permissionAction);

    const isModuleFullySelected = (module: Module) => {
        return module.permissions.every(p => selected.includes(p.action));
    };

    const isModulePartiallySelected = (module: Module) => {
        const selectedCount = module.permissions.filter(p => selected.includes(p.action)).length;
        return selectedCount > 0 && selectedCount < module.permissions.length;
    };

    const togglePermission = (permissionAction: string) => {
        if (disabled) return;

        const newSelected = selected.includes(permissionAction)
            ? selected.filter(action => action !== permissionAction)
            : [...selected, permissionAction];

        setSelected(newSelected);
        onPermissionsChange?.(newSelected);
    };

    const toggleModulePermissions = (module: Module) => {
        if (disabled) return;

        const allModulePermissions = module.permissions.map(p => p.action);
        const isFullySelected = isModuleFullySelected(module);

        const newSelected = isFullySelected
            ? selected.filter(id => !allModulePermissions.includes(id))
            : [...new Set([...selected, ...allModulePermissions])];

        setSelected(newSelected);
        onPermissionsChange?.(newSelected);
    };

    return (
        <div className="space-y-1">
            {modules.map((module) => {
                const isExpanded = isModuleExpanded(module.id);
                const isFullySelected = isModuleFullySelected(module);
                const isPartiallySelected = isModulePartiallySelected(module);

                return (
                    <div key={module.id} className="space-y-1">
                        {/* Module Header */}
                        <div className="flex items-center gap-2 py-1 hover:bg-accent rounded-md px-2">
                            <button
                                type="button"
                                onClick={() => toggleModule(module.id)}
                                className="p-0.5 hover:bg-accent-foreground/10 rounded"
                                disabled={disabled}
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4" />
                                ) : (
                                    <ChevronRight className="h-4 w-4" />
                                )}
                            </button>

                            <div className="flex items-center gap-2 flex-1">
                                {isExpanded ? (
                                    <FolderOpen className="h-4 w-4 text-muted-foreground" />
                                ) : (
                                    <Folder className="h-4 w-4 text-muted-foreground" />
                                )}

                                <Checkbox
                                    checked={isFullySelected}
                                    onCheckedChange={() => toggleModulePermissions(module)}
                                    disabled={disabled}
                                    className={isPartiallySelected ? 'data-[state=checked]:bg-primary/50' : ''}
                                />

                                <span className="text-sm font-medium">{module.label}</span>
                            </div>
                        </div>

                        {/* Module Permissions */}
                        {isExpanded && (
                            <div className="ml-6 space-y-1">
                                {module.permissions.map((permission) => (
                                    <div
                                        key={permission.id}
                                        className="flex items-center gap-2 py-1 hover:bg-accent rounded-md px-2"
                                    >
                                        <div className="w-4" /> {/* Spacer for alignment */}
                                        
                                        <FileText className="h-4 w-4 text-muted-foreground" />

                                        <Checkbox
                                            checked={isPermissionSelected(permission.action)}
                                            onCheckedChange={() => togglePermission(permission.action)}
                                            disabled={disabled}
                                        />

                                        <span className="text-sm">{permission.label}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}
