import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePurchaseOrderForm } from '../hooks/usePurchaseOrderForm';
import type { PurchaseOrderOptions } from '../types/PurchaseOrder';

type PurchaseOrderFormContextType = ReturnType<typeof usePurchaseOrderForm> & {
    options: PurchaseOrderOptions;
};

const PurchaseOrderFormContext = createContext<
    PurchaseOrderFormContextType | undefined
>(undefined);

interface PurchaseOrderFormProviderProps {
    children: ReactNode;
    value: PurchaseOrderFormContextType;
}

export function PurchaseOrderFormProvider({
    children,
    value,
}: PurchaseOrderFormProviderProps) {
    return (
        <PurchaseOrderFormContext.Provider value={value}>
            {children}
        </PurchaseOrderFormContext.Provider>
    );
}

export function usePurchaseOrderFormContext(): PurchaseOrderFormContextType {
    const context = useContext(PurchaseOrderFormContext);
    if (!context) {
        throw new Error(
            'usePurchaseOrderFormContext must be used within PurchaseOrderFormProvider',
        );
    }
    return context;
}
