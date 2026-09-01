import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSalesReturnForm } from '../hooks/useSalesReturnForm';
import type { SalesReturnOptions } from '../types/SalesReturn';

type SalesReturnFormContextType = ReturnType<typeof useSalesReturnForm> & {
    options: SalesReturnOptions;
};

const SalesReturnFormContext = createContext<
    SalesReturnFormContextType | undefined
>(undefined);

interface SalesReturnFormProviderProps {
    children: ReactNode;
    value: SalesReturnFormContextType;
}

export function SalesReturnFormProvider({
    children,
    value,
}: SalesReturnFormProviderProps) {
    return (
        <SalesReturnFormContext.Provider value={value}>
            {children}
        </SalesReturnFormContext.Provider>
    );
}

export function useSalesReturnFormContext(): SalesReturnFormContextType {
    const context = useContext(SalesReturnFormContext);
    if (!context) {
        throw new Error(
            'useSalesReturnFormContext must be used within SalesReturnFormProvider',
        );
    }
    return context;
}
