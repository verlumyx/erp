export interface Company {
    id: string;
    name: string;
    rif?: string | null;
    status: 'active' | 'inactive';
    address?: string | null;
    fiscal_address?: string | null;
    phone?: string | null;
    taxpayer_type?: 'ordinary' | 'formal' | 'special';
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
