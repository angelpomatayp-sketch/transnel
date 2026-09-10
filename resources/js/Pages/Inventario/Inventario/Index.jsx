import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Boxes, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
const qty = (value) => Number(value || 0).toFixed(2);

const estadoClass = {
    normal: 'bg-emerald-100 text-emerald-700',
    bajo: 'bg-amber-100 text-amber-700',
    sin_stock: 'bg-red-100 text-red-700',
};

const estadoText = {
    normal: 'Normal',
    bajo: 'Stock bajo',
    sin_stock: 'Sin stock',
};

export default function Index({ auth, inventario = [], almacenes = [], familias = [], filters = {}, scope = {}, totales = {} }) {
    const [form, setForm] = useState({
        buscar: filters.buscar ?? '',
        almacen_id: filters.almacen_id ?? '',
        familia_id: filters.familia_id ?? '',
        estado_stock: filters.estado_stock ?? '',
    });
    const inventarioRows = inventario.data ?? inventario;
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(route('inventario.inventario.index'), form, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [form]);

    return (
        <AuthenticatedLayout
            user={auth?.user}
            header={
                <div>
                    <h2 className="text-2xl font-bold text-slate-950">Inventario</h2>
                    <p className="text-sm text-slate-500">
                        {scope.es_almacenero ? 'Existencias de tu almacen asignado.' : 'Existencias generales por almacen.'}
                    </p>
                </div>
            }
        >
            <Head title="Inventario" />

            <div className="-m-2 w-[calc(100%+1rem)] lg:-m-4 lg:w-[calc(100%+2rem)]">
                <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-slate-200 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-950">Inventario actual</h1>
                            <p className="text-slate-500">
                                Stock disponible, costo promedio y valor por producto y almacen.
                            </p>
                        </div>
                        <div className="grid grid-cols-2 gap-2 text-sm lg:grid-cols-5">
                            <Metric label="Productos" value={totales.productos ?? 0} />
                            <Metric label="Existencia" value={qty(totales.existencia)} />
                            <Metric label="Valor" value={money(totales.valor)} />
                            <Metric label="Bajo" value={totales.stock_bajo ?? 0} />
                            <Metric label="Sin stock" value={totales.sin_stock ?? 0} />
                        </div>
                    </div>

                    <div className="grid gap-3 border-b border-slate-200 px-4 py-4 lg:grid-cols-5">
                        <div className="lg:col-span-2">
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Buscar</label>
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                <input
                                    value={form.buscar}
                                    onChange={(event) => setForm({ ...form, buscar: event.target.value })}
                                    className="w-full rounded-lg border border-slate-300 py-3 pl-10 pr-3 outline-none focus:border-slate-900"
                                    placeholder="Codigo, producto, familia..."
                                />
                            </div>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Almacen</label>
                            <select
                                value={form.almacen_id ?? ''}
                                disabled={scope.es_almacenero}
                                onChange={(event) => setForm({ ...form, almacen_id: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-slate-900 disabled:bg-slate-100"
                            >
                                <option value="">Todos</option>
                                {almacenes.map((almacen) => (
                                    <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Familia</label>
                            <select
                                value={form.familia_id ?? ''}
                                onChange={(event) => setForm({ ...form, familia_id: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-slate-900"
                            >
                                <option value="">Todas</option>
                                {familias.map((familia) => (
                                    <option key={familia.id} value={familia.id}>{familia.nombre}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Estado stock</label>
                            <select
                                value={form.estado_stock ?? ''}
                                onChange={(event) => setForm({ ...form, estado_stock: event.target.value })}
                                className="w-full rounded-lg border border-slate-300 px-3 py-3 outline-none focus:border-slate-900"
                            >
                                <option value="">Todos</option>
                                <option value="normal">Normal</option>
                                <option value="bajo">Stock bajo</option>
                                <option value="sin_stock">Sin stock</option>
                            </select>
                        </div>
                    </div>

                    <div className="px-3 py-4 sm:px-4">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left text-xs 2xl:text-sm">
                                <colgroup>
                                    {!scope.es_almacenero && <col className="w-[13%]" />}
                                    <col className={scope.es_almacenero ? 'w-[30%]' : 'w-[25%]'} />
                                    <col className={scope.es_almacenero ? 'w-[18%]' : 'w-[17%]'} />
                                    <col className="w-[6%]" />
                                    <col className="w-[8%]" />
                                    <col className="w-[7%]" />
                                    <col className="w-[9%]" />
                                    <col className="w-[7%]" />
                                    <col className="w-[7%]" />
                                </colgroup>
                                <thead className="bg-slate-50 text-xs font-semibold uppercase text-slate-600">
                                    <tr>
                                        {!scope.es_almacenero && <th className="truncate px-3 py-4">Almacen</th>}
                                        <th className="truncate px-3 py-4">Producto</th>
                                        <th className="truncate px-3 py-4">Familia</th>
                                        <th className="truncate px-3 py-4">Unidad</th>
                                        <th className="truncate px-3 py-4 text-right">Existencia</th>
                                        <th className="truncate px-3 py-4 text-right">Minimo</th>
                                        <th className="truncate px-3 py-4 text-right">Costo prom.</th>
                                        <th className="truncate px-3 py-4 text-right">Valor</th>
                                        <th className="whitespace-nowrap px-3 py-4">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {inventarioRows.length === 0 ? (
                                        <tr>
                                            <td colSpan={scope.es_almacenero ? 8 : 9} className="px-4 py-12 text-center text-slate-500">
                                                <Boxes className="mx-auto mb-3 h-8 w-8 text-slate-400" />
                                                No hay existencias para los filtros seleccionados.
                                            </td>
                                        </tr>
                                    ) : inventarioRows.map((row) => (
                                        <tr key={`${row.almacen_id}-${row.producto_id}`} className="text-slate-900">
                                            {!scope.es_almacenero && <td className="truncate px-3 py-4" title={row.almacen}>{row.almacen}</td>}
                                            <td className="truncate px-3 py-4 font-semibold" title={`${row.codigo} - ${row.producto}`}>{row.producto}</td>
                                            <td className="truncate px-3 py-4" title={row.familia}>{row.familia}</td>
                                            <td className="truncate px-3 py-4" title={row.unidad}>{row.unidad}</td>
                                            <td className="truncate px-3 py-4 text-right font-semibold">{qty(row.existencia)}</td>
                                            <td className="truncate px-3 py-4 text-right">{qty(row.stock_minimo)}</td>
                                            <td className="truncate px-3 py-4 text-right">{money(row.costo_promedio)}</td>
                                            <td className="truncate px-3 py-4 text-right font-semibold">{money(row.valor)}</td>
                                            <td className="px-3 py-4">
                                                <span className={`inline-flex whitespace-nowrap rounded-full px-3 py-1 text-[11px] font-semibold ${estadoClass[row.estado_stock] ?? estadoClass.normal}`}>
                                                    {estadoText[row.estado_stock] ?? 'Normal'}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={inventario} />
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ label, value }) {
    return (
        <div className="rounded-lg bg-slate-100 px-4 py-3">
            <div className="text-xs font-semibold uppercase text-slate-500">{label}</div>
            <div className="mt-1 whitespace-nowrap font-bold text-slate-950">{value}</div>
        </div>
    );
}
