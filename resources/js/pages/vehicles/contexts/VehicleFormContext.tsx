import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useVehicleForm } from '../hooks/useVehicleForm';

type VehicleFormContextType = ReturnType<typeof useVehicleForm>;

const VehicleFormContext = createContext<VehicleFormContextType | undefined>(
    undefined,
);

interface VehicleFormProviderProps {
    children: ReactNode;
    value: VehicleFormContextType;
}

export function VehicleFormProvider({
    children,
    value,
}: VehicleFormProviderProps) {
    return (
        <VehicleFormContext.Provider value={value}>
            {children}
        </VehicleFormContext.Provider>
    );
}

export function useVehicleFormContext(): VehicleFormContextType {
    const context = useContext(VehicleFormContext);
    if (!context) {
        throw new Error(
            'useVehicleFormContext must be used within VehicleFormProvider',
        );
    }
    return context;
}
