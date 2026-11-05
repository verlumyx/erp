interface StreamCrmLogoProps {
    simple?: boolean;
    className?: string;
    iconClassName?: string;
    hideText?: boolean;
}

/**
 * Valolabs POS brand lockup used across auth and navigation.
 * `simple` or `hideText` renders only the primary mark (used inside compact spots).
 */
export default function StreamCrmLogo({
    simple = false,
    className = '',
    iconClassName = 'h-9 w-auto',
    hideText = false,
}: StreamCrmLogoProps) {
    const showText = !simple && !hideText;

    return (
        <span className={`inline-flex items-center gap-2.5 ${className}`}>
            <img
                src="/images/logo-1.png"
                alt="Valolabs"
                className={`shrink-0 object-contain ${iconClassName}`}
            />
            {showText && (
                <span className="logo-txt text-xl font-bold tracking-tight text-slate-900 select-none dark:text-white">
                    Valolabs
                    <b className="font-extrabold text-indigo-600 dark:text-indigo-400">
                        POS
                    </b>
                </span>
            )}
        </span>
    );
}
