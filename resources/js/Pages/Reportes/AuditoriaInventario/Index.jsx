import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Search, X, ShieldCheck } from 'lucide-react';
import { useMemo, useState } from 'react';

const number = (value) => {
    if (value === null || value === undefined || value === '') return '-';
    return Number(value).toFixed(2);
};

const label = (value) => String(value ?? '-')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());

export default function Index({ auth, auditorias = [], productos = [], almacenes = [], filters = {}, tipos = [], acciones = [] }) {
    const [form, setForm] = useState({
        buscar: filters.buscar ?? '',
        producto_id: filters.producto_id ?? '',
        almacen_id: filters.almacen_id ?? '',
        tipo: filters.tipo ?? '',
        accion: filters.accion ?? '',
        desde: filters.desde ?? '',
        hasta: filters.hasta ?? '',
    });

    const totalRegistros = useMemo(() => auditorias.length, [auditorias]);

    const submit = (event) => {
        event.preventDefault();
        router.get(route('reportes.auditoria-inventario.index'), form, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clear = () => {
        const empty = { buscar: '', producto_id: '', almacen_id: '', tipo: '', accion: '', desde: '', hasta: '' };
        setForm(empty);
        router.get(route('reportes.auditoria-inventario.index'), empty, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout
            user={auth?.user}
            header={
                <div>
                    <h2 className="text-2xl font-bold text-slate-950">Auditoria de Inventario</h2>
                    <p className="text-sm text-slate-500">Trazabilidad de stock, kardex, anulaciones y movimientos.</p>
                </div>
            }
        >
            <Head title="Auditoria de Inventario" />

            <div className="w-full px-2 py-8 sm:px-3 lg:px-4">
                <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-slate-200 px-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-950">Auditoria de Inventario</h1>
                            <p className="text-slate-500">Consulta stock antes/despues, usuario, documento y motivo.</p>
                        </div>
                        <div className="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">
                            <ShieldCheck className="h-5 w-5 text-emerald-600" />
                            {totalRegistros} registros
                        </div>
                    </div>

                    <form onSubmit={submit} className="grid gap-3 border-b border-slate-200 px-4 py-5 xl:grid-cols-7">
                        <div className="xl:col-span-2">
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Buscar</label>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={form.buscar}
                                    onChange={(event) => setForm({ ...form, buscar: event.target.value })}
                                    className="w-full rounded-lg border border-slate-300 py-3 pl-10 pr-3 text-slate-900 outline-none focus:border-slate-900"
                                    placeholder="Documento, producto, usuario..."
                                />
                            </div>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Producto</label>
                            <select
                                value={form.producto_id}
                                onChange={(event) => setForm({ ...form, producto_id: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900 outline-none focus:border-slate-900"
                            >
                                <option value="">Todos</option>
                                {productos.map((producto) => (
                                    <option key={producto.id} value={producto.id}>{producto.nombre}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Almacen</label>
                            <select
                                value={form.almacen_id}
                                onChange={(event) => setForm({ ...form, almacen_id: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900 outline-none focus:border-slate-900"
                            >
                                <option value="">Todos</option>
                                {almacenes.map((almacen) => (
                                    <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Tipo</label>
                            <select
                                value={form.tipo}
                                onChange={(event) => setForm({ ...form, tipo: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900 outline-none focus:border-slate-900"
                            >
                                <option value="">Todos</option>
                                {tipos.map((tipo) => (
                                    <option key={tipo} value={tipo}>{label(tipo)}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Desde</label>
                            <input
                                type="date"
                                value={form.desde}
                                onChange={(event) => setForm({ ...form, desde: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900 outline-none focus:border-slate-900"
                            />
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Hasta</label>
                            <input
                                type="date"
                                value={form.hasta}
                                onChange={(event) => setForm({ ...form, hasta: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 text-slate-900 outline-none focus:border-slate-900"
                            />
                        </div>

                        <div className="flex items-end gap-2 xl:col-span-7">
                            <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-3 font-semibold text-white">
                                <Search className="h-5 w-5" />
                                Buscar
                            </button>
                            <button type="button" onClick={clear} className="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-600">
                                <X className="h-5 w-5" />
                                Limpiar
                            </button>
                        </div>
                    </form>

                    <div className="px-4 py-5">
                        <div className="overflow-x-auto rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left text-sm">
                                <thead className="bg-slate-50 text-xs font-semibold uppercase text-slate-600">
                                    <tr>
                                        <th className="w-28 px-2 py-4">Fecha</th>
                                        <th className="w-28 px-2 py-4">Accion</th>
                                        <th className="w-24 px-2 py-4">Tipo</th>
                                        <th className="px-2 py-4">Producto</th>
                                        <th className="w-36 px-2 py-4">Almacen</th>
                                        <th className="w-20 px-2 py-4 text-right">Antes</th>
                                        <th className="w-20 px-2 py-4 text-right">Cant.</th>
                                        <th className="w-20 px-2 py-4 text-right">Despues</th>
                                        <th className="w-36 px-2 py-4">Documento</th>
                                        <th className="w-32 px-2 py-4">Usuario</th>
                                        <th className="w-32 px-2 py-4">Motivo</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {auditorias.length === 0 ? (
                                        <tr>
                                            <td colSpan="11" className="px-4 py-10 text-center text-slate-500">
                                                No hay registros de auditoria para los filtros seleccionados.
                                            </td>
                                        </tr>
                                    ) : auditorias.map((row) => (
                                        <tr key={row.id} className="text-slate-900">
                                            <td className="whitespace-nowrap px-2 py-4">{row.fecha}</td>
                                            <td className="px-2 py-4">
                                                <span className="rounded-full bg-sky-100 px-2 py-1 text-[11px] font-semibold text-sky-700">
                                                    {label(row.accion)}
                                                </span>
                                            </td>
                                            <td className="truncate px-2 py-4">{label(row.tipo)}</td>
                                            <td className="truncate px-2 py-4 font-semibold" title={row.producto}>{row.producto}</td>
                                            <td className="truncate px-2 py-4" title={row.almacen}>{row.almacen}</td>
                                            <td className="px-2 py-4 text-right">{number(row.stock_antes)}</td>
                                            <td className="px-2 py-4 text-right font-semibold">{number(row.cantidad)}</td>
                                            <td className="px-2 py-4 text-right">{number(row.stock_despues)}</td>
                                            <td className="truncate px-2 py-4 font-mono text-xs text-blue-700" title={row.documento}>{row.documento}</td>
                                            <td className="truncate px-2 py-4" title={row.usuario}>{row.usuario}</td>
                                            <td className="truncate px-2 py-4 text-slate-600" title={row.motivo}>{row.motivo}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
