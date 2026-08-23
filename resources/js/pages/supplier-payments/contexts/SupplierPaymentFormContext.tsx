import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSupplierPaymentForm } from '../hooks/useSupplierPaymentForm';

type SupplierPaymentFormContextType = ReturnType<typeof useSupplierPaymentForm>;

const SupplierPaymentFormContext = createContext<
    SupplierPaymentFormContextType | undefined
>(undefined);

interface SupplierPaymentFormProviderProps {
    children: ReactNode;
    value: SupplierPaymentFormContextType;
}

export function SupplierPaymentFormProvider({
    children,
    value,
}: SupplierPaymentFormProviderProps) {
    return (
        <SupplierPaymentFormContext.Provider value={value}>
            {children}
        </SupplierPaymentFormContext.Provider>
    );
}

export function useSupplierPaymentFormContext(): SupplierPaymentFormContextType {
    const context = useContext(SupplierPaymentFormContext);
    if (!context) {
        throw new Error(
            'useSupplierPaymentFormContext must be used within SupplierPaymentFormProvider',
        );
    }
    return context;
}
