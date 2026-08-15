import React, { createContext, useContext, ReactNode } from 'react';

interface RoleFormData {
    name: string;
    description: string;
    permission_type: 'all' | 'custom';
    permissions: string[];
}

interface RoleFormContextType {
    data: RoleFormData;
    setData: (key: keyof RoleFormData, value: any) => void;
    errors: Partial<Record<keyof RoleFormData, string>>;
    processing: boolean;
    handleSubmit: (e: React.FormEvent) => void;
    reset: () => void;
}

const RoleFormContext = createContext<RoleFormContextType | undefined>(undefined);

interface RoleFormProviderProps {
    children: ReactNode;
    value: RoleFormContextType;
}

export function RoleFormProvider({ children, value }: RoleFormProviderProps) {
    return (
        <RoleFormContext.Provider value={value}>
            {children}
        </RoleFormContext.Provider>
    );
}

export function useRoleFormContext() {
    const context = useContext(RoleFormContext);
    if (!context) {
        throw new Error('useRoleFormContext must be used within RoleFormProvider');
    }
    return context;
}
