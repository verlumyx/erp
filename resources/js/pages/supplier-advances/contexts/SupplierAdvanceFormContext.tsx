import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSupplierAdvanceForm } from '../hooks/useSupplierAdvanceForm';

type SupplierAdvanceFormContextType = ReturnType<typeof useSupplierAdvanceForm>;

const SupplierAdvanceFormContext = createContext<
    SupplierAdvanceFormContextType | undefined
>(undefined);

interface SupplierAdvanceFormProviderProps {
    children: ReactNode;
    value: SupplierAdvanceFormContextType;
}

export function SupplierAdvanceFormProvider({
    children,
    value,
}: SupplierAdvanceFormProviderProps) {
    return (
        <SupplierAdvanceFormContext.Provider value={value}>
            {children}
        </SupplierAdvanceFormContext.Provider>
    );
}

export function useSupplierAdvanceFormContext(): SupplierAdvanceFormContextType {
    const context = useContext(SupplierAdvanceFormContext);
    if (!context) {
        throw new Error(
            'useSupplierAdvanceFormContext must be used within SupplierAdvanceFormProvider',
        );
    }
    return context;
}
