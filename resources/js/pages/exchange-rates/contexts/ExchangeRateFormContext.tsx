import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useExchangeRateForm } from '../hooks/useExchangeRateForm';

type ExchangeRateFormContextType = ReturnType<typeof useExchangeRateForm>;

const ExchangeRateFormContext = createContext<
    ExchangeRateFormContextType | undefined
>(undefined);

interface ExchangeRateFormProviderProps {
    children: ReactNode;
    value: ExchangeRateFormContextType;
}

export function ExchangeRateFormProvider({
    children,
    value,
}: ExchangeRateFormProviderProps) {
    return (
        <ExchangeRateFormContext.Provider value={value}>
            {children}
        </ExchangeRateFormContext.Provider>
    );
}

export function useExchangeRateFormContext(): ExchangeRateFormContextType {
    const context = useContext(ExchangeRateFormContext);
    if (!context) {
        throw new Error(
            'useExchangeRateFormContext must be used within ExchangeRateFormProvider',
        );
    }
    return context;
}
