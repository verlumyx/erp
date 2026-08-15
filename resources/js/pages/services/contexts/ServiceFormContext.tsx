import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useServiceForm } from '../hooks/useServiceForm';

type ServiceFormContextType = ReturnType<typeof useServiceForm>;

const ServiceFormContext = createContext<ServiceFormContextType | undefined>(
    undefined,
);

interface ServiceFormProviderProps {
    children: ReactNode;
    value: ServiceFormContextType;
}

export function ServiceFormProvider({
    children,
    value,
}: ServiceFormProviderProps) {
    return (
        <ServiceFormContext.Provider value={value}>
            {children}
        </ServiceFormContext.Provider>
    );
}

export function useServiceFormContext(): ServiceFormContextType {
    const context = useContext(ServiceFormContext);
    if (!context) {
        throw new Error(
            'useServiceFormContext must be used within ServiceFormProvider',
        );
    }
    return context;
}
