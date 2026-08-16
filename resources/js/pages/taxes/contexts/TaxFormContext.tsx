import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useTaxForm } from '../hooks/useTaxForm';

type TaxFormContextType = ReturnType<typeof useTaxForm>;

const TaxFormContext = createContext<TaxFormContextType | undefined>(undefined);

interface TaxFormProviderProps {
    children: ReactNode;
    value: TaxFormContextType;
}

export function TaxFormProvider({ children, value }: TaxFormProviderProps) {
    return (
        <TaxFormContext.Provider value={value}>
            {children}
        </TaxFormContext.Provider>
    );
}

export function useTaxFormContext(): TaxFormContextType {
    const context = useContext(TaxFormContext);
    if (!context) {
        throw new Error(
            'useTaxFormContext must be used within TaxFormProvider',
        );
    }
    return context;
}
