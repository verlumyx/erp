export interface Company {
  id: string;
  name: string;
  address: string;
  email?: string;
  phone?: string;
  status: 'active' | 'inactive';
  description?: string;
  created_at: string;
  updated_at: string | null;
}

export interface CompanyFormData {
  name: string;
  address: string;
  email?: string;
  phone?: string;
  description?: string;
  status?: 'active' | 'inactive';
}

export interface CompanySearchFilters {
  name?: string;
  status?: 'active' | 'inactive';
  address?: string;
  email?: string;
  phone?: string;
  limit?: number;
  offset?: number;
}

export interface CompanySearchResponse {
  companies: Company[];
  total: number;
  limit: number;
  offset: number;
}

export interface CompanyValidationErrors {
  name?: string[];
  address?: string[];
  email?: string[];
  phone?: string[];
  description?: string[];
  status?: string[];
}

