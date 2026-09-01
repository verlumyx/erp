import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useClientCollectionForm } from '../hooks/useClientCollectionForm';

type ClientCollectionFormContextType = ReturnType<
    typeof useClientCollectionForm
>;

const ClientCollectionFormContext = createContext<
    ClientCollectionFormContextType | undefined
>(undefined);

interface ClientCollectionFormProviderProps {
    children: ReactNode;
    value: ClientCollectionFormContextType;
}

export function ClientCollectionFormProvider({
    children,
    value,
}: ClientCollectionFormProviderProps) {
    return (
        <ClientCollectionFormContext.Provider value={value}>
            {children}
        </ClientCollectionFormContext.Provider>
    );
}

export function useClientCollectionFormContext(): ClientCollectionFormContextType {
    const context = useContext(ClientCollectionFormContext);
    if (!context) {
        throw new Error(
            'useClientCollectionFormContext must be used within ClientCollectionFormProvider',
        );
    }
    return context;
}
