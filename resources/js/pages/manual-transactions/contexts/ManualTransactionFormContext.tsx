import { createContext, useContext, type ReactNode } from 'react';
import type { ManualTransactionFormState } from '../hooks/useManualTransactionForm';

const ManualTransactionFormContext = createContext<
    ManualTransactionFormState | undefined
>(undefined);

interface ManualTransactionFormProviderProps {
    children: ReactNode;
    value: ManualTransactionFormState;
}

export function ManualTransactionFormProvider({
    children,
    value,
}: ManualTransactionFormProviderProps) {
    return (
        <ManualTransactionFormContext.Provider value={value}>
            {children}
        </ManualTransactionFormContext.Provider>
    );
}

export function useManualTransactionFormContext(): ManualTransactionFormState {
    const context = useContext(ManualTransactionFormContext);

    if (context === undefined) {
        throw new Error(
            'useManualTransactionFormContext debe usarse dentro de un ManualTransactionFormProvider.',
        );
    }

    return context;
}
