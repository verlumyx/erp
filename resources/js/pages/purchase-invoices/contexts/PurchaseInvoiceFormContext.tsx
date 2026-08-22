import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePurchaseInvoiceForm } from '../hooks/usePurchaseInvoiceForm';
import type { PurchaseInvoiceOptions } from '../types/PurchaseInvoice';

type PurchaseInvoiceFormContextType = ReturnType<
    typeof usePurchaseInvoiceForm
> & {
    options: PurchaseInvoiceOptions;
};

const PurchaseInvoiceFormContext = createContext<
    PurchaseInvoiceFormContextType | undefined
>(undefined);

interface PurchaseInvoiceFormProviderProps {
    children: ReactNode;
    value: PurchaseInvoiceFormContextType;
}

export function PurchaseInvoiceFormProvider({
    children,
    value,
}: PurchaseInvoiceFormProviderProps) {
    return (
        <PurchaseInvoiceFormContext.Provider value={value}>
            {children}
        </PurchaseInvoiceFormContext.Provider>
    );
}

export function usePurchaseInvoiceFormContext(): PurchaseInvoiceFormContextType {
    const context = useContext(PurchaseInvoiceFormContext);
    if (!context) {
        throw new Error(
            'usePurchaseInvoiceFormContext must be used within PurchaseInvoiceFormProvider',
        );
    }
    return context;
}
