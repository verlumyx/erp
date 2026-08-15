import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { usePlanForm } from '../hooks/usePlanForm';

type PlanFormContextType = ReturnType<typeof usePlanForm>;

const PlanFormContext = createContext<PlanFormContextType | undefined>(
    undefined,
);

interface PlanFormProviderProps {
    children: ReactNode;
    value: PlanFormContextType;
}

export function PlanFormProvider({ children, value }: PlanFormProviderProps) {
    return (
        <PlanFormContext.Provider value={value}>
            {children}
        </PlanFormContext.Provider>
    );
}

export function usePlanFormContext(): PlanFormContextType {
    const context = useContext(PlanFormContext);
    if (!context) {
        throw new Error(
            'usePlanFormContext must be used within PlanFormProvider',
        );
    }
    return context;
}
