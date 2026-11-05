import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Mail } from 'lucide-react';
import PasswordResetLinkController from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { home, login } from '@/routes';
import { request } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const sent = Boolean(status);

    return (
        <>
            <Head title="Recuperar contraseña — Valolabs ERP">
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

                        {!sent ? (
                            <>
                                {/* Title and subtitle */}
                                <div className="mb-7 space-y-1.5 text-center">
                                    <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                                        ¿Olvidaste tu contraseña?
                                    </h1>
                                    <p className="text-sm text-slate-500 dark:text-zinc-400">
                                        No te preocupes. Escribe el correo de tu
                                        cuenta y te enviaremos un enlace para
                                        restablecerla.
                                    </p>
                                </div>

                                <Form
                                    action={PasswordResetLinkController.store.url()}
                                    method="post"
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

                                            {/* Submit button */}
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                data-test="email-password-reset-link-button"
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
                                                        <span>Enviando…</span>
                                                    </>
                                                ) : (
                                                    <span>
                                                        Enviar enlace de recuperación
                                                    </span>
                                                )}
                                            </button>
                                        </>
                                    )}
                                </Form>

                                {/* Back to login link */}
                                <div className="mt-6 text-center text-xs text-slate-500 dark:text-zinc-400">
                                    ¿Recordaste tu contraseña?{' '}
                                    <Link
                                        href={login()}
                                        className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        Inicia sesión
                                    </Link>
                                </div>
                            </>
                        ) : (
                            <div className="space-y-6 text-center">
                                {/* Success Icon */}
                                <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                    <CheckCircle2 className="size-8 stroke-[1.8]" />
                                </div>

                                {/* Title and description */}
                                <div className="space-y-2">
                                    <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                                        Revisa tu correo
                                    </h1>
                                    <p className="text-sm text-slate-500 dark:text-zinc-400">
                                        Si tu correo está registrado, enviamos un
                                        enlace de recuperación. Revisa tu
                                        bandeja de entrada y la carpeta de spam
                                        — tiene una validez de 60 minutos.
                                    </p>
                                </div>

                                {status && (
                                    <div className="rounded-xl border border-emerald-200/80 bg-emerald-50/80 p-3 text-center text-xs font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        {status}
                                    </div>
                                )}

                                {/* Action button */}
                                <Link
                                    href={login()}
                                    className="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition-all hover:bg-indigo-700 hover:shadow active:scale-[0.99] dark:bg-indigo-600 dark:hover:bg-indigo-500"
                                >
                                    <ArrowLeft className="size-4" />
                                    <span>Volver a iniciar sesión</span>
                                </Link>

                                <p className="text-xs text-slate-500 dark:text-zinc-500">
                                    ¿No te llegó? Revisa spam o{' '}
                                    <Link
                                        href={request()}
                                        className="font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                    >
                                        prueba con otro correo
                                    </Link>
                                    .
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
