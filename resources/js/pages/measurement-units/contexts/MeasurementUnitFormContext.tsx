import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useMeasurementUnitForm } from '../hooks/useMeasurementUnitForm';

type MeasurementUnitFormContextType = ReturnType<typeof useMeasurementUnitForm>;

const MeasurementUnitFormContext = createContext<
    MeasurementUnitFormContextType | undefined
>(undefined);

interface MeasurementUnitFormProviderProps {
    children: ReactNode;
    value: MeasurementUnitFormContextType;
}

export function MeasurementUnitFormProvider({
    children,
    value,
}: MeasurementUnitFormProviderProps) {
    return (
        <MeasurementUnitFormContext.Provider value={value}>
            {children}
        </MeasurementUnitFormContext.Provider>
    );
}

export function useMeasurementUnitFormContext(): MeasurementUnitFormContextType {
    const context = useContext(MeasurementUnitFormContext);
    if (!context) {
        throw new Error(
            'useMeasurementUnitFormContext must be used within MeasurementUnitFormProvider',
        );
    }
    return context;
}
