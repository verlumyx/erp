import { createContext, useContext, type ReactNode } from 'react';
import type { RefundFormState } from '../hooks/useRefundForm';

const RefundFormContext = createContext<RefundFormState | undefined>(undefined);

interface RefundFormProviderProps {
    children: ReactNode;
    value: RefundFormState;
}

export function RefundFormProvider({
    children,
    value,
}: RefundFormProviderProps) {
    return (
        <RefundFormContext.Provider value={value}>
            {children}
        </RefundFormContext.Provider>
    );
}

export function useRefundFormContext(): RefundFormState {
    const context = useContext(RefundFormContext);

    if (context === undefined) {
        throw new Error(
            'useRefundFormContext debe usarse dentro de un RefundFormProvider.',
        );
    }

    return context;
}
