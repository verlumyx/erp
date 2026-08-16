import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useWarehouseForm } from '../hooks/useWarehouseForm';

type WarehouseFormContextType = ReturnType<typeof useWarehouseForm>;

const WarehouseFormContext = createContext<
    WarehouseFormContextType | undefined
>(undefined);

interface WarehouseFormProviderProps {
    children: ReactNode;
    value: WarehouseFormContextType;
}

export function WarehouseFormProvider({
    children,
    value,
}: WarehouseFormProviderProps) {
    return (
        <WarehouseFormContext.Provider value={value}>
            {children}
        </WarehouseFormContext.Provider>
    );
}

export function useWarehouseFormContext(): WarehouseFormContextType {
    const context = useContext(WarehouseFormContext);
    if (!context) {
        throw new Error(
            'useWarehouseFormContext must be used within WarehouseFormProvider',
        );
    }
    return context;
}
