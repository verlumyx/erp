import type { Auth } from './auth';
import type { NavItem } from './navigation';

export type * from './auth';
export type * from './configuration';
export type * from './currency';
export type * from './navigation';
export type * from './tax';
export type * from './ui';

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

