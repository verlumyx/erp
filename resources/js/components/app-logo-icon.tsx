import { HTMLAttributes } from 'react';

export default function AppLogoIcon({
    className = '',
    ...props
}: HTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/logo-1.png"
            alt="Valolabs"
            className={`object-contain ${className}`}
            {...props}
        />
    );
}
