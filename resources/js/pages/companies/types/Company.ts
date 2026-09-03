export interface Company {
    id: string;
    name: string;
    status: 'active' | 'inactive';
    description: string | null;
    created_at: string;
    updated_at: string | null;
}

export interface CompanyMeta {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
}

export interface CompanyFilters {
    name?: string;
    status?: string;
    limit?: number;
    offset?: number;
}

/** Un nodo del árbol de menús tal como lo arma el backend. Sin `url` es un grupo. */
export interface CompanyMenuNode {
    id: string;
    title: string;
    icon: string | null;
    url: string | null;
    permission: string | null;
    children: CompanyMenuNode[];
}

export interface CompanyMenus {
    mainNavItems: CompanyMenuNode[];
    footerNavItems: CompanyMenuNode[];
}
