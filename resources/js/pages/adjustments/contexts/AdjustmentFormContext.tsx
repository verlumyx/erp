import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useAdjustmentForm } from '../hooks/useAdjustmentForm';
import type { AdjustmentOptions } from '../types/Adjustment';

type AdjustmentFormContextType = ReturnType<typeof useAdjustmentForm> & {
    options: AdjustmentOptions;
};

const AdjustmentFormContext = createContext<
    AdjustmentFormContextType | undefined
>(undefined);

interface AdjustmentFormProviderProps {
    children: ReactNode;
    value: AdjustmentFormContextType;
}

export function AdjustmentFormProvider({
    children,
    value,
}: AdjustmentFormProviderProps) {
    return (
        <AdjustmentFormContext.Provider value={value}>
            {children}
        </AdjustmentFormContext.Provider>
    );
}

export function useAdjustmentFormContext(): AdjustmentFormContextType {
    const context = useContext(AdjustmentFormContext);
    if (!context) {
        throw new Error(
            'useAdjustmentFormContext must be used within AdjustmentFormProvider',
        );
    }
    return context;
}
