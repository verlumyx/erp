import { Form, Head, Link, usePage } from '@inertiajs/react';
import LeadPostController from '@/actions/App/Modules/Lead/Controllers/LeadPostController';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { COUNTRY_CODES, DEFAULT_DIAL } from '@/lib/phone';
import { home, login } from '@/routes';

interface ContactPageProps {
    flash?: {
        success?: string | null;
    };
    [key: string]: unknown;
}

export default function Contact() {
    const { flash } = usePage<ContactPageProps>().props;
    const sent = Boolean(flash?.success);

    return (
        <>
            <Head title="Contáctenos — StreamCRM">
                <link rel="stylesheet" href="/css/site.css" />
            </Head>

            <div className="auth">
                <aside className="auth-brand">
                    <Link className="logo" href={home()}>
                        <StreamCrmLogo />
                    </Link>
                    <div className="auth-brand-body">
                        <h2>Hablemos de tu negocio.</h2>
                        <p>
                            Déjanos tus datos y te contactamos para mostrarte
                            cómo StreamCRM ordena tus clientes, perfiles y
                            cobros en un solo lugar.
                        </p>
                        <div className="auth-quote">
                            “Me escribieron al día siguiente y en una llamada ya
                            tenía todo claro.”
                            <span>— Revendedor Pro</span>
                        </div>
                    </div>
                </aside>

                <main className="auth-main">
                    <div className="auth-top">
                        <Link className="auth-back" href={home()}>
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
                            Volver al inicio
                        </Link>
                        <Link className="logo" href={home()}>
                            <StreamCrmLogo simple />
                        </Link>
                    </div>

                    <div className="auth-card">
                        {!sent ? (
                            <div>
                                <h1>Contáctenos</h1>
                                <p className="sub">
                                    Cuéntanos quién eres y cómo ubicarte. Te
                                    responderemos a la brevedad.
                                </p>

                                <Form
                                    action={LeadPostController.url()}
                                    method="post"
                                    resetOnSuccess
                                    transform={(data) => ({
                                        ...data,
                                        phone: `${data.phone_prefix ?? ''} ${data.phone ?? ''}`.trim(),
                                    })}
                                    className="form"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div
                                                className={
                                                    errors.name
                                                        ? 'fld invalid'
                                                        : 'fld'
                                                }
                                            >
                                                <label htmlFor="name">
                                                    Nombre
                                                </label>
                                                <div className="ctrl">
                                                    <input
                                                        id="name"
                                                        type="text"
                                                        name="name"
                                                        placeholder="Tu nombre"
                                                        autoComplete="name"
                                                        autoFocus
                                                        required
                                                    />
                                                </div>
                                                <span className="err">
                                                    {errors.name ??
                                                        'Ingresa tu nombre'}
                                                </span>
                                            </div>

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
                                                    <input
                                                        id="email"
                                                        type="email"
                                                        name="email"
                                                        placeholder="tu@correo.com"
                                                        autoComplete="email"
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
                                                    errors.phone
                                                        ? 'fld invalid'
                                                        : 'fld'
                                                }
                                            >
                                                <label htmlFor="phone">
                                                    Teléfono
                                                </label>
                                                <div
                                                    className="ctrl"
                                                    style={{ gap: 10 }}
                                                >
                                                    <select
                                                        name="phone_prefix"
                                                        defaultValue={
                                                            DEFAULT_DIAL
                                                        }
                                                        aria-label="Prefijo de país"
                                                        style={{
                                                            flex: '0 0 auto',
                                                            height: 50,
                                                            borderRadius: 12,
                                                            border: '1.5px solid var(--border-2)',
                                                            background:
                                                                'transparent',
                                                            padding: '0 10px',
                                                            fontSize: 15,
                                                        }}
                                                    >
                                                        {COUNTRY_CODES.map(
                                                            (c) => (
                                                                <option
                                                                    key={c.name}
                                                                    value={
                                                                        c.dial
                                                                    }
                                                                >
                                                                    {c.name} (
                                                                    {c.dial})
                                                                </option>
                                                            ),
                                                        )}
                                                    </select>
                                                    <input
                                                        id="phone"
                                                        type="tel"
                                                        name="phone"
                                                        placeholder="412 1234567"
                                                        autoComplete="tel-national"
                                                        required
                                                        style={{
                                                            flex: 1,
                                                            minWidth: 0,
                                                        }}
                                                    />
                                                </div>
                                                <span className="err">
                                                    {errors.phone ??
                                                        'Ingresa tu teléfono'}
                                                </span>
                                            </div>

                                            <button
                                                type="submit"
                                                className="btn btn-brand btn-block btn-lg"
                                                disabled={processing}
                                            >
                                                {processing && (
                                                    <span className="spin" />
                                                )}
                                                {processing
                                                    ? 'Enviando…'
                                                    : 'Enviar'}
                                            </button>
                                        </>
                                    )}
                                </Form>

                                <p className="alt-foot">
                                    ¿Ya tienes cuenta?{' '}
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
                                <h1>¡Gracias por contactarnos!</h1>
                                <p className="sub">{flash?.success}</p>
                                <Link
                                    className="btn btn-ghost btn-block"
                                    href={home()}
                                    style={{ marginTop: 26 }}
                                >
                                    Volver al inicio
                                </Link>
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
