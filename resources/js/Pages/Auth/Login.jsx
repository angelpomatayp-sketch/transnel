import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';
import { LockKeyhole, Mail } from 'lucide-react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Iniciar sesion" />

            <main className="flex min-h-screen items-center justify-center bg-slate-100 px-5 py-8 text-slate-900">
                <div className="grid w-full max-w-5xl overflow-hidden rounded-lg border border-slate-200 bg-white shadow-xl shadow-slate-200/70 lg:grid-cols-[0.9fr_1.1fr]">
                    <section className="flex items-center justify-center border-b border-slate-200 bg-white p-8 lg:border-b-0 lg:border-r lg:p-10">
                        <img src="/logo.jpeg" alt="Logistica DYM" className="h-52 w-52 object-contain sm:h-64 sm:w-64 lg:h-80 lg:w-80" />
                    </section>

                    <section className="p-7 sm:p-10">
                        <div className="mb-7">
                            <p className="text-sm font-semibold uppercase tracking-[0.18em] text-accent-600">Bienvenido</p>
                            <h2 className="mt-2 text-3xl font-bold text-slate-950">Iniciar sesion</h2>
                            <p className="mt-2 text-sm text-slate-500">Ingresa con tu usuario autorizado para continuar.</p>
                        </div>

                        {status && (
                            <div className="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-5">
                            <div>
                                <label htmlFor="email" className="mb-2 block text-sm font-bold text-slate-700">Correo electronico</label>
                                <div className="relative">
                                    <Mail className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        autoComplete="username"
                                        autoFocus
                                        onChange={(event) => setData('email', event.target.value)}
                                        className="h-12 w-full rounded-lg border border-slate-300 bg-white pl-12 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-500 focus:ring-4 focus:ring-accent-100"
                                        placeholder="usuario@empresa.com"
                                    />
                                </div>
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <label htmlFor="password" className="mb-2 block text-sm font-bold text-slate-700">Contrasena</label>
                                <div className="relative">
                                    <LockKeyhole className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        autoComplete="current-password"
                                        onChange={(event) => setData('password', event.target.value)}
                                        className="h-12 w-full rounded-lg border border-slate-300 bg-white pl-12 pr-4 text-sm font-medium text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-accent-500 focus:ring-4 focus:ring-accent-100"
                                        placeholder="Ingresa tu contrasena"
                                    />
                                </div>
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between gap-4">
                                <label className="flex items-center gap-2 text-sm font-medium text-slate-600">
                                    <Checkbox
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(event) => setData('remember', event.target.checked)}
                                    />
                                    Recordarme
                                </label>

                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-sm font-semibold text-brand-500 underline-offset-4 hover:text-brand-700 hover:underline"
                                    >
                                        Olvide mi contrasena
                                    </Link>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex h-12 w-full items-center justify-center rounded-lg bg-brand-500 px-5 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Ingresando...' : 'Ingresar'}
                            </button>
                        </form>
                    </section>

                </div>
            </main>
        </>
    );
}
