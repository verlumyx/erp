import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useRouteForm } from '../hooks/useRouteForm';

type RouteFormContextType = ReturnType<typeof useRouteForm>;

const RouteFormContext = createContext<RouteFormContextType | undefined>(
    undefined,
);

interface RouteFormProviderProps {
    children: ReactNode;
    value: RouteFormContextType;
}

export function RouteFormProvider({ children, value }: RouteFormProviderProps) {
    return (
        <RouteFormContext.Provider value={value}>
            {children}
        </RouteFormContext.Provider>
    );
}

export function useRouteFormContext(): RouteFormContextType {
    const context = useContext(RouteFormContext);
    if (!context) {
        throw new Error(
            'useRouteFormContext must be used within RouteFormProvider',
        );
    }
    return context;
}
