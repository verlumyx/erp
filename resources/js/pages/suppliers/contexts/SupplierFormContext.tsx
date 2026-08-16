import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSupplierForm } from '../hooks/useSupplierForm';
import type { SupplierOptions } from '../types/Supplier';

type SupplierFormContextType = ReturnType<typeof useSupplierForm> & {
    options: SupplierOptions;
};

const SupplierFormContext = createContext<SupplierFormContextType | undefined>(
    undefined,
);

interface SupplierFormProviderProps {
    children: ReactNode;
    value: SupplierFormContextType;
}

export function SupplierFormProvider({
    children,
    value,
}: SupplierFormProviderProps) {
    return (
        <SupplierFormContext.Provider value={value}>
            {children}
        </SupplierFormContext.Provider>
    );
}

export function useSupplierFormContext(): SupplierFormContextType {
    const context = useContext(SupplierFormContext);
    if (!context) {
        throw new Error(
            'useSupplierFormContext must be used within SupplierFormProvider',
        );
    }
    return context;
}
