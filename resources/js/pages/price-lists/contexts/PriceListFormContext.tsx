import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePriceListForm } from '../hooks/usePriceListForm';

type PriceListFormContextType = ReturnType<typeof usePriceListForm>;

const PriceListFormContext = createContext<
    PriceListFormContextType | undefined
>(undefined);

interface PriceListFormProviderProps {
    children: ReactNode;
    value: PriceListFormContextType;
}

export function PriceListFormProvider({
    children,
    value,
}: PriceListFormProviderProps) {
    return (
        <PriceListFormContext.Provider value={value}>
            {children}
        </PriceListFormContext.Provider>
    );
}

export function usePriceListFormContext(): PriceListFormContextType {
    const context = useContext(PriceListFormContext);
    if (!context) {
        throw new Error(
            'usePriceListFormContext must be used within PriceListFormProvider',
        );
    }
    return context;
}
