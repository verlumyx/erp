import { Form, Head, Link } from '@inertiajs/react';
import { Check, Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useMemo, useState } from 'react';
import NewPasswordController from '@/actions/Laravel/Fortify/Http/Controllers/NewPasswordController';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { home, login } from '@/routes';

interface ResetPasswordProps {
    token: string;
    email: string;
}

const REQS = [
    {
        key: 'len',
        label: 'Al menos 8 caracteres',
        test: (v: string) => v.length >= 8,
    },
    {
        key: 'upper',
        label: 'Una letra mayúscula',
        test: (v: string) => /[A-Z]/.test(v),
    },
    {
        key: 'num',
        label: 'Un número',
        test: (v: string) => /[0-9]/.test(v),
    },
    {
        key: 'sym',
        label: 'Un símbolo (!?@#…)',
        test: (v: string) => /[^A-Za-z0-9]/.test(v),
    },
] as const;

const STRENGTH_LABELS = ['', 'Débil', 'Aceptable', 'Buena', 'Excelente'];

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const [password, setPassword] = useState('');
    const [confirm, setConfirm] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    const met = useMemo(
        () => REQS.map((r) => ({ key: r.key, ok: r.test(password) })),
        [password],
    );
    const score = met.filter((m) => m.ok).length;
    const mismatch = confirm.length > 0 && confirm !== password;

    return (
        <>
            <Head title="Restablecer contraseña — Valolabs ERP">
                <link rel="stylesheet" href="/css/site.css" />
            </Head>

            <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-50/70 p-4 sm:p-6 lg:p-8 dark:bg-zinc-950">
                {/* Minimalist ambient background glow */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 -z-10 flex items-center justify-center overflow-hidden"
                >
                    <div className="h-[520px] w-[520px] rounded-full bg-gradient-to-tr from-indigo-500/10 via-sky-400/10 to-transparent blur-3xl dark:from-indigo-600/15 dark:via-blue-500/10" />
                </div>

                <div className="w-full max-w-[440px]">
                    {/* Card container */}
                    <div className="rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm sm:p-9 dark:border-zinc-800 dark:bg-zinc-900">
                        {/* Brand Logo */}
                        <div className="mb-6 flex justify-center">
                            <Link
                                className="logo inline-flex items-center gap-2.5 text-slate-900 transition-opacity hover:opacity-90 dark:text-white"
                                href={home()}
                            >
                                <StreamCrmLogo />
                            </Link>
                        </div>

                        {/* Title and subtitle */}
                        <div className="mb-6 space-y-1.5 text-center">
                            <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                                Crea tu nueva contraseña
                            </h1>
                            <p className="text-sm text-slate-500 dark:text-zinc-400">
                                Debe ser distinta a las anteriores. Te recomendamos
                                una clave fuerte.
                            </p>
                        </div>

                        {/* Account pill */}
                        {email && (
                            <div className="mb-5 flex items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/70 px-3.5 py-2.5 text-xs text-slate-600 dark:border-zinc-800 dark:bg-zinc-800/40 dark:text-zinc-400">
                                <Mail className="size-3.5 text-slate-400 dark:text-zinc-500" />
                                <span className="truncate">
                                    Cuenta:{' '}
                                    <strong className="font-semibold text-slate-800 dark:text-zinc-200">
                                        {email}
                                    </strong>
                                </span>
                            </div>
                        )}

                        <Form
                            action={NewPasswordController.store.url()}
                            method="post"
                            transform={(data) => ({ ...data, token, email })}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            className="space-y-4 sm:space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Password */}
                                    <div className="space-y-1.5">
                                        <label
                                            htmlFor="password"
                                            className="block text-xs font-semibold tracking-wider text-slate-600 uppercase dark:text-zinc-400"
                                        >
                                            Nueva contraseña
                                        </label>
                                        <div className="relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500">
                                                <Lock className="size-4" />
                                            </div>
                                            <input
                                                id="password"
                                                type={
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                name="password"
                                                placeholder="••••••••"
                                                autoComplete="new-password"
                                                autoFocus
                                                required
                                                value={password}
                                                onChange={(e) =>
                                                    setPassword(e.target.value)
                                                }
                                                className={`h-11 w-full rounded-xl border bg-slate-50/50 pr-10 pl-10 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:bg-zinc-800 ${
                                                    errors.password
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-500'
                                                        : 'border-slate-200 focus:border-indigo-600 dark:border-zinc-700 dark:focus:border-indigo-500'
                                                }`}
                                            />
                                            <button
                                                type="button"
                                                aria-label={
                                                    showPassword
                                                        ? 'Ocultar contraseña'
                                                        : 'Mostrar contraseña'
                                                }
                                                onClick={() =>
                                                    setShowPassword((v) => !v)
                                                }
                                                className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                            >
                                                {showPassword ? (
                                                    <EyeOff className="size-4.5" />
                                                ) : (
                                                    <Eye className="size-4.5" />
                                                )}
                                            </button>
                                        </div>

                                        {/* Strength meter */}
                                        <div className="space-y-1 pt-1">
                                            <div className="grid grid-cols-4 gap-1.5">
                                                {[1, 2, 3, 4].map((step) => {
                                                    const active =
                                                        password.length > 0 &&
                                                        score >= step;
                                                    const colors = [
                                                        'bg-rose-500',
                                                        'bg-amber-500',
                                                        'bg-blue-500',
                                                        'bg-emerald-500',
                                                    ];
                                                    const color =
                                                        colors[score - 1] ??
                                                        'bg-slate-200 dark:bg-zinc-700';
                                                    return (
                                                        <div
                                                            key={step}
                                                            className={`h-1.5 rounded-full transition-all duration-300 ${
                                                                active
                                                                    ? color
                                                                    : 'bg-slate-200 dark:bg-zinc-700'
                                                            }`}
                                                        />
                                                    );
                                                })}
                                            </div>
                                            <p className="text-[11px] text-slate-500 dark:text-zinc-400">
                                                {password
                                                    ? `Seguridad: ${STRENGTH_LABELS[score]}`
                                                    : 'Usa 8+ caracteres con mayúsculas, números y símbolos'}
                                            </p>
                                        </div>

                                        {errors.password && (
                                            <p className="text-xs font-medium text-rose-600 dark:text-rose-400">
                                                {errors.password}
                                            </p>
                                        )}
                                    </div>

                                    {/* Password Confirmation */}
                                    <div className="space-y-1.5">
                                        <label
                                            htmlFor="password_confirmation"
                                            className="block text-xs font-semibold tracking-wider text-slate-600 uppercase dark:text-zinc-400"
                                        >
                                            Confirmar contraseña
                                        </label>
                                        <div className="relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500">
                                                <Lock className="size-4" />
                                            </div>
                                            <input
                                                id="password_confirmation"
                                                type={
                                                    showConfirm
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                name="password_confirmation"
                                                placeholder="••••••••"
                                                autoComplete="new-password"
                                                required
                                                value={confirm}
                                                onChange={(e) =>
                                                    setConfirm(e.target.value)
                                                }
                                                className={`h-11 w-full rounded-xl border bg-slate-50/50 pr-10 pl-10 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:bg-zinc-800 ${
                                                    mismatch ||
                                                    errors.password_confirmation
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-500'
                                                        : 'border-slate-200 focus:border-indigo-600 dark:border-zinc-700 dark:focus:border-indigo-500'
                                                }`}
                                            />
                                            <button
                                                type="button"
                                                aria-label={
                                                    showConfirm
                                                        ? 'Ocultar contraseña'
                                                        : 'Mostrar contraseña'
                                                }
                                                onClick={() =>
                                                    setShowConfirm((v) => !v)
                                                }
                                                className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 transition-colors hover:text-slate-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                            >
                                                {showConfirm ? (
                                                    <EyeOff className="size-4.5" />
                                                ) : (
                                                    <Eye className="size-4.5" />
                                                )}
                                            </button>
                                        </div>
                                        {mismatch && (
                                            <p className="text-xs font-medium text-rose-600 dark:text-rose-400">
                                                Las contraseñas no coinciden
                                            </p>
                                        )}
                                        {errors.password_confirmation &&
                                            !mismatch && (
                                                <p className="text-xs font-medium text-rose-600 dark:text-rose-400">
                                                    {
                                                        errors.password_confirmation
                                                    }
                                                </p>
                                            )}
                                    </div>

                                    {/* Requirements checklist */}
                                    <div className="rounded-xl border border-slate-100 bg-slate-50/70 p-3 text-xs dark:border-zinc-800/80 dark:bg-zinc-800/30">
                                        <p className="mb-2 font-semibold text-slate-600 dark:text-zinc-300">
                                            Requisitos de la contraseña:
                                        </p>
                                        <ul className="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                            {REQS.map((r) => {
                                                const isOk = met.find(
                                                    (m) => m.key === r.key,
                                                )?.ok;
                                                return (
                                                    <li
                                                        key={r.key}
                                                        className={`flex items-center gap-1.5 transition-colors ${
                                                            isOk
                                                                ? 'font-medium text-emerald-600 dark:text-emerald-400'
                                                                : 'text-slate-400 dark:text-zinc-500'
                                                        }`}
                                                    >
                                                        <Check
                                                            className={`size-3.5 ${
                                                                isOk
                                                                    ? 'stroke-[2.5]'
                                                                    : 'stroke-1 text-slate-300 dark:text-zinc-600'
                                                            }`}
                                                        />
                                                        <span>{r.label}</span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>

                                    {/* Submit button */}
                                    <button
                                        type="submit"
                                        disabled={processing || mismatch}
                                        data-test="reset-password-button"
                                        className="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow active:scale-[0.99] disabled:pointer-events-none disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                                    >
                                        {processing ? (
                                            <>
                                                <svg
                                                    className="size-4 animate-spin text-white"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                >
                                                    <circle
                                                        className="opacity-25"
                                                        cx="12"
                                                        cy="12"
                                                        r="10"
                                                        stroke="currentColor"
                                                        strokeWidth="4"
                                                    />
                                                    <path
                                                        className="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                                    />
                                                </svg>
                                                <span>Guardando…</span>
                                            </>
                                        ) : (
                                            <span>Guardar contraseña</span>
                                        )}
                                    </button>
                                </>
                            )}
                        </Form>

                        {/* Back to login link */}
                        <div className="mt-6 text-center text-xs text-slate-500 dark:text-zinc-400">
                            ¿Deseas cancelar?{' '}
                            <Link
                                href={login()}
                                className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Volver a iniciar sesión
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
