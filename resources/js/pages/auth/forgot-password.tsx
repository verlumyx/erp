import { Form, Head, Link } from '@inertiajs/react';
import PasswordResetLinkController from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { home, login } from '@/routes';
import { request } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const sent = Boolean(status);

    return (
        <>
            <Head title="Recuperar contraseña — StreamCRM">
                <link rel="stylesheet" href="/css/site.css" />
            </Head>

            <div className="auth">
                <aside className="auth-brand">
                    <Link className="logo" href={home()}>
                        <StreamCrmLogo />
                    </Link>
                    <div className="auth-brand-body">
                        <h2>Recuperar el acceso es rápido.</h2>
                        <p>
                            Te enviaremos un enlace seguro para crear una
                            contraseña nueva y volver a tu panel en minutos.
                        </p>
                        <div className="auth-quote">
                            “El soporte y la recuperación de cuenta nunca me han
                            dejado tirado. Todo simple.”
                            <span>— Revendedor Pro</span>
                        </div>
                    </div>
                </aside>

                <main className="auth-main">
                    <div className="auth-top">
                        <Link className="auth-back" href={login()}>
                            <svg
                                viewBox="0 0 24 24"
                                width="16"
                                height="16"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M19 12H5M12 19l-7-7 7-7" />
                            </svg>{' '}
                            Volver a iniciar sesión
                        </Link>
                        <Link className="logo" href={home()}>
                            <StreamCrmLogo simple />
                        </Link>
                    </div>

                    <div className="auth-card">
                        {!sent ? (
                            <div>
                                <div className="auth-icon">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.7"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <rect
                                            x="2.5"
                                            y="4.5"
                                            width="19"
                                            height="15"
                                            rx="2.5"
                                        />
                                        <path d="m3 7 9 6 9-6" />
                                    </svg>
                                </div>
                                <h1>¿Olvidaste tu contraseña?</h1>
                                <p className="sub">
                                    No hay problema. Escribe el correo de tu
                                    cuenta y te enviaremos un enlace para
                                    restablecerla.
                                </p>

                                <Form
                                    action={PasswordResetLinkController.store.url()}
                                    method="post"
                                    className="form"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div
                                                className={
                                                    errors.email
                                                        ? 'fld invalid'
                                                        : 'fld'
                                                }
                                            >
                                                <label htmlFor="email">
                                                    Correo electrónico
                                                </label>
                                                <div className="ctrl">
                                                    <svg
                                                        className="lead"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="1.8"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    >
                                                        <rect
                                                            x="2.5"
                                                            y="4.5"
                                                            width="19"
                                                            height="15"
                                                            rx="2.5"
                                                        />
                                                        <path d="m3 7 9 6 9-6" />
                                                    </svg>
                                                    <input
                                                        id="email"
                                                        type="email"
                                                        name="email"
                                                        placeholder="tu@correo.com"
                                                        autoComplete="email"
                                                        autoFocus
                                                        required
                                                    />
                                                </div>
                                                <span className="err">
                                                    {errors.email ??
                                                        'Ingresa un correo válido'}
                                                </span>
                                            </div>
                                            <button
                                                type="submit"
                                                className="btn btn-brand btn-block btn-lg"
                                                disabled={processing}
                                                data-test="email-password-reset-link-button"
                                            >
                                                {processing && (
                                                    <span className="spin" />
                                                )}
                                                {processing
                                                    ? 'Enviando…'
                                                    : 'Enviar enlace de recuperación'}
                                            </button>
                                        </>
                                    )}
                                </Form>

                                <p className="alt-foot">
                                    ¿Recordaste tu contraseña?{' '}
                                    <Link href={login()}>Inicia sesión</Link>
                                </p>
                            </div>
                        ) : (
                            <div className="sent">
                                <div className="big-ico">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="1.7"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    >
                                        <rect
                                            x="2.5"
                                            y="4.5"
                                            width="19"
                                            height="15"
                                            rx="2.5"
                                        />
                                        <path d="m3 7 9 6 9-6" />
                                        <path d="m16 15 2 2 4-4" />
                                    </svg>
                                </div>
                                <h1>Revisa tu correo</h1>
                                <p className="sub">
                                    Si el correo está registrado, enviamos un
                                    enlace de recuperación. Revisa tu bandeja de
                                    entrada (y la carpeta de spam) — tiene una
                                    validez de 60 minutos.
                                </p>
                                <Link
                                    className="btn btn-ghost btn-block"
                                    href={login()}
                                    style={{ marginTop: 26 }}
                                >
                                    Volver a iniciar sesión
                                </Link>
                                <p className="resend">
                                    ¿No te llegó? Revisa spam o{' '}
                                    <Link href={request()}>
                                        prueba con otro correo
                                    </Link>
                                    .
                                </p>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
