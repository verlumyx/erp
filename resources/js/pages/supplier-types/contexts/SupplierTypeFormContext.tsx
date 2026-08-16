import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSupplierTypeForm } from '../hooks/useSupplierTypeForm';

type SupplierTypeFormContextType = ReturnType<typeof useSupplierTypeForm>;

const SupplierTypeFormContext = createContext<
    SupplierTypeFormContextType | undefined
>(undefined);

interface SupplierTypeFormProviderProps {
    children: ReactNode;
    value: SupplierTypeFormContextType;
}

export function SupplierTypeFormProvider({
    children,
    value,
}: SupplierTypeFormProviderProps) {
    return (
        <SupplierTypeFormContext.Provider value={value}>
            {children}
        </SupplierTypeFormContext.Provider>
    );
}

export function useSupplierTypeFormContext(): SupplierTypeFormContextType {
    const context = useContext(SupplierTypeFormContext);
    if (!context) {
        throw new Error(
            'useSupplierTypeFormContext must be used within SupplierTypeFormProvider',
        );
    }
    return context;
}
