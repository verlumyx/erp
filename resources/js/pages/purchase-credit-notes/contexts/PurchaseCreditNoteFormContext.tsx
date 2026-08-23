import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePurchaseCreditNoteForm } from '../hooks/usePurchaseCreditNoteForm';
import type { PurchaseCreditNoteOptions } from '../types/PurchaseCreditNote';

type PurchaseCreditNoteFormContextType = ReturnType<
    typeof usePurchaseCreditNoteForm
> & {
    options: PurchaseCreditNoteOptions;
};

const PurchaseCreditNoteFormContext = createContext<
    PurchaseCreditNoteFormContextType | undefined
>(undefined);

interface PurchaseCreditNoteFormProviderProps {
    children: ReactNode;
    value: PurchaseCreditNoteFormContextType;
}

export function PurchaseCreditNoteFormProvider({
    children,
    value,
}: PurchaseCreditNoteFormProviderProps) {
    return (
        <PurchaseCreditNoteFormContext.Provider value={value}>
            {children}
        </PurchaseCreditNoteFormContext.Provider>
    );
}

export function usePurchaseCreditNoteFormContext(): PurchaseCreditNoteFormContextType {
    const context = useContext(PurchaseCreditNoteFormContext);
    if (!context) {
        throw new Error(
            'usePurchaseCreditNoteFormContext must be used within PurchaseCreditNoteFormProvider',
        );
    }
    return context;
}
