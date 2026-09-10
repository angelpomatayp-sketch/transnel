import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function ProductSearch({ products, value, onChange }) {
    const selected = products.find((product) => String(product.id) === String(value));
    const [query, setQuery] = useState(selected?.nombre ?? '');
    const [open, setOpen] = useState(false);

    useEffect(() => {
        setQuery(selected?.nombre ?? '');
    }, [selected?.id]);

    const filtered = useMemo(() => {
        const search = query.trim().toLowerCase();
        const source = search
            ? products.filter((product) => `${product.codigo} ${product.nombre}`.toLowerCase().includes(search))
            : products;

        return source.slice(0, 5);
    }, [products, query]);

    return (
        <label className="relative block">
            <span className="text-sm font-semibold text-slate-900">Producto</span>
            <div className="mt-1 flex h-10 overflow-hidden rounded-md border border-slate-300 bg-white shadow-sm focus-within:border-accent-400 focus-within:ring-2 focus-within:ring-accent-100">
                <input
                    type="text"
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        onChange('');
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    onBlur={() => window.setTimeout(() => setOpen(false), 120)}
                    className="min-w-0 flex-1 border-0 px-3 text-sm text-slate-900 focus:outline-none focus:ring-0"
                    placeholder="Buscar producto..."
                    autoComplete="off"
                />
                <button type="button" onClick={() => setOpen((current) => !current)} className="w-10 border-l border-slate-300 text-slate-500">
                    <Search className="mx-auto h-4 w-4" />
                </button>
            </div>
            {open && (
                <div className="absolute z-40 mt-1 max-h-52 w-full overflow-hidden rounded-md border border-slate-300 bg-white py-1 text-sm shadow-lg">
                    {filtered.length === 0 && <div className="px-3 py-2 text-slate-400">Sin resultados</div>}
                    {filtered.map((product) => (
                        <button
                            key={product.id}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onChange(product.id);
                                setQuery(product.nombre);
                                setOpen(false);
                            }}
                            className="block w-full px-3 py-2 text-left text-slate-700 hover:bg-sky-50 hover:text-sky-700"
                        >
                            {product.nombre}
                        </button>
                    ))}
                </div>
            )}
        </label>
    );
}

function typeBadge(type) {
    const normalized = String(type || '').toUpperCase();

    if (normalized.includes('ENTRADA')) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (normalized.includes('SALIDA')) {
        return 'bg-red-100 text-red-700';
    }

    if (normalized.includes('ANULACION')) {
        return 'bg-slate-200 text-slate-700';
    }

    return 'bg-sky-100 text-sky-700';
}

function typeLabel(type) {
    return String(type || '-')
        .replaceAll('_', ' ')
        .toLowerCase()
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function money(value) {
    return `S/ ${Number(value || 0).toFixed(2)}`;
}

function quantity(value) {
    const number = Number(value || 0);
    return number === 0 ? '-' : number.toFixed(2);
}

export default function Index({ kardex, filters, productos, almacenes, tipos, scope = {} }) {
    const [form, setForm] = useState({
        producto_id: filters.producto_id ?? '',
        almacen_id: filters.almacen_id ?? '',
        tipo: filters.tipo ?? '',
        desde: filters.desde ?? '',
        hasta: filters.hasta ?? '',
        incluir_anulados: Boolean(filters.incluir_anulados),
    });

    function setField(field, value) {
        setForm((current) => ({ ...current, [field]: value }));
    }

    function search(event) {
        event.preventDefault();

        router.get(route('inventario.kardex.index'), form, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function clearFilters() {
        router.get(route('inventario.kardex.index'), scope.es_almacenero ? { almacen_id: scope.almacen_id } : {}, {
            preserveState: false,
            preserveScroll: true,
            replace: true,
        });
    }

    return (
        <AuthenticatedLayout title="Kardex">
            <Head title="Kardex" />
            <div className="space-y-5">
                <FlashMessage />

                <form onSubmit={search} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="grid gap-4 xl:grid-cols-[1.6fr_0.75fr_0.75fr_1.15fr_1.15fr]">
                        <ProductSearch products={productos} value={form.producto_id} onChange={(value) => setField('producto_id', value)} />
                        <label className="block">
                            <span className="text-sm font-semibold text-slate-900">Almacen</span>
                            <select value={form.almacen_id} disabled={scope.es_almacenero} onChange={(event) => setField('almacen_id', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100 disabled:bg-slate-100">
                                <option value="">Todos</option>
                                {almacenes.map((almacen) => <option key={almacen.id} value={almacen.id}>{almacen.nombre}</option>)}
                            </select>
                        </label>
                        <label className="block">
                            <span className="text-sm font-semibold text-slate-900">Tipo</span>
                            <select value={form.tipo} onChange={(event) => setField('tipo', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100">
                                <option value="">Todos</option>
                                {tipos.map((tipo) => <option key={tipo} value={tipo}>{typeLabel(tipo)}</option>)}
                            </select>
                        </label>
                        <label className="block">
                            <span className="text-sm font-semibold text-slate-900">Desde</span>
                            <input type="date" value={form.desde} onChange={(event) => setField('desde', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100" />
                        </label>
                        <label className="block">
                            <span className="text-sm font-semibold text-slate-900">Hasta</span>
                            <input type="date" value={form.hasta} onChange={(event) => setField('hasta', event.target.value)} className="mt-1 h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 shadow-sm focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-100" />
                        </label>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                checked={form.incluir_anulados}
                                onChange={(event) => setField('incluir_anulados', event.target.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-brand-700 focus:ring-accent-400"
                            />
                            Incluir anulados
                        </label>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={clearFilters} className="inline-flex h-10 items-center rounded-md border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-500 hover:bg-slate-50">
                                <X className="mr-2 h-4 w-4" />
                                Limpiar
                            </button>
                            <button type="submit" className="inline-flex h-10 items-center rounded-md bg-brand-700 px-4 text-sm font-semibold text-white hover:bg-brand-800">
                                <Search className="mr-2 h-4 w-4" />
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>

                <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-[1180px] table-fixed divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr>
                                    <th className="w-28 px-4 py-3 text-left font-bold text-slate-900">Fecha</th>
                                    <th className="w-56 px-4 py-3 text-left font-bold text-slate-900">Producto</th>
                                    <th className="w-40 px-4 py-3 text-left font-bold text-slate-900">Almacen</th>
                                    <th className="w-28 px-4 py-3 text-left font-bold text-slate-900">Tipo</th>
                                    <th className="w-32 px-4 py-3 text-left font-bold text-slate-900">Documento</th>
                                    <th className="w-24 px-4 py-3 text-right font-bold text-slate-900">Entrada</th>
                                    <th className="w-24 px-4 py-3 text-right font-bold text-slate-900">Salida</th>
                                    <th className="w-24 px-4 py-3 text-right font-bold text-slate-900">C. Unit.</th>
                                    <th className="w-28 px-4 py-3 text-right font-bold text-slate-900">Costo Total</th>
                                    <th className="w-28 px-4 py-3 text-right font-bold text-slate-900">Saldo Cant.</th>
                                    <th className="w-28 px-4 py-3 text-right font-bold text-slate-900">Saldo Valor</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {kardex.data.length === 0 && (
                                    <tr>
                                        <td colSpan={11} className="px-4 py-8 text-center text-slate-500">
                                            Sin movimientos de kardex para los filtros seleccionados.
                                        </td>
                                    </tr>
                                )}
                                {kardex.data.map((row) => {
                                    const isEntry = Number(row.cantidad_entrada || 0) > 0;
                                    const unitCost = isEntry ? row.costo_entrada : row.costo_salida;
                                    const totalCost = isEntry ? row.total_entrada : row.total_salida;
                                    const document = row.movimiento?.documento || row.movimiento?.numero || '-';

                                    return (
                                        <tr key={row.id} className="odd:bg-white even:bg-slate-50">
                                            <td className="truncate whitespace-nowrap px-4 py-4 text-slate-800">{row.fecha?.substring(0, 10) ?? '-'}</td>
                                            <td className="truncate whitespace-nowrap px-4 py-4 text-slate-800" title={row.producto?.nombre ?? '-'}>
                                                {row.producto?.nombre ?? '-'}
                                            </td>
                                            <td className="truncate whitespace-nowrap px-4 py-4 text-slate-800" title={row.almacen?.nombre ?? '-'}>
                                                {row.almacen?.nombre ?? '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-4">
                                                <span className={`inline-flex rounded-md px-2 py-1 text-xs font-bold ${typeBadge(row.tipo)}`}>
                                                    {typeLabel(row.tipo)}
                                                </span>
                                            </td>
                                            <td className="truncate whitespace-nowrap px-4 py-4 font-mono text-xs text-blue-700" title={document}>{document}</td>
                                            <td className="whitespace-nowrap px-4 py-4 text-right font-semibold text-emerald-600">{quantity(row.cantidad_entrada)}</td>
                                            <td className="whitespace-nowrap px-4 py-4 text-right font-semibold text-red-600">{quantity(row.cantidad_salida)}</td>
                                            <td className="whitespace-nowrap px-4 py-4 text-right text-slate-800">{money(unitCost)}</td>
                                            <td className={`whitespace-nowrap px-4 py-4 text-right ${isEntry ? 'text-emerald-600' : 'text-red-600'}`}>{money(totalCost)}</td>
                                            <td className="whitespace-nowrap px-4 py-4 text-right font-bold text-slate-800">{Number(row.saldo_cantidad || 0).toFixed(2)}</td>
                                            <td className="whitespace-nowrap px-4 py-4 text-right font-bold text-blue-700">{money(row.saldo_total)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {kardex.links?.length > 3 && (
                        <div className="mt-4 flex flex-wrap justify-end gap-2">
                            {kardex.links.map((link, index) => (
                                <button
                                    key={`${link.label}-${index}`}
                                    type="button"
                                    disabled={!link.url}
                                    onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                                    className={`h-9 min-w-9 rounded-md border px-3 text-sm font-semibold ${
                                        link.active
                                            ? 'border-brand-700 bg-brand-700 text-white'
                                            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}


