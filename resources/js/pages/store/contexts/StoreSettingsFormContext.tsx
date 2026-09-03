import type { ReactNode } from 'react';
import { createContext, useContext } from 'react';
import type { useStoreSettingsForm } from '../hooks/useStoreSettingsForm';
import type { StoreSettingOptions } from '../types/Store';

type StoreSettingsFormContextType = ReturnType<typeof useStoreSettingsForm> & {
    options: StoreSettingOptions;
};

const StoreSettingsFormContext = createContext<
    StoreSettingsFormContextType | undefined
>(undefined);

interface StoreSettingsFormProviderProps {
    children: ReactNode;
    value: StoreSettingsFormContextType;
}

export function StoreSettingsFormProvider({
    children,
    value,
}: StoreSettingsFormProviderProps) {
    return (
        <StoreSettingsFormContext.Provider value={value}>
            {children}
        </StoreSettingsFormContext.Provider>
    );
}

export function useStoreSettingsFormContext(): StoreSettingsFormContextType {
    const context = useContext(StoreSettingsFormContext);
    if (!context) {
        throw new Error(
            'useStoreSettingsFormContext must be used within StoreSettingsFormProvider',
        );
    }
    return context;
}
