import { Form, Head, Link } from '@inertiajs/react';
import { Eye, EyeOff, Lock, Mail } from 'lucide-react';
import { useState } from 'react';
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { home } from '@/routes';
import { request } from '@/routes/password';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <>
            <Head title="Iniciar sesión — ValolabsPOS">
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

                <div className="w-full max-w-[420px]">
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
                        <div className="mb-7 space-y-1.5 text-center">
                            <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                                Bienvenido de vuelta
                            </h1>
                            <p className="text-sm text-slate-500 dark:text-zinc-400">
                                Ingresa tus datos para entrar a tu panel
                            </p>
                        </div>

                        {/* Status notification */}
                        {status && (
                            <div className="mb-6 rounded-xl border border-emerald-200/80 bg-emerald-50/80 p-3.5 text-center text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                                {status}
                            </div>
                        )}

                        {/* Login form */}
                        <Form
                            action={AuthenticatedSessionController.store.url()}
                            method="post"
                            resetOnSuccess={['password']}
                            className="space-y-4 sm:space-y-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Email */}
                                    <div className="space-y-1.5">
                                        <label
                                            htmlFor="email"
                                            className="block text-xs font-semibold tracking-wider text-slate-600 uppercase dark:text-zinc-400"
                                        >
                                            Correo electrónico
                                        </label>
                                        <div className="relative">
                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 dark:text-zinc-500">
                                                <Mail className="size-4" />
                                            </div>
                                            <input
                                                id="email"
                                                type="email"
                                                name="email"
                                                placeholder="tu@correo.com"
                                                autoComplete="email"
                                                autoFocus
                                                required
                                                className={`h-11 w-full rounded-xl border bg-slate-50/50 pr-3.5 pl-10 text-sm text-slate-900 transition-colors placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:outline-none dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:bg-zinc-800 ${
                                                    errors.email
                                                        ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-500'
                                                        : 'border-slate-200 focus:border-indigo-600 dark:border-zinc-700 dark:focus:border-indigo-500'
                                                }`}
                                            />
                                        </div>
                                        {errors.email && (
                                            <p className="text-xs font-medium text-rose-600 dark:text-rose-400">
                                                {errors.email}
                                            </p>
                                        )}
                                    </div>

                                    {/* Password */}
                                    <div className="space-y-1.5">
                                        <div className="flex items-center justify-between">
                                            <label
                                                htmlFor="password"
                                                className="block text-xs font-semibold tracking-wider text-slate-600 uppercase dark:text-zinc-400"
                                            >
                                                Contraseña
                                            </label>
                                            {canResetPassword && (
                                                <Link
                                                    className="text-xs font-semibold text-indigo-600 transition-colors hover:text-indigo-500 hover:underline dark:text-indigo-400"
                                                    href={request()}
                                                >
                                                    ¿Olvidaste tu contraseña?
                                                </Link>
                                            )}
                                        </div>
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
                                                autoComplete="current-password"
                                                required
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
                                        {errors.password && (
                                            <p className="text-xs font-medium text-rose-600 dark:text-rose-400">
                                                {errors.password}
                                            </p>
                                        )}
                                    </div>

                                    {/* Remember Me */}
                                    <div className="flex items-center pt-0.5">
                                        <label className="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 select-none dark:text-zinc-400">
                                            <input
                                                type="checkbox"
                                                name="remember"
                                                className="size-4 rounded border-slate-300 text-indigo-600 accent-indigo-600 focus:ring-indigo-500/20 dark:border-zinc-700"
                                            />
                                            <span>
                                                Mantener sesión iniciada
                                            </span>
                                        </label>
                                    </div>

                                    {/* Submit button */}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        data-test="login-button"
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
                                                <span>Entrando…</span>
                                            </>
                                        ) : (
                                            <span>Entrar a mi panel</span>
                                        )}
                                    </button>
                                </>
                            )}
                        </Form>

                        {/* Footnote */}
                        <div className="mt-6 text-center text-xs text-slate-500 dark:text-zinc-500">
                            ¿Problemas para entrar?{' '}
                            <a
                                href="mailto:soporte@valolabspos.com"
                                className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                Escríbenos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
