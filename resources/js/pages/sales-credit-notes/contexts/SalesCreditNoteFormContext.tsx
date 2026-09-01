import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useSalesCreditNoteForm } from '../hooks/useSalesCreditNoteForm';
import type { SalesCreditNoteOptions } from '../types/SalesCreditNote';

type SalesCreditNoteFormContextType = ReturnType<
    typeof useSalesCreditNoteForm
> & {
    options: SalesCreditNoteOptions;
};

const SalesCreditNoteFormContext = createContext<
    SalesCreditNoteFormContextType | undefined
>(undefined);

interface SalesCreditNoteFormProviderProps {
    children: ReactNode;
    value: SalesCreditNoteFormContextType;
}

export function SalesCreditNoteFormProvider({
    children,
    value,
}: SalesCreditNoteFormProviderProps) {
    return (
        <SalesCreditNoteFormContext.Provider value={value}>
            {children}
        </SalesCreditNoteFormContext.Provider>
    );
}

export function useSalesCreditNoteFormContext(): SalesCreditNoteFormContextType {
    const context = useContext(SalesCreditNoteFormContext);
    if (!context) {
        throw new Error(
            'useSalesCreditNoteFormContext must be used within SalesCreditNoteFormProvider',
        );
    }
    return context;
}
