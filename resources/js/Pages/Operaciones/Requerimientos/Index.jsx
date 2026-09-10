import FlashMessage from '@/Components/Admin/FlashMessage';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { todayInLima } from '@/utils/date';
import { Head, router } from '@inertiajs/react';
import { Ban, Check, Edit2, Eye, FileText, MessageSquare, Plus, Send, Trash2, X } from 'lucide-react';
import { useState } from 'react';

const today = todayInLima();

function estadoClass(estado) {
    const styles = {
        borrador: 'bg-sky-100 text-sky-700',
        pendiente: 'bg-amber-100 text-amber-700',
        observado: 'bg-orange-100 text-orange-700',
        aprobado: 'bg-emerald-100 text-emerald-700',
        rechazado: 'bg-red-100 text-red-700',
        anulado: 'bg-red-100 text-red-700',
        en_compra: 'bg-indigo-100 text-indigo-700',
        atendido: 'bg-emerald-100 text-emerald-700',
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
                onFocus={() => { setOpen(true); setQuery(''); }}
                onChange={(event) => { setQuery(event.target.value); setOpen(true); onChange(''); }}
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

function RequerimientoForm({ requerimiento, productos, almacenes, centros, onClose }) {
    const [form, setForm] = useState(requerimiento ? {
        almacen_id: requerimiento.almacen_id ?? requerimiento.almacen?.id ?? '',
        centro_costo_id: requerimiento.centro_costo_id ?? requerimiento.centro_costo?.id ?? '',
        fecha_requerida: String(requerimiento.fecha_requerida ?? requerimiento.fecha ?? today).slice(0, 10),
        prioridad: requerimiento.prioridad ?? 'normal',
        motivo: requerimiento.motivo ?? '',
        observaciones: requerimiento.observaciones ?? '',
        estado: requerimiento.estado ?? 'borrador',
        detalles: requerimiento.detalles ?? [],
    } : {
        almacen_id: almacenes[0]?.id ?? '',
        centro_costo_id: centros[0]?.id ?? '',
        fecha_requerida: today,
        prioridad: 'normal',
        motivo: '',
        observaciones: '',
        estado: 'borrador',
        detalles: [],
    });
    const [item, setItem] = useState({ producto_id: '', cantidad_solicitada: 1, especificaciones: '' });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const fieldError = (name) => errors[name] ?? null;
    const detailErrors = Object.entries(errors).filter(([key]) => key.startsWith('detalles.'));

    const addItem = () => {
        if (!item.producto_id || Number(item.cantidad_solicitada) <= 0) return;
        const producto = productos.find((row) => Number(row.id) === Number(item.producto_id));
        setForm((current) => ({
            ...current,
            detalles: [...current.detalles, { ...item, producto, producto_id: Number(item.producto_id), cantidad_solicitada: Number(item.cantidad_solicitada) }],
        }));
        setItem({ producto_id: '', cantidad_solicitada: 1, especificaciones: '' });
    };

    const submit = (estado) => {
        const pendingItem = item.producto_id && Number(item.cantidad_solicitada) > 0
            ? [{
                producto_id: Number(item.producto_id),
                cantidad_solicitada: Number(item.cantidad_solicitada),
                especificaciones: item.especificaciones || null,
            }]
            : [];
        const detalles = [
            ...(form.detalles ?? []).map((detalle) => ({
                producto_id: detalle.producto_id,
                cantidad_solicitada: detalle.cantidad_solicitada ?? detalle.cantidad,
                especificaciones: detalle.especificaciones ?? detalle.observaciones ?? null,
            })),
            ...pendingItem,
        ];

        const payload = {
            ...form,
            estado,
            almacen_id: form.almacen_id || null,
            centro_costo_id: form.centro_costo_id || null,
            fecha_requerida: form.fecha_requerida || null,
            motivo: form.motivo || null,
            observaciones: form.observaciones || null,
            detalles,
        };
        const options = {
            preserveScroll: true,
            onStart: () => {
                setProcessing(true);
                setErrors({});
            },
            onError: (validationErrors) => setErrors(validationErrors),
            onSuccess: () => {
                onClose();
                router.reload({ only: ['requerimientos'], preserveScroll: true });
            },
            onFinish: () => setProcessing(false),
        };
        requerimiento?.id
            ? router.put(`/operaciones/requerimientos/${requerimiento.id}`, payload, options)
            : router.post('/operaciones/requerimientos', payload, options);
    };

    return (
        <Modal
            title={requerimiento?.id ? 'Editar Requerimiento' : 'Nuevo Requerimiento'}
            onClose={onClose}
            footer={
                <>
                    <button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cancelar</button>
                    <button type="button" disabled={processing} onClick={() => submit('borrador')} className="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-60">Guardar borrador</button>
                    <button type="button" disabled={processing} onClick={() => submit('enviado')} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-5 py-3 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"><Send className="h-5 w-5" /> {processing ? 'Guardando...' : 'Guardar y enviar'}</button>
                </>
            }
        >
            {Object.keys(errors).length > 0 && (
                <div className="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    Revisa los campos marcados. El requerimiento no se guardo porque falta informacion requerida.
                </div>
            )}
            <div className="grid gap-4 md:grid-cols-4">
                <label className="space-y-2"><span className="font-semibold text-slate-700">Obra / Unidad *</span><select value={form.centro_costo_id} onChange={(event) => setForm({ ...form, centro_costo_id: event.target.value })} className={`w-full rounded-lg border px-4 py-3 ${fieldError('centro_costo_id') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`}><option value="">Seleccione</option>{centros.map((row) => <option key={row.id} value={row.id}>{row.nombre}</option>)}</select>{fieldError('centro_costo_id') && <p className="text-sm font-semibold text-red-600">{fieldError('centro_costo_id')}</p>}</label>
                <label className="space-y-2"><span className="font-semibold text-slate-700">Almacen *</span><select value={form.almacen_id} onChange={(event) => setForm({ ...form, almacen_id: event.target.value })} className={`w-full rounded-lg border px-4 py-3 ${fieldError('almacen_id') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`}><option value="">Seleccione</option>{almacenes.map((row) => <option key={row.id} value={row.id}>{row.nombre}</option>)}</select>{fieldError('almacen_id') && <p className="text-sm font-semibold text-red-600">{fieldError('almacen_id')}</p>}</label>
                <label className="space-y-2"><span className="font-semibold text-slate-700">Fecha requerida *</span><input type="date" value={form.fecha_requerida} onChange={(event) => setForm({ ...form, fecha_requerida: event.target.value })} className={`w-full rounded-lg border px-4 py-3 ${fieldError('fecha_requerida') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`} />{fieldError('fecha_requerida') && <p className="text-sm font-semibold text-red-600">{fieldError('fecha_requerida')}</p>}</label>
                <label className="space-y-2"><span className="font-semibold text-slate-700">Prioridad *</span><select value={form.prioridad} onChange={(event) => setForm({ ...form, prioridad: event.target.value })} className={`w-full rounded-lg border px-4 py-3 ${fieldError('prioridad') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`}><option value="normal">Normal</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select>{fieldError('prioridad') && <p className="text-sm font-semibold text-red-600">{fieldError('prioridad')}</p>}</label>
            </div>
            <label className="mt-4 block space-y-2"><span className="font-semibold text-slate-700">Motivo del Requerimiento *</span><textarea value={form.motivo ?? ''} onChange={(event) => setForm({ ...form, motivo: event.target.value })} className={`h-20 w-full rounded-lg border px-4 py-3 ${fieldError('motivo') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`} />{fieldError('motivo') && <p className="text-sm font-semibold text-red-600">{fieldError('motivo')}</p>}</label>
            <label className="mt-4 block space-y-2"><span className="font-semibold text-slate-700">Observaciones</span><textarea value={form.observaciones ?? ''} onChange={(event) => setForm({ ...form, observaciones: event.target.value })} className={`h-20 w-full rounded-lg border px-4 py-3 ${fieldError('observaciones') ? 'border-red-300 bg-red-50' : 'border-slate-300'}`} />{fieldError('observaciones') && <p className="text-sm font-semibold text-red-600">{fieldError('observaciones')}</p>}</label>

            <div className="mt-5 flex items-center justify-between border-t border-slate-200 pt-5">
                <h3 className="text-lg font-bold text-slate-700">Productos Requeridos *</h3>
                <button type="button" onClick={addItem} className="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700"><Plus className="h-5 w-5" /> Agregar Producto</button>
            </div>
            <div className="mt-3 grid gap-3 md:grid-cols-[1fr_180px_1fr]">
                <SearchSelect options={productos} value={item.producto_id} onChange={(value) => setItem({ ...item, producto_id: value })} placeholder="Buscar producto..." />
                <input type="number" min="0.01" step="0.01" value={item.cantidad_solicitada} onChange={(event) => setItem({ ...item, cantidad_solicitada: event.target.value })} className="rounded-lg border border-slate-300 px-4 py-3" />
                <input value={item.especificaciones ?? ''} onChange={(event) => setItem({ ...item, especificaciones: event.target.value })} placeholder="Especificaciones" className="rounded-lg border border-slate-300 px-4 py-3" />
            </div>
            {fieldError('detalles') && <p className="mt-3 text-sm font-semibold text-red-600">{fieldError('detalles')}</p>}
            {detailErrors.length > 0 && <p className="mt-3 text-sm font-semibold text-red-600">Hay productos con datos incompletos o invalidos.</p>}
            <DetalleTable detalles={form.detalles} remove={(index) => setForm((current) => ({ ...current, detalles: current.detalles.filter((_, i) => i !== index) }))} />
        </Modal>
    );
}

function DetalleTable({ detalles, remove }) {
    return (
        <div className="mt-4 overflow-hidden rounded-lg border border-slate-300">
            <table className="w-full text-left">
                <thead className="bg-slate-50 text-sm uppercase text-slate-600"><tr><th className="px-4 py-3">Producto</th><th className="px-4 py-3">Cantidad</th><th className="px-4 py-3">Especificaciones</th>{remove && <th className="px-4 py-3 text-right">Acciones</th>}</tr></thead>
                <tbody>
                    {detalles.map((detalle, index) => <tr key={`${detalle.producto_id}-${index}`} className="border-t border-slate-200"><td className="px-4 py-3">{detalle.producto?.nombre ?? detalle.producto_nombre ?? '-'}</td><td className="px-4 py-3">{Number(detalle.cantidad_solicitada ?? detalle.cantidad ?? 0).toFixed(2)}</td><td className="px-4 py-3">{detalle.especificaciones ?? detalle.observaciones ?? '-'}</td>{remove && <td className="px-4 py-3 text-right"><button type="button" onClick={() => remove(index)} className="text-red-600"><Trash2 className="h-5 w-5" /></button></td>}</tr>)}
                    {detalles.length === 0 && <tr><td colSpan={remove ? 4 : 3} className="px-5 py-8 text-center text-slate-400">No hay productos agregados</td></tr>}
                </tbody>
            </table>
        </div>
    );
}

function DecisionModal({ action, requerimiento, onClose }) {
    const [comentario, setComentario] = useState('');
    const config = {
        aprobar: { title: 'Aprobar requerimiento', route: 'aprobar', button: 'Aprobar', className: 'bg-emerald-600' },
        observar: { title: 'Observar requerimiento', route: 'observar', button: 'Observar', className: 'bg-orange-600' },
        rechazar: { title: 'Rechazar requerimiento', route: 'rechazar', button: 'Rechazar', className: 'bg-red-600' },
        anular: { title: 'Anular requerimiento', route: 'anular', button: 'Anular', className: 'bg-red-700' },
    }[action];

    const submit = (event) => {
        event.preventDefault();
        router.put(`/operaciones/requerimientos/${requerimiento.id}/${config.route}`, { comentario }, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <form onSubmit={submit}>
            <Modal title={config.title} onClose={onClose} maxWidth="max-w-3xl" footer={<><button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cancelar</button><button type="submit" className={`rounded-lg px-5 py-3 font-semibold text-white ${config.className}`}>{config.button}</button></>}>
                <div className="rounded-lg bg-slate-50 p-5"><div className="text-sm text-slate-500">Requerimiento</div><div className="font-bold">{requerimiento.numero}</div></div>
                <label className="mt-5 block space-y-2"><span className="font-semibold text-slate-700">Comentario / motivo {action === 'aprobar' ? '(opcional)' : '*'}</span><textarea value={comentario} onChange={(event) => setComentario(event.target.value)} className="h-28 w-full rounded-lg border border-slate-300 px-4 py-3" required={action !== 'aprobar'} /></label>
            </Modal>
        </form>
    );
}

function DetailModal({ requerimiento, onClose }) {
    return (
        <Modal title="Detalle del requerimiento" onClose={onClose} maxWidth="max-w-5xl" footer={<button type="button" onClick={onClose} className="rounded-lg bg-slate-100 px-5 py-3 font-semibold text-slate-700">Cerrar</button>}>
            <div className="grid gap-4 rounded-lg bg-slate-50 p-5 md:grid-cols-4">
                <div><div className="text-sm text-slate-500">Numero</div><div className="font-bold text-blue-700">{requerimiento.numero}</div></div>
                <div><div className="text-sm text-slate-500">Centro</div><div className="font-bold">{requerimiento.centro_costo?.nombre ?? requerimiento.centro?.nombre ?? '-'}</div></div>
                <div><div className="text-sm text-slate-500">Almacen</div><div className="font-bold">{requerimiento.almacen?.nombre ?? '-'}</div></div>
                <div><div className="text-sm text-slate-500">Estado</div><span className={`rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(requerimiento.estado)}`}>{requerimiento.estado}</span></div>
            </div>
            <div className="mt-5 rounded-lg border border-slate-200 p-5"><div className="font-bold text-slate-700">Motivo</div><p className="mt-2 text-slate-600">{requerimiento.motivo ?? '-'}</p></div>
            <DetalleTable detalles={requerimiento.detalles ?? []} />
        </Modal>
    );
}

export default function Index({ requerimientos = [], productos = [], almacenes = [], centros = [] }) {
    const [formOpen, setFormOpen] = useState(null);
    const [detailOpen, setDetailOpen] = useState(null);
    const [decisionOpen, setDecisionOpen] = useState(null);
    return (
        <AuthenticatedLayout headerTitle="Requerimientos" headerSubtitle="Logistica">
            <Head title="Requerimientos" />
            <div className="px-3 py-8 xl:px-5">
                <FlashMessage />
                <section className="mx-auto w-full max-w-none overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-8 py-7">
                        <div><h1 className="text-2xl font-bold text-slate-900">Requerimientos</h1><p className="mt-2 text-slate-500">Solicitudes internas de productos para operaciones.</p></div>
                        <button onClick={() => setFormOpen({})} className="inline-flex items-center gap-2 rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white"><Plus className="h-5 w-5" /> Nuevo</button>
                    </div>
                    <div className="p-8">
                        <div className="overflow-hidden rounded-lg border border-slate-200">
                            <table className="w-full table-fixed text-left">
                                <thead className="bg-slate-50 text-sm uppercase text-slate-600"><tr><th className="w-[13%] px-4 py-4">Numero</th><th className="w-[9%] px-4 py-4">Fecha</th><th className="w-[20%] px-4 py-4">Centro</th><th className="w-[17%] px-4 py-4">Almacen</th><th className="w-[6%] px-4 py-4">Items</th><th className="w-[10%] px-4 py-4">Estado</th><th className="w-[25%] px-4 py-4 text-right">Acciones</th></tr></thead>
                                <tbody>
                                    {requerimientos.map((requerimiento) => {
                                        const editable = ['borrador', 'observado'].includes(requerimiento.estado);
                                        const pending = requerimiento.estado === 'pendiente';
                                        const cancellable = !['anulado', 'rechazado', 'atendido', 'en_compra'].includes(requerimiento.estado);

                                        return (
                                            <tr key={requerimiento.id} className="border-t border-slate-200">
                                                <td className="whitespace-nowrap px-4 py-5 font-mono">{requerimiento.numero}</td>
                                                <td className="whitespace-nowrap px-4 py-5">{String(requerimiento.fecha_requerida ?? requerimiento.fecha ?? '').slice(0, 10)}</td>
                                                <td className="whitespace-nowrap px-4 py-5">{requerimiento.centro_costo?.nombre ?? requerimiento.centro?.nombre ?? '-'}</td>
                                                <td className="whitespace-nowrap px-4 py-5">{requerimiento.almacen?.nombre ?? '-'}</td>
                                                <td className="px-4 py-5">{requerimiento.detalles?.length ?? 0}</td>
                                                <td className="px-4 py-5"><span className={`inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase ${estadoClass(requerimiento.estado)}`}>{requerimiento.estado}</span></td>
                                                <td className="px-4 py-5">
                                                    <div className="flex justify-end gap-1.5">
                                                        <button title="Ver" onClick={() => setDetailOpen(requerimiento)} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200"><Eye className="h-5 w-5 text-blue-600" /></button>
                                                        {editable && <button title="Editar" onClick={() => setFormOpen(requerimiento)} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200"><Edit2 className="h-5 w-5 text-slate-700" /></button>}
                                                        {pending && <button title="Aprobar" onClick={() => setDecisionOpen({ action: 'aprobar', requerimiento })} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200"><Check className="h-5 w-5 text-emerald-600" /></button>}
                                                        {pending && <button title="Observar" onClick={() => setDecisionOpen({ action: 'observar', requerimiento })} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-orange-200"><MessageSquare className="h-5 w-5 text-orange-600" /></button>}
                                                        {pending && <button title="Rechazar" onClick={() => setDecisionOpen({ action: 'rechazar', requerimiento })} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200"><Ban className="h-5 w-5 text-red-600" /></button>}
                                                        {cancellable && <button title="Anular" onClick={() => setDecisionOpen({ action: 'anular', requerimiento })} className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-200"><X className="h-5 w-5 text-red-700" /></button>}
                                                        <a title="PDF" href={`/operaciones/requerimientos/${requerimiento.id}/pdf`} target="_blank" className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200"><FileText className="h-5 w-5 text-blue-600" /></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {requerimientos.length === 0 && <tr><td colSpan="7" className="px-5 py-10 text-center text-slate-400">No hay requerimientos registrados</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
            {formOpen && <RequerimientoForm requerimiento={formOpen.id ? formOpen : null} productos={productos} almacenes={almacenes} centros={centros} onClose={() => setFormOpen(null)} />}
            {detailOpen && <DetailModal requerimiento={detailOpen} onClose={() => setDetailOpen(null)} />}
            {decisionOpen && <DecisionModal action={decisionOpen.action} requerimiento={decisionOpen.requerimiento} onClose={() => setDecisionOpen(null)} />}
        </AuthenticatedLayout>
    );
}

