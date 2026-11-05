export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center">
                <img
                    src="/images/logo-1.png"
                    alt="Valolabs"
                    className="size-8 object-contain"
                />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate text-base leading-tight font-semibold tracking-tight text-sidebar-accent-foreground">
                    Valolabs
                    <b className="font-extrabold text-indigo-600 dark:text-indigo-400">
                        POS
                    </b>
                </span>
            </div>
        </>
    );
}
