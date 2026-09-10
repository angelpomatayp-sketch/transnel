import FlashMessage from '@/Components/Admin/FlashMessage';
import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { todayInLima } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { Ban, Eye, FileText, Plus, RotateCcw, Send, X } from 'lucide-react';
import { useState } from 'react';

const today = todayInLima();

function money(value) {
    return `S/ ${Number(value ?? 0).toFixed(2)}`;
}

function qty(value) {
    return Number(value ?? 0).toFixed(2);
}

function estadoClass(estado) {
    const styles = {
        pendiente: 'bg-amber-100 text-amber-700',
        recibido_parcial: 'bg-sky-100 text-sky-700',
        recibido: 'bg-emerald-100 text-emerald-700',
        anulado: 'bg-red-100 text-red-700',
    };
    return styles[estado] ?? 'bg-slate-100 text-slate-700';
}

function Modal({ title, children, onClose, footer, maxWidth = 'max-w-6xl' }) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4">
            <div className={`flex max-h-[92vh] w-full ${maxWidth} flex-col overflow-hidden rounded-lg bg-white shadow-2xl`}>
                <div className="flex items-center justify-between border-b border-slate-200 px-7 py-4">
                    <h2 className="text-xl font-bold text-slate-800">{title}</h2>
                    <button type="button" onClick={onClose} className="rounded-full border border-slate-300 p-2 text-slate-600 hover:bg-slate-50"><X className="h-5 w-5" /></button>
                </div>
                <div className="overflow-y-auto px-7 py-5">{children}</div>
                {footer && <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-7 py-4">{footer}</div>}
            </div>
        </div>
    );
}

function SearchSelect({ options, value, onChange, placeholder }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = options.find((option) => Number(option.id) === Number(value));
    const filtered = options.filter((option) => `${option.nombre ?? ''} ${option.codigo ?? ''}`.toLowerCase().includes(query.toLowerCase())).slice(0, 5);

    return (
        <div className="relative">
            <input
                value={open ? query : (selected?.nombre ?? '')}
                onFocus={() => {
                    setOpen(true);
                    setQuery('');
                }}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                    onChange('');
                }}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
                placeholder={placeholder}
                className="w-full rounded-lg border border-slate-300 px-4 py-3"
            />
            {open && (
                <div className="absolute z-30 mt-1 max-h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    {filtered.map((option) => (
                        <button key={option.id} type="button" onMouseDown={(event) => event.preventDefault()} onClick={() => { onChange(option.id); setQuery(option.nombre); setOpen(false); }} className="block w-full px-4 py-3 text-left hover:bg-slate-50">
                            <div className="font-semibold">{option.nombre}</div>
                            <div className="text-sm text-slate-500">{option.codigo ?? '-'}</div>
                        </button>
                    ))}
                    {filtered.length === 0 && <div className="px-4 py-3 text-slate-400">Sin resultados</div>}
                </div>
            )}
        </div>
    );
}

function ProviderSearchSelect({ options, value, onChange }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = options.find((option) => Number(option.id) === Number(value));
    const filtered = options
        .filter((option) => `${option.razon_social ?? ''} ${option.ruc ?? ''} ${option.nombre_comercial ?? ''}`.toLowerCase().includes(query.toLowerCase()))
        .slice(0, 5);

    return (
        <div className="relative">
            <input
                value={open ? query : (selected?.razon_social ?? '')}
                onFocus={() => {
                    setOpen(true);
                    setQuery('');
                }}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                    onChange('');
                }}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
                placeholder="Buscar proveedor..."
                className="w-full rounded-lg border border-slate-300 px-4 py-3"
            />
            {open && (
                <div className="absolute z-30 mt-1 max-h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    {filtered.map((option) => (
                        <button
                            key={option.id}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onChange(option.id);
                                setQuery(option.razon_social);
                                setOpen(false);
                            }}
                            className="block w-full px-4 py-3 text-left hover:bg-slate-50"
                        >
                            <div className="font-semibold">{option.razon_social}</div>
                            <div className="text-sm text-slate-500">RUC: {option.ruc ?? '-'}</div>
                        </button>
                    ))}
                    {filtered.length === 0 && <div className="px-4 py-3 text-slate-400">Sin resultados</div>}
                </div>
            )}
        </div>
    );
}

function RequirementSearchSelect({ options, value, onChange }) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const selected = options.find((option) => Number(option.id) === Number(value));
    const filtered = options
        .filter((option) => `${option.numero ?? ''} ${option.centro_costo?.nombre ?? ''} ${option.almacen?.nombre ?? ''}`.toLowerCase().includes(query.toLowerCase()))
        .slice(0, 5);

    return (
        <div className="relative">
            <input
                value={open ? query : (selected?.numero ?? '')}
                onFocus={() => {
                    setOpen(true);
                    setQuery('');
                }}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                    onChange('');
                }}
                onBlur={() => setTimeout(() => setOpen(false), 150)}
                placeholder="Buscar requerimiento aprobado..."
                className="w-full rounded-lg border border-slate-300 px-4 py-3"
            />
            {open && (
                <div className="absolute z-30 mt-1 max-h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                    {filtered.map((option) => (
                        <button
                            key={option.id}
                            type="button"
                            onMouseDown={(event) => event.preventDefault()}
                            onClick={() => {
                                onChange(option.id);
                                setQuery(option.numero);
                                setOpen(false);
                            }}
                            className="block w-full px-4 py-3 text-left hover:bg-slate-50"
                        >
                            <div className="font-semibold">{option.numero}</div>
                            <div className="text-sm text-slate-500">{option.centro_costo?.nombre ?? option.almacen?.nombre ?? 'Requerimiento aprobado'}</div>
                        </button>
                    ))}
                    {filtered.length === 0 && <div className="px-4 py-3 text-slate-400">Sin resultados</div>}
                </div>
            )}
        </div>
    );
}

function CompraForm({ proveedores, productos, almacenes, centros, requerimientos, onClose }) {
    const [form, setForm] = useState({
        proveedor_id: '',
        requerimiento_id: '',
        almacen_id: '',
        centro_costo_id: '',
        fecha: today,
        fecha_entrega: '',
        documento_referencia: '',
        observaciones: '',
        detalles: [],
    });
    const [item, setItem] = useState({ producto_id: '', cantidad: 1, precio_unitario: 0, observaciones: '' });

    const addItem = () => {
        if (!item.producto_id || Number(item.cantidad) <= 0) return;
        const producto = productos.find((row) => Number(row.id) === Number(item.producto_id));
        setForm((current) => ({
            ...current,
            detalles: [...current.detalles, { ...item, producto, cantidad: Number(item.cantidad), precio_unitario: Number(item.precio_unitario) }],
        }));
        setItem({ producto_id: '', cantidad: 1, precio_unitario: 0, observaciones: '' });
    };

    const applyRequirement = (id) => {
        const requerimiento = requerimientos.find((row) => Number(row.id) === Number(id));
        if (!requerimiento) {
            setForm((current) => ({ ...current, requerimiento_id: '' }));
            return;
        }

        setForm((current) => ({
            ...current,
            requerimiento_id: id,
            almacen_id: requerimiento.almacen_id ?? requerimiento.almacen?.id ?? current.almacen_id,
            centro_costo_id: requerimiento.centro_costo_id ?? requerimiento.centro_costo?.id ?? current.centro_costo_id,
            detalles: (requerimiento.detalles ?? []).map((detalle) => ({
                producto_id: detalle.producto_id,
                producto: detalle.producto,
                cantidad: Number(detalle.cantidad_solicitada ?? detalle.cantidad ?? 1),
                precio_unitario: 0,
                observaciones: detalle.especificaciones ?? detalle.observaciones ?? null,
            })),
        }));
    };

    const submit = (event) => {
        event.preventDefault();
        router.post('/operaciones/compras', form, { preserveScroll: true, onSuccess: onClose });
    };

    const total = form.detalles.reduce((sum, row) => sum + Number(row.cantidad) * Number(row.precio_unitario), 0);

    return (
        <form onSubmit={submit}>
            <Modal title="Nueva orden de compra" onClose={onClose} footer={<><button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cancelar</button><button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-5 py-3 font-semibold text-white"><Send className="h-5 w-5" /> Guardar orden</button></>}>
                <div className="grid gap-4 md:grid-cols-4">
                    <label className="space-y-2 md:col-span-2">
                        <span className="font-semibold text-slate-700">Proveedor *</span>
                        <ProviderSearchSelect options={proveedores} value={form.proveedor_id} onChange={(value) => setForm({ ...form, proveedor_id: value })} />
                    </label>
                    <label className="space-y-2 md:col-span-2">
                        <span className="font-semibold text-slate-700">Requerimiento aprobado</span>
                        <RequirementSearchSelect options={requerimientos} value={form.requerimiento_id} onChange={applyRequirement} />
                    </label>
                    <label className="space-y-2"><span className="font-semibold text-slate-700">Almacen *</span><select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3"><option value="">Seleccionar</option>{almacenes.map((row) => <option key={row.id} value={row.id}>{row.nombre}</option>)}</select></label>
                    <label className="space-y-2"><span className="font-semibold text-slate-700">Fecha *</span><input type="date" value={form.fecha} onChange={(event) => setForm({ ...form, fecha: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" /></label>
                    <label className="space-y-2"><span className="font-semibold text-slate-700">Obra / Unidad</span><select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3"><option value="">Sin centro</option>{centros.map((row) => <option key={row.id} value={row.id}>{row.nombre}</option>)}</select></label>
                    <label className="space-y-2"><span className="font-semibold text-slate-700">Fecha entrega</span><input type="date" value={form.fecha_entrega} onChange={(event) => setForm({ ...form, fecha_entrega: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" /></label>
                    <label className="space-y-2 md:col-span-2"><span className="font-semibold text-slate-700">Doc. referencia</span><input value={form.documento_referencia} onChange={(event) => setForm({ ...form, documento_referencia: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" /></label>
                </div>

                <div className="mt-5 rounded-lg border border-slate-800 p-4">
                    <div className="mb-3 font-bold text-slate-700">Productos</div>
                    <div className="grid gap-3 md:grid-cols-[1fr_140px_170px_1fr_auto]">
                        <SearchSelect options={productos} value={item.producto_id} onChange={(value) => setItem({ ...item, producto_id: value })} placeholder="Buscar producto..." />
                        <input type="number" min="0.01" step="0.01" value={item.cantidad} onChange={(event) => setItem({ ...item, cantidad: event.target.value })} className="rounded-lg border border-slate-300 px-4 py-3" />
                        <input type="number" min="0" step="0.01" value={item.precio_unitario} onChange={(event) => setItem({ ...item, precio_unitario: event.target.value })} className="rounded-lg border border-slate-300 px-4 py-3" />
                        <input value={item.observaciones} onChange={(event) => setItem({ ...item, observaciones: event.target.value })} placeholder="Observaciones" className="rounded-lg border border-slate-300 px-4 py-3" />
                        <button type="button" onClick={addItem} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Agregar</button>
                    </div>
                </div>

                <DetalleTable detalles={form.detalles} remove={(index) => setForm((current) => ({ ...current, detalles: current.detalles.filter((_, i) => i !== index) }))} />
                <div className="mt-4 text-right text-lg font-bold">Total: {money(total * 1.18)}</div>
            </Modal>
        </form>
    );
}

function DetalleTable({ detalles, remove }) {
    return (
        <div className="mt-5 overflow-hidden rounded-lg border border-slate-200">
            <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm uppercase text-slate-600"><tr><th className="px-4 py-3">Producto</th><th className="px-4 py-3">Cant.</th><th className="px-4 py-3">Recibido</th><th className="px-4 py-3">P. Unit.</th><th className="px-4 py-3">Subtotal</th>{remove && <th className="px-4 py-3 text-right">Acciones</th>}</tr></thead>
                <tbody>
                    {detalles.map((detalle, index) => <tr key={`${detalle.producto_id}-${index}`} className="border-t border-slate-200"><td className="px-4 py-3">{detalle.producto?.nombre}</td><td className="px-4 py-3">{qty(detalle.cantidad)}</td><td className="px-4 py-3">{qty(detalle.cantidad_recibida)}</td><td className="px-4 py-3">{money(detalle.precio_unitario)}</td><td className="px-4 py-3">{money(Number(detalle.cantidad) * Number(detalle.precio_unitario))}</td>{remove && <td className="px-4 py-3 text-right"><button type="button" onClick={() => remove(index)} className="text-red-600">Quitar</button></td>}</tr>)}
                    {detalles.length === 0 && <tr><td colSpan={remove ? 6 : 5} className="px-4 py-8 text-center text-slate-400">No hay productos agregados</td></tr>}
                </tbody>
            </table>
        </div>
    );
}

function ReceiveModal({ compra, onClose }) {
    const [form, setForm] = useState({
        documento_recepcion: '',
        detalles: (compra.detalles ?? []).map((detalle) => ({
            id: detalle.id,
            producto: detalle.producto,
            cantidad: detalle.cantidad,
            cantidad_recibida_actual: detalle.cantidad_recibida ?? 0,
            pendiente: Math.max(0, Number(detalle.cantidad) - Number(detalle.cantidad_recibida ?? 0)),
            cantidad_recibida: Math.max(0, Number(detalle.cantidad) - Number(detalle.cantidad_recibida ?? 0)),
        })),
    });

    const submit = (event) => {
        event.preventDefault();
        router.put(`/operaciones/compras/${compra.id}/recibir`, form, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <form onSubmit={submit}>
            <Modal title="Recepcionar compra" onClose={onClose} maxWidth="max-w-5xl" footer={<><button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cancelar</button><button type="submit" className="rounded-lg bg-slate-800 px-5 py-3 font-semibold text-white">Confirmar recepcion</button></>}>
                <label className="mb-4 block space-y-2"><span className="font-semibold text-slate-700">Documento recepcion</span><input value={form.documento_recepcion} onChange={(event) => setForm({ ...form, documento_recepcion: event.target.value })} className="w-full rounded-lg border border-slate-300 px-4 py-3" /></label>
                <div className="overflow-hidden rounded-lg border border-slate-200">
                    <table className="w-full text-left">
                        <thead className="bg-slate-50 text-sm uppercase text-slate-600"><tr><th className="px-4 py-3">Producto</th><th className="px-4 py-3">Pedido</th><th className="px-4 py-3">Ya recibido</th><th className="px-4 py-3">Pendiente</th><th className="px-4 py-3">Recibir ahora</th></tr></thead>
                        <tbody>{form.detalles.map((detalle, index) => <tr key={detalle.id} className="border-t border-slate-200"><td className="px-4 py-3">{detalle.producto?.nombre}</td><td className="px-4 py-3">{qty(detalle.cantidad)}</td><td className="px-4 py-3">{qty(detalle.cantidad_recibida_actual)}</td><td className="px-4 py-3">{qty(detalle.pendiente)}</td><td className="px-4 py-3"><input type="number" min="0" max={detalle.pendiente} step="0.01" value={detalle.cantidad_recibida} onChange={(event) => setForm((current) => ({ ...current, detalles: current.detalles.map((row, rowIndex) => rowIndex === index ? { ...row, cantidad_recibida: event.target.value } : row) }))} className="w-32 rounded-lg border border-slate-300 px-3 py-2" /></td></tr>)}</tbody>
                    </table>
                </div>
            </Modal>
        </form>
    );
}

function DetailModal({ compra, onClose }) {
    return (
        <Modal title="Detalle de compra" onClose={onClose} maxWidth="max-w-5xl" footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cerrar</button>}>
            <div className="grid gap-4 rounded-lg bg-slate-50 p-5 md:grid-cols-4">
                <div><div className="text-sm text-slate-500">Numero</div><div className="font-bold text-blue-700">{compra.numero}</div></div>
                <div><div className="text-sm text-slate-500">Proveedor</div><div className="font-bold">{compra.proveedor?.razon_social}</div></div>
                <div><div className="text-sm text-slate-500">Almacen</div><div className="font-bold">{compra.almacen?.nombre}</div></div>
                <div><div className="text-sm text-slate-500">Estado</div><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(compra.estado)}`}>{compra.estado}</span></div>
            </div>
            <DetalleTable detalles={compra.detalles ?? []} />
            <div className="mt-4 text-right text-lg font-bold">Total: {money(compra.total)}</div>
        </Modal>
    );
}

export default function Index({ compras = [], proveedores = [], productos = [], almacenes = [], centros = [], requerimientos = [] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [detailOpen, setDetailOpen] = useState(null);
    const [receiveOpen, setReceiveOpen] = useState(null);
    const compraRows = compras.data ?? compras;

    return (
        <AuthenticatedLayout headerTitle="Compras" headerSubtitle="Logistica">
            <Head title="Compras" />
            <div className="px-3 py-8 xl:px-5">
                <FlashMessage />
                <section className="mx-auto w-full max-w-none overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-8 py-7">
                        <div><h1 className="text-2xl font-bold text-slate-900">Compras</h1><p className="mt-2 text-slate-500">Ordenes de compra y recepcion de productos.</p></div>
                        <button onClick={() => setCreateOpen(true)} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white"><Plus className="h-5 w-5" /> Nueva compra</button>
                    </div>
                    <div className="p-5 xl:p-6">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600"><tr><th className="w-[14%] px-4 py-4">Numero</th><th className="w-[24%] px-4 py-4">Proveedor</th><th className="w-[11%] px-4 py-4">Fecha</th><th className="w-[15%] px-4 py-4">Almacen</th><th className="w-[7%] px-4 py-4">Items</th><th className="w-[13%] px-4 py-4">Estado</th><th className="w-[16%] px-4 py-4 text-right">Acciones</th></tr></thead>
                                <tbody>
                                    {compraRows.map((compra) => <tr key={compra.id} className="border-t border-slate-200"><td className="whitespace-nowrap px-4 py-5 font-mono">{compra.numero}</td><td className="px-4 py-5 font-semibold leading-tight">{compra.proveedor?.razon_social}</td><td className="whitespace-nowrap px-4 py-5">{String(compra.fecha ?? '').slice(0, 10)}</td><td className="px-4 py-5">{compra.almacen?.nombre}</td><td className="px-4 py-5">{compra.detalles?.length ?? 0}</td><td className="px-4 py-5"><span className={`inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(compra.estado)}`}>{compra.estado}</span></td><td className="px-4 py-5"><div className="flex justify-end gap-1.5"><button title="Ver" onClick={() => setDetailOpen(compra)} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200"><Eye className="h-5 w-5 text-blue-600" /></button><a title="PDF" href={`/operaciones/compras/${compra.id}/pdf`} target="_blank" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200"><FileText className="h-5 w-5 text-blue-600" /></a>{!['recibido', 'anulado'].includes(compra.estado) && <button title="Recepcionar" onClick={() => setReceiveOpen(compra)} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200"><RotateCcw className="h-5 w-5 text-emerald-600" /></button>} {!['recibido', 'anulado'].includes(compra.estado) && <button title="Anular" onClick={() => { const motivo = window.prompt('Motivo de anulacion'); if (motivo) router.put(`/operaciones/compras/${compra.id}/anular`, { motivo }, { preserveScroll: true }); }} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200"><Ban className="h-5 w-5 text-red-600" /></button>}</div></td></tr>)}
                                    {compraRows.length === 0 && <tr><td colSpan="7" className="px-5 py-10 text-center text-slate-400">No hay compras registradas</td></tr>}
                                </tbody>
                            </table>
                        </div>
                        <Pagination paginator={compras} />
                    </div>
                </section>
            </div>
            {createOpen && <CompraForm proveedores={proveedores} productos={productos} almacenes={almacenes} centros={centros} requerimientos={requerimientos} onClose={() => setCreateOpen(false)} />}
            {detailOpen && <DetailModal compra={detailOpen} onClose={() => setDetailOpen(null)} />}
            {receiveOpen && <ReceiveModal compra={receiveOpen} onClose={() => setReceiveOpen(null)} />}
        </AuthenticatedLayout>
    );
}

