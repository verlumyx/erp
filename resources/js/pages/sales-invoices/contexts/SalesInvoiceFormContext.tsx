import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSalesInvoiceForm } from '../hooks/useSalesInvoiceForm';
import type { SalesInvoiceOptions } from '../types/SalesInvoice';

type SalesInvoiceFormContextType = ReturnType<typeof useSalesInvoiceForm> & {
    options: SalesInvoiceOptions;
};

const SalesInvoiceFormContext = createContext<
    SalesInvoiceFormContextType | undefined
>(undefined);

interface SalesInvoiceFormProviderProps {
    children: ReactNode;
    value: SalesInvoiceFormContextType;
}

export function SalesInvoiceFormProvider({
    children,
    value,
}: SalesInvoiceFormProviderProps) {
    return (
        <SalesInvoiceFormContext.Provider value={value}>
            {children}
        </SalesInvoiceFormContext.Provider>
    );
}

export function useSalesInvoiceFormContext(): SalesInvoiceFormContextType {
    const context = useContext(SalesInvoiceFormContext);
    if (!context) {
        throw new Error(
            'useSalesInvoiceFormContext must be used within SalesInvoiceFormProvider',
        );
    }
    return context;
}
