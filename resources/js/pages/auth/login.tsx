import { Form, Head, Link } from '@inertiajs/react';
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
            <Head title="Iniciar sesión — StreamCRM">
                <link rel="stylesheet" href="/css/site.css" />
            </Head>

            <div className="auth">
                {/* BRAND PANEL */}
                <aside className="auth-brand">
                    <Link className="logo" href={home()}>
                        <StreamCrmLogo />
                    </Link>
                    <div className="auth-brand-body">
                        <h2>Tu negocio de streaming, bajo control.</h2>
                        <p>
                            Clientes, perfiles, vencimientos y cobros — todo en
                            un panel. Entra y sigue vendiendo con orden.
                        </p>
                        <div className="auth-mini">
                            <div className="m">
                                <div className="mv">$241k</div>
                                <div className="ml">Ganancia / mes</div>
                            </div>
                            <div className="m">
                                <div className="mv">52</div>
                                <div className="ml">Perfiles activos</div>
                            </div>
                            <div className="m">
                                <div className="mv">9</div>
                                <div className="ml">Plataformas</div>
                            </div>
                        </div>
                        <div className="auth-quote">
                            “Antes llevaba todo en cuadernos y notas del
                            teléfono. Ahora sé al instante quién me debe y qué
                            vence.”
                            <span>— Vendedor independiente, Santiago</span>
                        </div>
                    </div>
                </aside>

                {/* FORM */}
                <main className="auth-main">
                    <div className="auth-top">
                        <Link className="logo" href={home()}>
                            <StreamCrmLogo simple />
                        </Link>
                        <span className="alt">
                            ¿Necesitas una cuenta?{' '}
                            <a href="mailto:soporte@streamcrm.cl">
                                Contáctanos
                            </a>
                        </span>
                    </div>

                    <div className="auth-card">
                        <h1>Bienvenido de vuelta</h1>
                        <p className="sub">
                            Ingresa tus datos para entrar a tu panel.
                        </p>

                        {status && (
                            <div
                                className="strength-txt"
                                style={{ color: 'var(--ok)', marginTop: 16 }}
                            >
                                {status}
                            </div>
                        )}

                        <Form
                            action={AuthenticatedSessionController.store.url()}
                            method="post"
                            resetOnSuccess={['password']}
                            className="form"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div
                                        className={
                                            errors.email ? 'fld invalid' : 'fld'
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

                                    <div
                                        className={
                                            errors.password
                                                ? 'fld invalid'
                                                : 'fld'
                                        }
                                    >
                                        <div className="row-between">
                                            <label htmlFor="password">
                                                Contraseña
                                            </label>
                                            {canResetPassword && (
                                                <Link
                                                    className="link"
                                                    href={request()}
                                                >
                                                    ¿Olvidaste tu contraseña?
                                                </Link>
                                            )}
                                        </div>
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
                                                    x="4"
                                                    y="10"
                                                    width="16"
                                                    height="11"
                                                    rx="2.5"
                                                />
                                                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                            </svg>
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
                                            />
                                            <button
                                                type="button"
                                                className="toggle"
                                                aria-label="Mostrar contraseña"
                                                onClick={() =>
                                                    setShowPassword((v) => !v)
                                                }
                                            >
                                                {showPassword ? (
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="1.8"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    >
                                                        <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z" />
                                                        <circle
                                                            cx="12"
                                                            cy="12"
                                                            r="3"
                                                        />
                                                        <path d="m3 3 18 18" />
                                                    </svg>
                                                ) : (
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="1.8"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    >
                                                        <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z" />
                                                        <circle
                                                            cx="12"
                                                            cy="12"
                                                            r="3"
                                                        />
                                                    </svg>
                                                )}
                                            </button>
                                        </div>
                                        <span className="err">
                                            {errors.password ??
                                                'Ingresa tu contraseña'}
                                        </span>
                                    </div>

                                    <div className="row-between">
                                        <label className="check">
                                            <input
                                                type="checkbox"
                                                name="remember"
                                            />{' '}
                                            Mantener sesión iniciada
                                        </label>
                                    </div>

                                    <button
                                        type="submit"
                                        className="btn btn-brand btn-block btn-lg"
                                        disabled={processing}
                                        data-test="login-button"
                                    >
                                        {processing && (
                                            <span className="spin" />
                                        )}
                                        {processing
                                            ? 'Entrando…'
                                            : 'Entrar a mi panel'}
                                    </button>
                                </>
                            )}
                        </Form>

                        <p className="alt-foot">
                            ¿Problemas para entrar?{' '}
                            <a href="mailto:soporte@streamcrm.cl">Escríbenos</a>
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
