import { ChevronDown, ChevronRight, FileText, Folder, FolderOpen } from 'lucide-react';
import { useState } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { CompanyMenuNode } from '../types/Company';

interface CompanyMenusTreeProps {
    nodes: CompanyMenuNode[];
    /** Ids de las hojas que la empresa NO ve. */
    disabledIds: string[];
    onChange: (disabledIds: string[]) => void;
    disabled?: boolean;
}

/** Una hoja es un menú con URL propia; un grupo solo agrupa a sus hijos. */
function isLeaf(node: CompanyMenuNode): boolean {
    return node.url !== null && node.url !== '';
}

/** Las hojas del subárbol, en cualquier nivel: los grupos futuros pueden anidarse más. */
export function collectLeafIds(nodes: CompanyMenuNode[]): string[] {
    return nodes.flatMap((node) => [
        ...(isLeaf(node) ? [node.id] : []),
        ...collectLeafIds(node.children),
    ]);
}

/**
 * Árbol de menús con casillas: marcado = la empresa lo ve. Se controla desde
 * fuera con la lista de deshabilitados, que es lo que guarda el backend.
 */
export function CompanyMenusTree({ nodes, disabledIds, onChange, disabled = false }: CompanyMenusTreeProps) {
    const [expanded, setExpanded] = useState<string[]>([]);

    const isEnabled = (id: string) => !disabledIds.includes(id);

    const toggleExpanded = (id: string) => {
        setExpanded((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]));
    };

    const toggleLeaf = (id: string) => {
        if (disabled) return;

        onChange(isEnabled(id) ? [...disabledIds, id] : disabledIds.filter((item) => item !== id));
    };

    /** Marca o desmarca de golpe todas las hojas de un grupo. */
    const toggleGroup = (group: CompanyMenuNode) => {
        if (disabled) return;

        const leafIds = collectLeafIds(group.children);
        const allEnabled = leafIds.every(isEnabled);

        onChange(
            allEnabled
                ? [...new Set([...disabledIds, ...leafIds])]
                : disabledIds.filter((id) => !leafIds.includes(id)),
        );
    };

    return (
        <div className="space-y-1">
            {nodes.map((node) =>
                isLeaf(node) ? (
                    <LeafRow
                        key={node.id}
                        node={node}
                        checked={isEnabled(node.id)}
                        onToggle={() => toggleLeaf(node.id)}
                        disabled={disabled}
                        indent={false}
                    />
                ) : (
                    <GroupNode
                        key={node.id}
                        node={node}
                        expanded={expanded.includes(node.id)}
                        onToggleExpanded={() => toggleExpanded(node.id)}
                        onToggleGroup={() => toggleGroup(node)}
                        isEnabled={isEnabled}
                        onToggleLeaf={toggleLeaf}
                        disabled={disabled}
                    />
                ),
            )}
        </div>
    );
}

interface GroupNodeProps {
    node: CompanyMenuNode;
    expanded: boolean;
    onToggleExpanded: () => void;
    onToggleGroup: () => void;
    isEnabled: (id: string) => boolean;
    onToggleLeaf: (id: string) => void;
    disabled: boolean;
}

function GroupNode({ node, expanded, onToggleExpanded, onToggleGroup, isEnabled, onToggleLeaf, disabled }: GroupNodeProps) {
    const leafIds = collectLeafIds(node.children);
    const enabledCount = leafIds.filter(isEnabled).length;
    const isFullyEnabled = leafIds.length > 0 && enabledCount === leafIds.length;
    const isPartiallyEnabled = enabledCount > 0 && enabledCount < leafIds.length;

    return (
        <div className="space-y-1">
            <div className="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-accent">
                <button
                    type="button"
                    onClick={onToggleExpanded}
                    className="rounded p-0.5 hover:bg-accent-foreground/10"
                    aria-expanded={expanded}
                    aria-label={`${expanded ? 'Ocultar' : 'Mostrar'} los menús de ${node.title}`}
                >
                    {expanded ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                </button>

                <div className="flex flex-1 items-center gap-2">
                    {expanded ? (
                        <FolderOpen className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <Folder className="h-4 w-4 text-muted-foreground" />
                    )}

                    <Checkbox
                        checked={isFullyEnabled || isPartiallyEnabled}
                        onCheckedChange={onToggleGroup}
                        disabled={disabled}
                        aria-label={`Habilitar todo ${node.title}`}
                        className={cn(
                            isPartiallyEnabled &&
                                'data-[state=checked]:border-primary/40 data-[state=checked]:bg-primary/40',
                        )}
                    />

                    <span className="text-sm font-semibold">{node.title}</span>

                    {/* Sin esto un grupo cerrado escondería cuántos menús siguen habilitados */}
                    {!expanded && (
                        <span className="rounded-full bg-primary/10 px-1.5 py-0.5 text-[11px] font-semibold text-primary tabular-nums">
                            {enabledCount}/{leafIds.length}
                        </span>
                    )}
                </div>
            </div>

            {expanded && (
                <div className="ml-6 space-y-1">
                    {node.children.map((child) =>
                        isLeaf(child) ? (
                            <LeafRow
                                key={child.id}
                                node={child}
                                checked={isEnabled(child.id)}
                                onToggle={() => onToggleLeaf(child.id)}
                                disabled={disabled}
                                indent
                            />
                        ) : (
                            <GroupNode
                                key={child.id}
                                node={child}
                                expanded={false}
                                onToggleExpanded={() => undefined}
                                onToggleGroup={() => undefined}
                                isEnabled={isEnabled}
                                onToggleLeaf={onToggleLeaf}
                                disabled={disabled}
                            />
                        ),
                    )}
                </div>
            )}
        </div>
    );
}

interface LeafRowProps {
    node: CompanyMenuNode;
    checked: boolean;
    onToggle: () => void;
    disabled: boolean;
    indent: boolean;
}

function LeafRow({ node, checked, onToggle, disabled, indent }: LeafRowProps) {
    return (
        <div className="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-accent">
            {indent && <div className="w-4" />}
            <FileText className="h-4 w-4 text-muted-foreground" />
            <Checkbox
                id={`menu-${node.id}`}
                checked={checked}
                onCheckedChange={onToggle}
                disabled={disabled}
            />
            <label htmlFor={`menu-${node.id}`} className="text-sm cursor-pointer">
                {node.title}
            </label>
            {node.url && <span className="text-xs text-muted-foreground">{node.url}</span>}
        </div>
    );
}
