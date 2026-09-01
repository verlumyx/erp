import { usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronRight,
    FileText,
    Folder,
    FolderOpen,
    Layers,
} from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

interface Permission {
    id: string;
    action: string;
    label: string;
}

interface ModuleGroup {
    title: string;
    icon?: string | null;
}

interface Module {
    id: string;
    name: string;
    label: string;
    icon?: string;
    group?: ModuleGroup | null;
    permissions: Permission[];
}

/**
 * Los módulos vienen ordenados como el sidebar, así que un grupo son los
 * módulos consecutivos que comparten título. Los que no traen grupo —los del
 * pie de página— se muestran sueltos, igual que antes.
 */
interface TreeGroup {
    title: string | null;
    modules: Module[];
}

function buildGroups(modules: Module[]): TreeGroup[] {
    return modules.reduce<TreeGroup[]>((groups, module) => {
        const title = module.group?.title ?? null;
        const current = groups[groups.length - 1];

        if (current && current.title === title && title !== null) {
            current.modules.push(module);

            return groups;
        }

        return [...groups, { title, modules: [module] }];
    }, []);
}

interface PermissionsTreeProps {
    selectedPermissions?: string[];
    onPermissionsChange?: (permissions: string[]) => void;
    disabled?: boolean;
}

export function PermissionsTree({
    selectedPermissions = [],
    onPermissionsChange,
    disabled = false,
}: PermissionsTreeProps) {
    const { permissions } = usePage().props as any;
    const modules: Module[] = permissions?.modules || [];
    const groups = buildGroups(modules);

    const [expandedGroups, setExpandedGroups] = useState<string[]>([]);
    const [expandedModules, setExpandedModules] = useState<string[]>([]);
    const [selected, setSelected] = useState<string[]>(selectedPermissions);

    // Sincronizar el estado local con las props cuando cambian
    useEffect(() => {
        setSelected(selectedPermissions);
    }, [selectedPermissions]);

    const toggleGroup = (title: string) => {
        setExpandedGroups((prev) =>
            prev.includes(title)
                ? prev.filter((item) => item !== title)
                : [...prev, title],
        );
    };

    const toggleModule = (moduleId: string) => {
        setExpandedModules((prev) =>
            prev.includes(moduleId)
                ? prev.filter((id) => id !== moduleId)
                : [...prev, moduleId],
        );
    };

    const isGroupExpanded = (title: string) => expandedGroups.includes(title);

    const isModuleExpanded = (moduleId: string) =>
        expandedModules.includes(moduleId);

    const isPermissionSelected = (permissionAction: string) =>
        selected.includes(permissionAction);

    const actionsOf = (modulesToRead: Module[]) =>
        modulesToRead.flatMap((module) =>
            module.permissions.map((permission) => permission.action),
        );

    const selectedCountOf = (modulesToRead: Module[]) =>
        actionsOf(modulesToRead).filter((action) => selected.includes(action))
            .length;

    const applySelection = (newSelected: string[]) => {
        setSelected(newSelected);
        onPermissionsChange?.(newSelected);
    };

    const togglePermission = (permissionAction: string) => {
        if (disabled) return;

        applySelection(
            selected.includes(permissionAction)
                ? selected.filter((action) => action !== permissionAction)
                : [...selected, permissionAction],
        );
    };

    /** Marca o desmarca de golpe todos los permisos de un módulo o de un grupo. */
    const toggleAll = (modulesToToggle: Module[]) => {
        if (disabled) return;

        const actions = actionsOf(modulesToToggle);
        const isFullySelected = actions.every((action) =>
            selected.includes(action),
        );

        applySelection(
            isFullySelected
                ? selected.filter((action) => !actions.includes(action))
                : [...new Set([...selected, ...actions])],
        );
    };

    return (
        <div className="space-y-1">
            {groups.map((group) => {
                if (group.title === null) {
                    return group.modules.map((module) => (
                        <ModuleNode
                            key={module.id}
                            module={module}
                            expanded={isModuleExpanded(module.id)}
                            onToggleExpanded={() => toggleModule(module.id)}
                            selectedCount={selectedCountOf([module])}
                            onToggleAll={() => toggleAll([module])}
                            isPermissionSelected={isPermissionSelected}
                            onTogglePermission={togglePermission}
                            disabled={disabled}
                        />
                    ));
                }

                const isExpanded = isGroupExpanded(group.title);
                const total = actionsOf(group.modules).length;
                const selectedCount = selectedCountOf(group.modules);

                return (
                    <div key={group.title} className="space-y-1">
                        {/* Group Header */}
                        <div className="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-accent">
                            <button
                                type="button"
                                onClick={() => toggleGroup(group.title!)}
                                className="rounded p-0.5 hover:bg-accent-foreground/10"
                                disabled={disabled}
                                aria-expanded={isExpanded}
                                aria-label={`${isExpanded ? 'Ocultar' : 'Mostrar'} los módulos de ${group.title}`}
                            >
                                {isExpanded ? (
                                    <ChevronDown className="h-4 w-4" />
                                ) : (
                                    <ChevronRight className="h-4 w-4" />
                                )}
                            </button>

                            <div className="flex flex-1 items-center gap-2">
                                <Layers className="h-4 w-4 text-muted-foreground" />

                                <Checkbox
                                    checked={selectedCount > 0}
                                    onCheckedChange={() =>
                                        toggleAll(group.modules)
                                    }
                                    disabled={disabled}
                                    className={cn(
                                        selectedCount > 0 &&
                                            selectedCount < total &&
                                            'data-[state=checked]:border-primary/40 data-[state=checked]:bg-primary/40',
                                    )}
                                />

                                <span className="text-sm font-semibold">
                                    {group.title}
                                </span>

                                {/* Sin esto un grupo cerrado escondería lo ya marcado */}
                                {selectedCount > 0 && !isExpanded && (
                                    <span className="rounded-full bg-primary/10 px-1.5 py-0.5 text-[11px] font-semibold text-primary tabular-nums">
                                        {selectedCount}
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* Group Modules */}
                        {isExpanded && (
                            <div className="ml-6 space-y-1">
                                {group.modules.map((module) => (
                                    <ModuleNode
                                        key={module.id}
                                        module={module}
                                        expanded={isModuleExpanded(module.id)}
                                        onToggleExpanded={() =>
                                            toggleModule(module.id)
                                        }
                                        selectedCount={selectedCountOf([
                                            module,
                                        ])}
                                        onToggleAll={() => toggleAll([module])}
                                        isPermissionSelected={
                                            isPermissionSelected
                                        }
                                        onTogglePermission={togglePermission}
                                        disabled={disabled}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
}

interface ModuleNodeProps {
    module: Module;
    expanded: boolean;
    onToggleExpanded: () => void;
    selectedCount: number;
    onToggleAll: () => void;
    isPermissionSelected: (action: string) => boolean;
    onTogglePermission: (action: string) => void;
    disabled: boolean;
}

function ModuleNode({
    module,
    expanded,
    onToggleExpanded,
    selectedCount,
    onToggleAll,
    isPermissionSelected,
    onTogglePermission,
    disabled,
}: ModuleNodeProps) {
    const isFullySelected =
        module.permissions.length > 0 &&
        selectedCount === module.permissions.length;
    const isPartiallySelected =
        selectedCount > 0 && selectedCount < module.permissions.length;

    return (
        <div className="space-y-1">
            {/* Module Header */}
            <div className="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-accent">
                <button
                    type="button"
                    onClick={onToggleExpanded}
                    className="rounded p-0.5 hover:bg-accent-foreground/10"
                    disabled={disabled}
                    aria-expanded={expanded}
                    aria-label={`${expanded ? 'Ocultar' : 'Mostrar'} los permisos de ${module.label}`}
                >
                    {expanded ? (
                        <ChevronDown className="h-4 w-4" />
                    ) : (
                        <ChevronRight className="h-4 w-4" />
                    )}
                </button>

                <div className="flex flex-1 items-center gap-2">
                    {expanded ? (
                        <FolderOpen className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <Folder className="h-4 w-4 text-muted-foreground" />
                    )}

                    <Checkbox
                        checked={isFullySelected || isPartiallySelected}
                        onCheckedChange={onToggleAll}
                        disabled={disabled}
                        className={cn(
                            isPartiallySelected &&
                                'data-[state=checked]:border-primary/40 data-[state=checked]:bg-primary/40',
                        )}
                    />

                    <span className="text-sm font-medium">{module.label}</span>
                </div>
            </div>

            {/* Module Permissions */}
            {expanded && (
                <div className="ml-6 space-y-1">
                    {module.permissions.map((permission) => (
                        <div
                            key={permission.id}
                            className="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-accent"
                        >
                            <div className="w-4" /> {/* Spacer for alignment */}
                            <FileText className="h-4 w-4 text-muted-foreground" />
                            <Checkbox
                                checked={isPermissionSelected(
                                    permission.action,
                                )}
                                onCheckedChange={() =>
                                    onTogglePermission(permission.action)
                                }
                                disabled={disabled}
                            />
                            <span className="text-sm">{permission.label}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
