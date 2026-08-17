import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useConfigurationForm } from '../hooks/useConfigurationForm';

type ConfigurationFormContextType = ReturnType<typeof useConfigurationForm>;

const ConfigurationFormContext = createContext<
    ConfigurationFormContextType | undefined
>(undefined);

interface ConfigurationFormProviderProps {
    children: ReactNode;
    value: ConfigurationFormContextType;
}

export function ConfigurationFormProvider({
    children,
    value,
}: ConfigurationFormProviderProps) {
    return (
        <ConfigurationFormContext.Provider value={value}>
            {children}
        </ConfigurationFormContext.Provider>
    );
}

export function useConfigurationFormContext(): ConfigurationFormContextType {
    const context = useContext(ConfigurationFormContext);
    if (!context) {
        throw new Error(
            'useConfigurationFormContext must be used within ConfigurationFormProvider',
        );
    }
    return context;
}
