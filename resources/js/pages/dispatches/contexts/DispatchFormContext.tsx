import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useDispatchForm } from '../hooks/useDispatchForm';
import type { DispatchOptions } from '../types/Dispatch';

type DispatchFormContextType = ReturnType<typeof useDispatchForm> & {
    options: DispatchOptions;
};

const DispatchFormContext = createContext<DispatchFormContextType | undefined>(
    undefined,
);

interface DispatchFormProviderProps {
    children: ReactNode;
    value: DispatchFormContextType;
}

export function DispatchFormProvider({
    children,
    value,
}: DispatchFormProviderProps) {
    return (
        <DispatchFormContext.Provider value={value}>
            {children}
        </DispatchFormContext.Provider>
    );
}

export function useDispatchFormContext(): DispatchFormContextType {
    const context = useContext(DispatchFormContext);
    if (!context) {
        throw new Error(
            'useDispatchFormContext must be used within DispatchFormProvider',
        );
    }
    return context;
}
