export interface Role {
  id: string;
  name: string;
  status: 'active' | 'inactive';
  description: string;
  created_at: string;
  updated_at: string | null;
}

export interface RoleFormData {
  name: string;
  description?: string;
  status?: 'active' | 'inactive';
}

export interface RoleSearchFilters {
  name?: string;
  status?: 'active' | 'inactive';
  description?: string;
  limit?: number;
  offset?: number;
}

export interface RoleSearchResponse {
  roles: Role[];
  total: number;
  limit: number;
  offset: number;
}

export interface RoleValidationErrors {
  name?: string[];
  description?: string[];
  status?: string[];
}
