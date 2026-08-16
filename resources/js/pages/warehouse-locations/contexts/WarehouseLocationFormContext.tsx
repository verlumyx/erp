import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useWarehouseLocationForm } from '../hooks/useWarehouseLocationForm';

type WarehouseLocationFormContextType = ReturnType<
    typeof useWarehouseLocationForm
>;

const WarehouseLocationFormContext = createContext<
    WarehouseLocationFormContextType | undefined
>(undefined);

interface WarehouseLocationFormProviderProps {
    children: ReactNode;
    value: WarehouseLocationFormContextType;
}

export function WarehouseLocationFormProvider({
    children,
    value,
}: WarehouseLocationFormProviderProps) {
    return (
        <WarehouseLocationFormContext.Provider value={value}>
            {children}
        </WarehouseLocationFormContext.Provider>
    );
}

export function useWarehouseLocationFormContext(): WarehouseLocationFormContextType {
    const context = useContext(WarehouseLocationFormContext);
    if (!context) {
        throw new Error(
            'useWarehouseLocationFormContext must be used within WarehouseLocationFormProvider',
        );
    }
    return context;
}
