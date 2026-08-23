import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePurchaseReturnForm } from '../hooks/usePurchaseReturnForm';
import type { PurchaseReturnOptions } from '../types/PurchaseReturn';

type PurchaseReturnFormContextType = ReturnType<
    typeof usePurchaseReturnForm
> & {
    options: PurchaseReturnOptions;
};

const PurchaseReturnFormContext = createContext<
    PurchaseReturnFormContextType | undefined
>(undefined);

interface PurchaseReturnFormProviderProps {
    children: ReactNode;
    value: PurchaseReturnFormContextType;
}

export function PurchaseReturnFormProvider({
    children,
    value,
}: PurchaseReturnFormProviderProps) {
    return (
        <PurchaseReturnFormContext.Provider value={value}>
            {children}
        </PurchaseReturnFormContext.Provider>
    );
}

export function usePurchaseReturnFormContext(): PurchaseReturnFormContextType {
    const context = useContext(PurchaseReturnFormContext);
    if (!context) {
        throw new Error(
            'usePurchaseReturnFormContext must be used within PurchaseReturnFormProvider',
        );
    }
    return context;
}
