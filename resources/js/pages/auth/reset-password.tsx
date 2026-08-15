import { Form, Head, Link } from '@inertiajs/react';
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
    { key: 'num', label: 'Un número', test: (v: string) => /[0-9]/.test(v) },
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
            <Head title="Restablecer contraseña — StreamCRM">
                <link rel="stylesheet" href="/css/site.css" />
            </Head>

            <div className="auth">
                <aside className="auth-brand">
                    <Link className="logo" href={home()}>
                        <StreamCrmLogo />
                    </Link>
                    <div className="auth-brand-body">
                        <h2>Crea una contraseña nueva y segura.</h2>
                        <p>
                            Elige una clave fuerte para proteger tus clientes,
                            cuentas y cobros. Después podrás entrar con
                            normalidad.
                        </p>
                        <div className="auth-quote">
                            “Mis datos de clientes son mi negocio. Me importa
                            que estén bien protegidos.”
                            <span>— Revendedora, Concepción</span>
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
                                    x="4"
                                    y="10"
                                    width="16"
                                    height="11"
                                    rx="2.5"
                                />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                <path d="M12 14.5v2.5" />
                            </svg>
                        </div>
                        <h1>Crea tu nueva contraseña</h1>
                        <p className="sub">
                            Debe ser distinta a las anteriores. Te recomendamos
                            una clave fuerte.
                        </p>

                        <Form
                            action={NewPasswordController.store.url()}
                            method="post"
                            transform={(data) => ({ ...data, token, email })}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            className="form"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div
                                        className={
                                            errors.password
                                                ? 'fld invalid'
                                                : 'fld'
                                        }
                                    >
                                        <label htmlFor="password">
                                            Nueva contraseña
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
                                                autoComplete="new-password"
                                                autoFocus
                                                required
                                                value={password}
                                                onChange={(e) =>
                                                    setPassword(e.target.value)
                                                }
                                            />
                                            <button
                                                type="button"
                                                className="toggle"
                                                aria-label="Mostrar contraseña"
                                                onClick={() =>
                                                    setShowPassword((v) => !v)
                                                }
                                            >
                                                <EyeIcon off={showPassword} />
                                            </button>
                                        </div>
                                        <div
                                            className={`strength s${password ? score : 0}`}
                                        >
                                            <div className="strength-bars">
                                                <i />
                                                <i />
                                                <i />
                                                <i />
                                            </div>
                                        </div>
                                        <div className="strength-txt">
                                            {password
                                                ? `Seguridad: ${STRENGTH_LABELS[score]}`
                                                : 'Usa 8+ caracteres con mayúsculas, números y símbolos'}
                                        </div>
                                        {errors.password && (
                                            <span
                                                className="err"
                                                style={{ display: 'block' }}
                                            >
                                                {errors.password}
                                            </span>
                                        )}
                                    </div>

                                    <div
                                        className={
                                            mismatch ||
                                            errors.password_confirmation
                                                ? 'fld invalid'
                                                : 'fld'
                                        }
                                    >
                                        <label htmlFor="password_confirmation">
                                            Confirmar contraseña
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
                                                    x="4"
                                                    y="10"
                                                    width="16"
                                                    height="11"
                                                    rx="2.5"
                                                />
                                                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                            </svg>
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
                                            />
                                            <button
                                                type="button"
                                                className="toggle"
                                                aria-label="Mostrar contraseña"
                                                onClick={() =>
                                                    setShowConfirm((v) => !v)
                                                }
                                            >
                                                <EyeIcon off={showConfirm} />
                                            </button>
                                        </div>
                                        <span className="err">
                                            {errors.password_confirmation ??
                                                'Las contraseñas no coinciden'}
                                        </span>
                                    </div>

                                    <ul className="reqs">
                                        {REQS.map((r) => (
                                            <li
                                                key={r.key}
                                                className={
                                                    met.find(
                                                        (m) => m.key === r.key,
                                                    )?.ok
                                                        ? 'met'
                                                        : undefined
                                                }
                                            >
                                                <span className="rk">
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        strokeWidth="3"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    >
                                                        <path d="M20 6 9 17l-5-5" />
                                                    </svg>
                                                </span>{' '}
                                                {r.label}
                                            </li>
                                        ))}
                                    </ul>

                                    <button
                                        type="submit"
                                        className="btn btn-brand btn-block btn-lg"
                                        disabled={processing}
                                        data-test="reset-password-button"
                                    >
                                        {processing && (
                                            <span className="spin" />
                                        )}
                                        {processing
                                            ? 'Guardando…'
                                            : 'Guardar contraseña'}
                                    </button>
                                </>
                            )}
                        </Form>
                    </div>
                </main>
            </div>
        </>
    );
}

function EyeIcon({ off }: { off: boolean }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z" />
            <circle cx="12" cy="12" r="3" />
            {off && <path d="m3 3 18 18" />}
        </svg>
    );
}
