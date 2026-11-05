import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Barcode,
    Boxes,
    Building2,
    Calendar,
    Check,
    CheckCircle2,
    Clock,
    FileSpreadsheet,
    HelpCircle,
    Layers,
    Lock,
    Mail,
    Phone,
    ShieldCheck,
    ShoppingCart,
    Sparkles,
    User,
    Warehouse,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import StreamCrmLogo from '@/components/streamcrm-logo';
import { COUNTRY_CODES, DEFAULT_DIAL } from '@/lib/phone';
import { login } from '@/routes';

interface DemoPageProps {
    flash?: {
        success?: string | null;
    };
    [key: string]: unknown;
}

export default function DemoPage() {
    const { flash } = usePage<DemoPageProps>().props;
    const [submitted, setSubmitted] = useState(Boolean(flash?.success));

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        company_name: '',
        email: '',
        phone_prefix: DEFAULT_DIAL,
        phone: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Si se indicó nombre de empresa, se concatena amistosamente en el campo name
        const combinedName = data.company_name.trim()
            ? `${data.name.trim()} (${data.company_name.trim()})`
            : data.name.trim();

        post('/demo', {
            data: {
                name: combinedName,
                email: data.email.trim(),
                phone: `${data.phone_prefix} ${data.phone.trim()}`.trim(),
            },
            onSuccess: () => {
                setSubmitted(true);
                reset();
            },
        });
    };

    return (
        <>
            <Head title="Solicitar Demo — Valolabs ERP & Sistema de Inventario" />

            <div className="min-h-screen bg-slate-50 text-slate-900 transition-colors duration-200 dark:bg-zinc-950 dark:text-zinc-100">
                {/* AMBIENT BACKGROUND GLOW */}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 -z-10 overflow-hidden"
                >
                    <div className="absolute -top-40 left-1/2 h-[560px] w-[900px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-indigo-500/15 via-sky-400/10 to-transparent blur-3xl dark:from-indigo-600/20 dark:via-purple-500/10" />
                </div>

                {/* NAVBAR */}
                <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-900/80">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <Link href="/demo" className="inline-flex items-center">
                            <StreamCrmLogo />
                        </Link>

                        <nav className="hidden items-center gap-6 md:flex">
                            <a
                                href="#inventario"
                                className="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400"
                            >
                                Módulo de Inventario
                            </a>
                            <a
                                href="#flujo"
                                className="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400"
                            >
                                Cómo Funciona
                            </a>
                            <a
                                href="#preguntas"
                                className="text-sm font-medium text-slate-600 transition-colors hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400"
                            >
                                Preguntas
                            </a>
                        </nav>

                        <div className="flex items-center gap-3">
                            <Link
                                href={login()}
                                className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
                            >
                                Entrar a mi panel
                            </Link>
                            <a
                                href="#formulario"
                                className="inline-flex h-9 items-center justify-center rounded-lg bg-indigo-600 px-3.5 text-xs font-semibold text-white shadow-xs transition hover:bg-indigo-700 active:scale-95 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                            >
                                Solicitar Demo
                            </a>
                        </div>
                    </div>
                </header>

                <main>
                    {/* HERO & FORM SECTION */}
                    <section className="relative overflow-hidden pt-12 pb-20 sm:pt-16 sm:pb-28">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
                                {/* Left column: Presentation */}
                                <div className="lg:col-span-7">
                                    <div className="inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-50/80 px-3 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        <Sparkles className="size-3.5 text-indigo-600 dark:text-indigo-400" />
                                        <span>Demostración guiada sin compromiso</span>
                                    </div>

                                    <h1 className="mt-5 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-5xl sm:leading-[1.12] dark:text-white">
                                        Control total de tu{' '}
                                        <span className="bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500 bg-clip-text text-transparent dark:from-indigo-400 dark:via-sky-400 dark:to-blue-300">
                                            inventario
                                        </span>{' '}
                                        y ventas en tiempo real
                                    </h1>

                                    <p className="mt-5 text-base text-slate-600 sm:text-lg dark:text-zinc-300">
                                        Valolabs ERP conecta tus almacenes con el punto de venta y operaciones. Dile adiós a los faltantes misteriosos, errores en caja y ventas sin existencias físicas.
                                    </p>

                                    {/* Bullet points */}
                                    <div className="mt-8 grid gap-3 sm:grid-cols-2">
                                        <div className="flex items-start gap-2.5">
                                            <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                <Check className="size-3 stroke-[3]" />
                                            </div>
                                            <span className="text-sm font-medium text-slate-700 dark:text-zinc-300">
                                                <strong>Kardex inmutable</strong> con auditoría por usuario
                                            </span>
                                        </div>

                                        <div className="flex items-start gap-2.5">
                                            <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                <Check className="size-3 stroke-[3]" />
                                            </div>
                                            <span className="text-sm font-medium text-slate-700 dark:text-zinc-300">
                                                <strong>Multibodega</strong> y ubicaciones por pasillo/estante
                                            </span>
                                        </div>

                                        <div className="flex items-start gap-2.5">
                                            <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                <Check className="size-3 stroke-[3]" />
                                            </div>
                                            <span className="text-sm font-medium text-slate-700 dark:text-zinc-300">
                                                <strong>Trazabilidad por series</strong> (IMEI) y lotes
                                            </span>
                                        </div>

                                        <div className="flex items-start gap-2.5">
                                            <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                                <Check className="size-3 stroke-[3]" />
                                            </div>
                                            <span className="text-sm font-medium text-slate-700 dark:text-zinc-300">
                                                <strong>Sincronización POS</strong> y cobro multimoneda (USD/VES)
                                            </span>
                                        </div>
                                    </div>

                                    {/* Stats / trust ribbon */}
                                    <div className="mt-10 border-t border-slate-200/80 pt-6 dark:border-zinc-800">
                                        <div className="flex flex-wrap items-center gap-8">
                                            <div>
                                                <div className="text-2xl font-extrabold text-slate-900 dark:text-white">
                                                    100%
                                                </div>
                                                <div className="text-xs text-slate-500 dark:text-zinc-400">
                                                    Auditoría de movimientos
                                                </div>
                                            </div>
                                            <div className="h-8 w-px bg-slate-200 dark:bg-zinc-800" />
                                            <div>
                                                <div className="text-2xl font-extrabold text-slate-900 dark:text-white">
                                                    0
                                                </div>
                                                <div className="text-xs text-slate-500 dark:text-zinc-400">
                                                    Sobreventas accidentales
                                                </div>
                                            </div>
                                            <div className="h-8 w-px bg-slate-200 dark:bg-zinc-800" />
                                            <div>
                                                <div className="text-2xl font-extrabold text-slate-900 dark:text-white">
                                                    Tiempo real
                                                </div>
                                                <div className="text-xs text-slate-500 dark:text-zinc-400">
                                                    Descuento de stock en caja
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Right column: Form Card */}
                                <div id="formulario" className="lg:col-span-5">
                                    <div className="relative rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xl shadow-slate-200/50 sm:p-8 dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none">
                                        {submitted || flash?.success ? (
                                            <div className="py-8 text-center">
                                                <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                                                    <CheckCircle2 className="size-8" />
                                                </div>
                                                <h3 className="mt-4 text-xl font-bold text-slate-900 dark:text-white">
                                                    ¡Solicitud enviada con éxito!
                                                </h3>
                                                <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                                    {flash?.success ??
                                                        'Gracias por tu interés en Valolabs ERP. Un especialista se comunicará contigo vía WhatsApp o correo electrónico para coordinar tu acceso a la demo interactiva.'}
                                                </p>
                                                <div className="mt-6 flex flex-col gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setSubmitted(false)}
                                                        className="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                                                    >
                                                        Enviar otra solicitud
                                                    </button>
                                                    <Link
                                                        href={login()}
                                                        className="mt-2 inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 px-4 text-xs font-semibold text-white hover:bg-slate-800 dark:bg-zinc-800 dark:hover:bg-zinc-700"
                                                    >
                                                        Ir a iniciar sesión
                                                    </Link>
                                                </div>
                                            </div>
                                        ) : (
                                            <>
                                                <div className="mb-6">
                                                    <span className="text-xs font-semibold tracking-wider text-indigo-600 uppercase dark:text-indigo-400">
                                                        Paso 1 de 1
                                                    </span>
                                                    <h2 className="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                                                        Solicita tu acceso a la demo
                                                    </h2>
                                                    <p className="mt-1.5 text-xs text-slate-500 dark:text-zinc-400">
                                                        Llena tus datos y te preparamos un entorno de prueba configurado para tu tipo de negocio.
                                                    </p>
                                                </div>

                                                <form onSubmit={handleSubmit} className="space-y-4">
                                                    {/* Nombre */}
                                                    <div>
                                                        <label
                                                            htmlFor="name"
                                                            className="block text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                        >
                                                            Nombre y apellido <span className="text-rose-500">*</span>
                                                        </label>
                                                        <div className="relative mt-1.5">
                                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-zinc-500">
                                                                <User className="size-4" />
                                                            </div>
                                                            <input
                                                                id="name"
                                                                type="text"
                                                                required
                                                                value={data.name}
                                                                onChange={(e) => setData('name', e.target.value)}
                                                                placeholder="Ej. Carlos Mendoza"
                                                                className={`h-11 w-full rounded-xl border bg-slate-50/50 pl-9 pr-3 text-sm text-slate-900 transition-all outline-hidden placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-900 ${
                                                                    errors.name ? 'border-rose-500 ring-rose-500/10' : 'border-slate-200'
                                                                }`}
                                                            />
                                                        </div>
                                                        {errors.name && (
                                                            <p className="mt-1 text-xs text-rose-500">{errors.name}</p>
                                                        )}
                                                    </div>

                                                    {/* Empresa (opcional) */}
                                                    <div>
                                                        <label
                                                            htmlFor="company_name"
                                                            className="block text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                        >
                                                            Nombre de tu empresa o negocio <span className="text-xs font-normal text-slate-400 dark:text-zinc-500">(opcional)</span>
                                                        </label>
                                                        <div className="relative mt-1.5">
                                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-zinc-500">
                                                                <Building2 className="size-4" />
                                                            </div>
                                                            <input
                                                                id="company_name"
                                                                type="text"
                                                                value={data.company_name}
                                                                onChange={(e) => setData('company_name', e.target.value)}
                                                                placeholder="Ej. Distribuidora Los Andes C.A."
                                                                className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3 text-sm text-slate-900 transition-all outline-hidden placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-900"
                                                            />
                                                        </div>
                                                    </div>

                                                    {/* Correo */}
                                                    <div>
                                                        <label
                                                            htmlFor="email"
                                                            className="block text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                        >
                                                            Correo electrónico <span className="text-rose-500">*</span>
                                                        </label>
                                                        <div className="relative mt-1.5">
                                                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-zinc-500">
                                                                <Mail className="size-4" />
                                                            </div>
                                                            <input
                                                                id="email"
                                                                type="email"
                                                                required
                                                                value={data.email}
                                                                onChange={(e) => setData('email', e.target.value)}
                                                                placeholder="carlos@tuempresa.com"
                                                                className={`h-11 w-full rounded-xl border bg-slate-50/50 pl-9 pr-3 text-sm text-slate-900 transition-all outline-hidden placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-900 ${
                                                                    errors.email ? 'border-rose-500 ring-rose-500/10' : 'border-slate-200'
                                                                }`}
                                                            />
                                                        </div>
                                                        {errors.email && (
                                                            <p className="mt-1 text-xs text-rose-500">{errors.email}</p>
                                                        )}
                                                    </div>

                                                    {/* Teléfono / WhatsApp */}
                                                    <div>
                                                        <label
                                                            htmlFor="phone"
                                                            className="block text-xs font-semibold text-slate-700 dark:text-zinc-300"
                                                        >
                                                            Teléfono / WhatsApp <span className="text-rose-500">*</span>
                                                        </label>
                                                        <div className="mt-1.5 flex gap-2">
                                                            <select
                                                                value={data.phone_prefix}
                                                                onChange={(e) => setData('phone_prefix', e.target.value)}
                                                                className="h-11 w-32 shrink-0 rounded-xl border border-slate-200 bg-slate-50/50 px-2.5 text-xs font-medium text-slate-900 transition-all outline-hidden focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:focus:border-indigo-400"
                                                            >
                                                                {COUNTRY_CODES.map((c) => (
                                                                    <option key={`${c.name}-${c.dial}`} value={c.dial}>
                                                                        {c.name} ({c.dial})
                                                                    </option>
                                                                ))}
                                                            </select>

                                                            <div className="relative flex-1">
                                                                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 dark:text-zinc-500">
                                                                    <Phone className="size-4" />
                                                                </div>
                                                                <input
                                                                    id="phone"
                                                                    type="tel"
                                                                    required
                                                                    value={data.phone}
                                                                    onChange={(e) => setData('phone', e.target.value)}
                                                                    placeholder="412 123 4567"
                                                                    className={`h-11 w-full rounded-xl border bg-slate-50/50 pl-9 pr-3 text-sm text-slate-900 transition-all outline-hidden placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-zinc-700 dark:bg-zinc-800/60 dark:text-white dark:placeholder:text-zinc-500 dark:focus:border-indigo-400 dark:focus:bg-zinc-900 ${
                                                                        errors.phone ? 'border-rose-500 ring-rose-500/10' : 'border-slate-200'
                                                                    }`}
                                                                />
                                                            </div>
                                                        </div>
                                                        {errors.phone && (
                                                            <p className="mt-1 text-xs text-rose-500">{errors.phone}</p>
                                                        )}
                                                    </div>

                                                    {/* Submit button */}
                                                    <button
                                                        type="submit"
                                                        disabled={processing}
                                                        className="mt-2 flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 hover:shadow-md active:scale-[0.99] disabled:opacity-60 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                                                    >
                                                        {processing ? (
                                                            <span>Enviando solicitud…</span>
                                                        ) : (
                                                            <>
                                                                <span>Solicitar Acceso a la Demo</span>
                                                                <ArrowRight className="size-4" />
                                                            </>
                                                        )}
                                                    </button>

                                                    <p className="text-center text-[11px] text-slate-400 dark:text-zinc-500">
                                                        Tus datos están protegidos. No compartimos tu información con terceros.
                                                    </p>
                                                </form>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* INVENTORY CAPABILITIES SECTION */}
                    <section id="inventario" className="border-t border-slate-200/80 bg-white py-20 dark:border-zinc-800 dark:bg-zinc-900/50">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-3xl text-center">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    <Boxes className="size-3.5" />
                                    Módulo de Inventario Avanzado
                                </span>
                                <h2 className="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                                    ¿Por qué nuestro sistema de inventario es superior?
                                </h2>
                                <p className="mt-4 text-base text-slate-600 dark:text-zinc-400">
                                    Diseñado desde cero para empresas con movimientos constantes de mercancía, múltiples depósitos y exigencia estricta de auditoría.
                                </p>
                            </div>

                            <div className="mt-14 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                                {/* Feature 1: Kardex Inmutable */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20">
                                        <ShieldCheck className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Kardex Inmutable y Auditoría
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        La verdadera "caja negra" del inventario. Cada unidad que entra, sale o se ajusta queda firmada con fecha, hora, responsable y número de documento de respaldo. Imposible de manipular a mano.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        Trazabilidad total garantizada
                                    </div>
                                </div>

                                {/* Feature 2: Lotes y Vencimientos */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/20">
                                        <Calendar className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Lotes y Fechas de Caducidad
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        Control de lotes con fechas de vencimiento para alimentos, fármacos y cosméticos. Soporta despacho FIFO (primero en entrar, primero en salir) para evitar pérdidas por caducidad.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                                        Gestión FIFO / FEFO
                                    </div>
                                </div>

                                {/* Feature 3: Serialización / IMEI */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-sky-500 text-white shadow-md shadow-sky-500/20">
                                        <Barcode className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Series e IMEI Individuales
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        Generador de secuencias de series y trazabilidad unitaria para tecnología, teléfonos y maquinaria. Rastrea la vida útil, recepción, venta y reclamos de garantía por número de serie.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-sky-600 dark:text-sky-400">
                                        Garantías unitarias
                                    </div>
                                </div>

                                {/* Feature 4: Multibodega y Ubicaciones */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-600/20">
                                        <Warehouse className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Multibodega y Ubicaciones Físicas
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        Crea múltiples bodegas y sucursales. Dentro de cada bodega puedes subdividir el espacio en pasillos, estantes y compartimentos para agilizar los despachos y conteos físicos.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-violet-600 dark:text-violet-400">
                                        Transferencias guiadas
                                    </div>
                                </div>

                                {/* Feature 5: Físico vs Reservado */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/20">
                                        <Layers className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Existencia Disponible vs. Reservada
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        Cuando un vendedor emite una orden de venta, las unidades quedan automáticamente "Reservadas". El mostrador nunca venderá por error mercancía que ya fue pagada y espera empaque.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        Cero sobreventas
                                    </div>
                                </div>

                                {/* Feature 6: Conexión con POS & Multimoneda */}
                                <div className="group relative rounded-2xl border border-slate-200/80 bg-slate-50/50 p-6 transition-all hover:border-indigo-200 hover:bg-white hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/80">
                                    <div className="flex size-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-md shadow-rose-500/20">
                                        <ShoppingCart className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-bold text-slate-900 dark:text-white">
                                        Integración con POS Multimoneda
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-slate-600 dark:text-zinc-400">
                                        Venta ágil en terminal de caja que descuenta existencias al instante en la bodega configurada para esa caja. Soporta cobros combinados en USD y VES con conversión a tasa del día.
                                    </p>
                                    <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400">
                                        Sincronización en vivo
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* WORKFLOW SECTION */}
                    <section id="flujo" className="py-20">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                            <div className="mx-auto max-w-3xl text-center">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    Flujo Operativo
                                </span>
                                <h2 className="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                                    Cómo viaja la mercancía en Valolabs ERP
                                </h2>
                                <p className="mt-4 text-base text-slate-600 dark:text-zinc-400">
                                    Un ciclo limpio que garantiza que nada entre ni salga de tu empresa sin registro previo.
                                </p>
                            </div>

                            <div className="mt-14 grid gap-8 md:grid-cols-3">
                                <div className="relative rounded-2xl border border-slate-200/80 bg-white p-7 text-center dark:border-zinc-800 dark:bg-zinc-900">
                                    <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                        <span className="text-lg font-bold">1</span>
                                    </div>
                                    <h3 className="mt-4 text-lg font-bold text-slate-900 dark:text-white">
                                        Entrada de Mercancía
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        Recepción enlazada a la Orden de Compra. Se audita lo recibido, se asignan series o lotes y se incrementa el saldo en Kardex.
                                    </p>
                                </div>

                                <div className="relative rounded-2xl border border-slate-200/80 bg-white p-7 text-center dark:border-zinc-800 dark:bg-zinc-900">
                                    <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                        <span className="text-lg font-bold">2</span>
                                    </div>
                                    <h3 className="mt-4 text-lg font-bold text-slate-900 dark:text-white">
                                        Almacenaje y Ubicación
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        Los artículos se ubican por pasillo o estante. Si se trasladan a otra sucursal, se genera una transferencia con guía de transporte.
                                    </p>
                                </div>

                                <div className="relative rounded-2xl border border-slate-200/80 bg-white p-7 text-center dark:border-zinc-800 dark:bg-zinc-900">
                                    <div className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                                        <span className="text-lg font-bold">3</span>
                                    </div>
                                    <h3 className="mt-4 text-lg font-bold text-slate-900 dark:text-white">
                                        Venta y Despacho
                                    </h3>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        Al concretar la venta en caja o emitir la nota de entrega, las existencias se descuentan instantáneamente con respaldo en PDF.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* FAQ SECTION */}
                    <section id="preguntas" className="border-t border-slate-200/80 bg-slate-100/60 py-20 dark:border-zinc-800 dark:bg-zinc-900/30">
                        <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                            <div className="text-center">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                    <HelpCircle className="size-3.5" />
                                    Preguntas Frecuentes
                                </span>
                                <h2 className="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                                    Todo lo que necesitas saber sobre la demo
                                </h2>
                            </div>

                            <div className="mt-12 space-y-4">
                                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">
                                        ¿La demo tiene costo o compromiso de compra?
                                    </h4>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        No. Te damos acceso completo al entorno de prueba para que explores todas las funciones de inventario, ventas, caja y reportes a tu propio ritmo.
                                    </p>
                                </div>

                                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">
                                        ¿Puedo importar mi catálogo actual desde Excel?
                                    </h4>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        Sí. El sistema cuenta con importadores masivos en Excel para artículos, clientes, proveedores y saldos iniciales de inventario.
                                    </p>
                                </div>

                                <div className="rounded-2xl border border-slate-200/80 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
                                    <h4 className="text-base font-bold text-slate-900 dark:text-white">
                                        ¿Funciona en la nube o en servidor local?
                                    </h4>
                                    <p className="mt-2 text-sm text-slate-600 dark:text-zinc-400">
                                        Valolabs ERP funciona 100% en la nube (accesible desde cualquier laptop, tablet o celular) o mediante instalación en servidor local para empresas con redes internas dedicadas.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* BOTTOM CALL TO ACTION */}
                    <section className="relative overflow-hidden bg-indigo-600 py-16 text-white dark:bg-indigo-700">
                        <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                            <h2 className="text-3xl font-extrabold sm:text-4xl">
                                ¿Listo para ver Valolabs ERP en acción?
                            </h2>
                            <p className="mx-auto mt-4 max-w-2xl text-base text-indigo-100 sm:text-lg">
                                Solicita tu acceso hoy y descubre cómo simplificar tus conteos, evitar pérdidas de mercancía y acelerar tus ventas.
                            </p>
                            <div className="mt-8 flex justify-center gap-4">
                                <a
                                    href="#formulario"
                                    className="inline-flex h-12 items-center justify-center rounded-xl bg-white px-6 text-sm font-bold text-indigo-600 shadow-md transition hover:bg-slate-50 active:scale-95"
                                >
                                    Solicitar acceso ahora
                                </a>
                            </div>
                        </div>
                    </section>
                </main>

                {/* FOOTER */}
                <footer className="border-t border-slate-200/80 bg-white py-12 dark:border-zinc-800 dark:bg-zinc-950">
                    <div className="mx-auto flex max-w-7xl flex-col items-center justify-between gap-6 px-4 text-center sm:flex-row sm:px-6 sm:text-left lg:px-8">
                        <div className="flex items-center gap-2">
                            <StreamCrmLogo simple />
                        </div>
                        <p className="text-xs text-slate-500 dark:text-zinc-500">
                            © {new Date().getFullYear()} Valolabs ERP. Todos los derechos reservados.
                        </p>
                        <div className="flex items-center gap-4 text-xs font-semibold text-slate-600 dark:text-zinc-400">
                            <Link href={login()} className="hover:text-indigo-600 dark:hover:text-indigo-400">
                                Iniciar sesión
                            </Link>
                            <span className="text-slate-300 dark:text-zinc-700">•</span>
                            <a href="mailto:hola@valolabs.site" className="hover:text-indigo-600 dark:hover:text-indigo-400">
                                Soporte
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
