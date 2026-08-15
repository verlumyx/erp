import React, { createContext, useContext, ReactNode } from 'react';

export interface ExistingUser {
    id: string;
    name: string;
}

export interface UserFormData {
    id: string;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role_id: string;
    existing_user_id: string;
}

interface UserFormContextType {
    data: UserFormData;
    setData: (key: keyof UserFormData, value: string) => void;
    errors: Partial<Record<keyof UserFormData, string>>;
    processing: boolean;
    handleSubmit: (e: React.FormEvent) => void;
    reset: () => void;
    emailStep: boolean;
    emailChecked: boolean;
    existingUser: ExistingUser | null;
    isCheckingEmail: boolean;
    alreadyInCompany: boolean;
    handleEmailCheck: (email: string) => Promise<void>;
    handleChangeEmail: () => void;
}

const UserFormContext = createContext<UserFormContextType | undefined>(undefined);

interface UserFormProviderProps {
    children: ReactNode;
    value: UserFormContextType;
}

export function UserFormProvider({ children, value }: UserFormProviderProps) {
    return (
        <UserFormContext.Provider value={value}>
            {children}
        </UserFormContext.Provider>
    );
}

export function useUserFormContext() {
    const context = useContext(UserFormContext);
    if (!context) {
        throw new Error('useUserFormContext must be used within UserFormProvider');
    }
    return context;
}
