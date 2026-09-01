import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useClientAdvanceForm } from '../hooks/useClientAdvanceForm';

type ClientAdvanceFormContextType = ReturnType<typeof useClientAdvanceForm>;

const ClientAdvanceFormContext = createContext<
    ClientAdvanceFormContextType | undefined
>(undefined);

interface ClientAdvanceFormProviderProps {
    children: ReactNode;
    value: ClientAdvanceFormContextType;
}

export function ClientAdvanceFormProvider({
    children,
    value,
}: ClientAdvanceFormProviderProps) {
    return (
        <ClientAdvanceFormContext.Provider value={value}>
            {children}
        </ClientAdvanceFormContext.Provider>
    );
}

export function useClientAdvanceFormContext(): ClientAdvanceFormContextType {
    const context = useContext(ClientAdvanceFormContext);
    if (!context) {
        throw new Error(
            'useClientAdvanceFormContext must be used within ClientAdvanceFormProvider',
        );
    }
    return context;
}
