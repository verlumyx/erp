import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSalesOrderForm } from '../hooks/useSalesOrderForm';
import type { SalesOrderOptions } from '../types/SalesOrder';

type SalesOrderFormContextType = ReturnType<typeof useSalesOrderForm> & {
    options: SalesOrderOptions;
};

const SalesOrderFormContext = createContext<
    SalesOrderFormContextType | undefined
>(undefined);

interface SalesOrderFormProviderProps {
    children: ReactNode;
    value: SalesOrderFormContextType;
}

export function SalesOrderFormProvider({
    children,
    value,
}: SalesOrderFormProviderProps) {
    return (
        <SalesOrderFormContext.Provider value={value}>
            {children}
        </SalesOrderFormContext.Provider>
    );
}

export function useSalesOrderFormContext(): SalesOrderFormContextType {
    const context = useContext(SalesOrderFormContext);
    if (!context) {
        throw new Error(
            'useSalesOrderFormContext must be used within SalesOrderFormProvider',
        );
    }
    return context;
}
