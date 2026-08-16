import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useClientForm } from '../hooks/useClientForm';
import type { ClientOptions } from '../types/Client';

type ClientFormContextType = ReturnType<typeof useClientForm> & {
    options: ClientOptions;
};

const ClientFormContext = createContext<ClientFormContextType | undefined>(
    undefined,
);

interface ClientFormProviderProps {
    children: ReactNode;
    value: ClientFormContextType;
}

export function ClientFormProvider({
    children,
    value,
}: ClientFormProviderProps) {
    return (
        <ClientFormContext.Provider value={value}>
            {children}
        </ClientFormContext.Provider>
    );
}

export function useClientFormContext(): ClientFormContextType {
    const context = useContext(ClientFormContext);
    if (!context) {
        throw new Error(
            'useClientFormContext must be used within ClientFormProvider',
        );
    }
    return context;
}
