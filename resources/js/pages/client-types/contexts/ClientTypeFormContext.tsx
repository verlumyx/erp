import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useClientTypeForm } from '../hooks/useClientTypeForm';

type ClientTypeFormContextType = ReturnType<typeof useClientTypeForm>;

const ClientTypeFormContext = createContext<
    ClientTypeFormContextType | undefined
>(undefined);

interface ClientTypeFormProviderProps {
    children: ReactNode;
    value: ClientTypeFormContextType;
}

export function ClientTypeFormProvider({
    children,
    value,
}: ClientTypeFormProviderProps) {
    return (
        <ClientTypeFormContext.Provider value={value}>
            {children}
        </ClientTypeFormContext.Provider>
    );
}

export function useClientTypeFormContext(): ClientTypeFormContextType {
    const context = useContext(ClientTypeFormContext);
    if (!context) {
        throw new Error(
            'useClientTypeFormContext must be used within ClientTypeFormProvider',
        );
    }
    return context;
}
