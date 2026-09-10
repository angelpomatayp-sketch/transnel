import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { FileDown, Search, X } from 'lucide-react';
import { useState } from 'react';

const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value ?? 0));
const number = (value) => new Intl.NumberFormat('es-PE', { maximumFractionDigits: 2 }).format(Number(value ?? 0));

function formatValue(value, index, columns) {
    const column = String(columns[index] ?? '').toLowerCase();

    if (column.includes('valor') || column.includes('costo')) {
        return money(value);
    }

    if (typeof value === 'number') {
        return number(value);
    }

    return value ?? '-';
}

function SummaryCard({ item }) {
    const tones = {
        green: 'bg-emerald-50 text-emerald-700',
        red: 'bg-red-50 text-red-700',
        blue: 'bg-blue-50 text-blue-700',
    };

    return (
        <div className={`rounded-lg px-5 py-6 text-center ${tones[item.tone] ?? 'bg-slate-50 text-slate-700'}`}>
            <p className="text-sm font-semibold">{item.label}</p>
            <p className="mt-2 text-2xl font-bold">{item.type === 'money' ? money(item.value) : number(item.value)}</p>
        </div>
    );
}

export default function Index({ scope = {}, tabs = {}, filters = {}, selectors = {}, report = {} }) {
    const [form, setForm] = useState({
        tipo: filters.tipo ?? 'kardex',
        buscar: filters.buscar ?? '',
        producto_id: filters.producto_id ?? '',
        almacen_id: filters.almacen_id ?? '',
        centro_costo_id: filters.centro_costo_id ?? '',
        desde: filters.desde ?? '',
        hasta: filters.hasta ?? '',
    });

    const columns = report.columns ?? [];
    const rows = report.rows ?? [];
    const summary = report.summary ?? [];
    const query = new URLSearchParams(Object.fromEntries(Object.entries(form).filter(([, value]) => value !== '' && value !== null && value !== undefined))).toString();
    const pdfUrl = `${route('reportes.logistico.pdf')}?${query}`;

    function apply(next = form) {
        router.get(route('reportes.logistico.index'), next, { preserveScroll: true, preserveState: true, replace: true });
    }

    function selectTab(tipo) {
        const next = { ...form, tipo };
        setForm(next);
        apply(next);
    }

    function submit(event) {
        event.preventDefault();
        apply();
    }

    function clear() {
        const next = { tipo: form.tipo, buscar: '', producto_id: '', almacen_id: '', centro_costo_id: '', desde: '', hasta: '' };
        setForm(next);
        apply(next);
    }

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold text-slate-950">Reportes</h2>
                    <p className="text-sm text-slate-500">Genera y exporta reportes del sistema - {scope.titulo}</p>
                </div>
            }
        >
            <Head title="Reportes" />

            <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-wrap border-b border-slate-200">
                    {Object.entries(tabs).map(([key, label]) => (
                        <button
                            key={key}
                            type="button"
                            onClick={() => selectTab(key)}
                            className={`border-b-2 px-5 py-4 text-sm font-bold transition ${
                                form.tipo === key
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-900'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                <form onSubmit={submit} className="grid gap-4 border-b border-slate-200 p-5 xl:grid-cols-10">
                    <div className="xl:col-span-2">
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Producto</label>
                        <select value={form.producto_id} onChange={(event) => setForm({ ...form, producto_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm">
                            <option value="">Todos los productos</option>
                            {(selectors.productos ?? []).map((item) => <option key={item.id} value={item.id}>{item.codigo} - {item.nombre}</option>)}
                        </select>
                    </div>

                    <div className="xl:col-span-2">
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Almacen</label>
                        <select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm">
                            <option value="">Todos los almacenes</option>
                            {(selectors.almacenes ?? []).map((item) => <option key={item.id} value={item.id}>{item.nombre}</option>)}
                        </select>
                    </div>

                    <div className="xl:col-span-2">
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Centro de costo</label>
                        <select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm">
                            <option value="">Todos los centros</option>
                            {(selectors.centros ?? []).map((item) => <option key={item.id} value={item.id}>{item.codigo} - {item.nombre}</option>)}
                        </select>
                    </div>

                    <div className="xl:col-span-2">
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Buscar</label>
                        <input value={form.buscar} onChange={(event) => setForm({ ...form, buscar: event.target.value })} placeholder="Buscar..." className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm" />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Fecha Inicio</label>
                        <input type="date" value={form.desde} onChange={(event) => setForm({ ...form, desde: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm" />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Fecha Fin</label>
                        <input type="date" value={form.hasta} onChange={(event) => setForm({ ...form, hasta: event.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-3 text-sm" />
                    </div>

                    <div className="flex items-center justify-end gap-2 border-t border-slate-100 pt-4 xl:col-span-10">
                        <button type="submit" className="inline-flex h-12 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white">
                            <Search className="h-5 w-5" />
                            Generar
                        </button>
                        <button type="button" onClick={clear} className="inline-flex h-12 items-center justify-center rounded-lg bg-slate-100 px-3 text-slate-700">
                            <X className="h-5 w-5" />
                        </button>
                        <a href={pdfUrl} target="_blank" className="inline-flex h-12 items-center justify-center rounded-lg bg-red-500 px-3 text-white" title="Exportar PDF">
                            <FileDown className="h-5 w-5" />
                        </a>
                    </div>
                </form>

                <div className="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-6">
                    {summary.map((item) => <SummaryCard key={item.label} item={item} />)}
                </div>

                <div className="px-5 pb-5">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="text-lg font-bold text-slate-950">{report.title}</h3>
                        <span className="text-sm font-semibold text-slate-500">{rows.length} registros</span>
                    </div>
                    <div className="overflow-x-auto rounded-lg border border-slate-200">
                        <table className="w-full min-w-[1100px] table-fixed text-left text-sm">
                            <thead className="bg-slate-50 text-xs font-bold uppercase text-slate-600">
                                <tr>
                                    {columns.map((column) => <th key={column} className="px-3 py-4">{column}</th>)}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={columns.length} className="px-4 py-10 text-center text-slate-500">No hay datos para los filtros seleccionados.</td>
                                    </tr>
                                ) : rows.map((row, rowIndex) => (
                                    <tr key={rowIndex} className={rowIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'}>
                                        {row.map((value, index) => (
                                            <td key={`${rowIndex}-${index}`} className="truncate px-3 py-4" title={String(value ?? '')}>
                                                {formatValue(value, index, columns)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </AuthenticatedLayout>
    );
}
